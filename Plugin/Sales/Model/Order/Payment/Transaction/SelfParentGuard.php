<?php declare(strict_types=1);
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\Payment\Transaction;

use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Break the transaction parent link when a transaction is its own parent (Magento 2.4.8 only).
 *
 * 2.4.8 added a fallback to Sales\Model\ResourceModel\Order\Payment\Transaction::_beforeSave(): a
 * transaction saved with no parent_txn_id has its parent_id set from the OLDEST transaction row of the
 * order, `ORDER BY transaction_id ASC LIMIT 1`. An order's authorization has no parent_txn_id and IS that
 * oldest row, so every save after its insert sets parent_id to its own transaction_id.
 *
 * Transaction::close() then recurses on itself, because 2.4.8 closes the parent of an authorization
 * without checking that the parent is a different transaction:
 *
 *     if ($this->_transactionsAutoLinking && self::TYPE_AUTH === $this->getTxnType()) {
 *         $paymentTransaction = $this->getParentTransaction();
 *         if ($paymentTransaction) {
 *             $paymentTransaction->close($shouldSave);
 *         }
 *     }
 *
 * getParentTransaction() loads a FRESH model each level rather than returning $this, so the recursion
 * allocates a new transaction model and issues another write per level and never terminates. On an
 * unbounded php-cli memory_limit that means the kernel OOM killer: ~14 GB in a few minutes, no PHP error.
 * Any gateway that authorizes online and later captures, voids or refunds reaches it.
 *
 * 2.4.9 fixed this twice over — the _beforeSave() fallback is gone, and close()/_loadChildren() reject a
 * parent or child that is the transaction itself. 2.4.7 and earlier never wrote the self-reference. This
 * restores the 2.4.9 read behavior one level lower, at the parent lookup itself, so it also covers
 * closeAuthorization() and anything else that walks the link.
 *
 * Deliberately NOT version-gated: the guard can only fire on data 2.4.8 wrote, so it is inert on every
 * other release and stops firing on its own once such a store is patched or upgraded. It leaves the stored
 * parent_id alone — same as 2.4.9, which tolerates the rows rather than rewriting them.
 *
 * @see https://github.com/magento/magento2/issues/40165 the sibling defect from the same 2.4.8 commit
 */
class SelfParentGuard
{
    /**
     * Report no parent transaction when the parent resolved to the transaction itself.
     *
     * @param Transaction $subject
     * @param Transaction|false $result
     * @return Transaction|false
     */
    public function afterGetParentTransaction(
        Transaction $subject,
        $result
    ) {
        if ($result instanceof Transaction
            && $result->getId() !== null
            && (int)$result->getId() === (int)$subject->getId()) {
            return false;
        }

        return $result;
    }
}
