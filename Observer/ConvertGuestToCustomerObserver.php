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

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;

class ConvertGuestToCustomerObserver implements ObserverInterface
{
    /**
     * ConvertGuestToCustomerObserver constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Sales\Model\Order\PaymentFactory $paymentFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment $paymentResource
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected CardRepositoryInterface $cardRepository,
        protected PaymentFactory $paymentFactory,
        protected Payment $paymentResource,
        protected RemoteAddress $remoteAddress,
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getData('customer_data_object');
        /** @var array $delegateData */
        $delegateData = $observer->getData('delegate_data');

        if (isset($delegateData['__sales_assign_order_id'])
            && $customer instanceof CustomerInterface) {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $this->paymentFactory->create();
            $this->paymentResource->load($payment, $delegateData['__sales_assign_order_id'], 'parent_id');
            try {
                // The given card must match the order, which must match the customer.
                $card = $this->cardRepository->load($payment->getData('tokenbase_id'));
                $card->setCustomerId($customer->getId());

                // Activate the card by default if config is opt-out.
                $activate = (int)$this->scopeConfig->getValue(
                    'payment/' . $card->getMethod() . '/savecard_opt_out',
                    ScopeInterface::SCOPE_STORE
                );
                if ($activate === 1) {
                    $card->setActive(1);
                }

                $this->cardRepository->save($card);
            } catch (LocalizedException) {
                // No-op: gracefully skip a card save if it fails.
            }
        }
    }
}
