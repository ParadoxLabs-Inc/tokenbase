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

namespace ParadoxLabs\TokenBase\Plugin;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Store\Model\StoreManagerInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;

class ValidateCardSave
{
    /**
     * ValidatePaymentObserver constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Payment\Gateway\Validator\ValidatorPoolInterface|null $validatorPool
     */
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ?ValidatorPoolInterface $validatorPool = null
    ) {
    }

    /**
     * Validate payment data before gateway sync
     *
     * Note: Implemented in plugin because tokenbase_card_save_before helpfully runs _after_ gateway sync. This hooks
     * in reliably before gateway syncing occurs, without major refactoring or BC breaks.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $subject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeBeforeSave(
        CardInterface $subject
    ) {
        if ($subject instanceof DataObject === false
            || $subject->hasData('info_instance') === false
            || $subject->getData('no_sync') === true
            || $subject->getData('no_validate') === true) {
            return;
        }

        try {
            // Get payment method validation by method code (intended for tokenbase, but will work for any that use it).
            $validator = $this->validatorPool->get($subject->getMethod());
            $result    = $validator->validate([
                'payment' => $subject->getData('info_instance'),
                'storeId' => $this->storeManager->getStore()->getId(),
            ]);

            if (!$result->isValid()) {
                throw new CommandException(
                    __(implode("\n", $result->getFailsDescription()))
                );
            }
        } catch (NotFoundException) {
            // No validator pool for this payment method -- nothing to validate
            return;
        }
    }
}
