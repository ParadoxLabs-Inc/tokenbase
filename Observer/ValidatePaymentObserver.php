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

namespace ParadoxLabs\TokenBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

class ValidatePaymentObserver implements ObserverInterface
{
    /**
     * ValidatePaymentObserver constructor.
     *
     * @param \Magento\Payment\Gateway\Validator\ValidatorPoolInterface|null $validatorPool
     */
    public function __construct(
        protected ?ValidatorPoolInterface $validatorPool = null
    ) {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $order = $observer->getData('order');
        $quote = $observer->getData('quote');

        if ($order instanceof OrderInterface) {
            $model = $order;
        } elseif ($quote instanceof CartInterface) {
            $model = $quote;
        }

        if ($model->getPayment() instanceof InfoInterface === false) {
            return;
        }

        try {
            // Get payment method validation by method code (intended for tokenbase, but will work for any that use it).
            $validator = $this->validatorPool->get($model->getPayment()->getMethod());
            $result    = $validator->validate([
                'payment' => $model->getPayment(),
                'storeId' => $model->getStoreId(),
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
