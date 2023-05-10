<?php
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
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * ConvertGuestToCustomerObserver Class
 */
class ConvertGuestToCustomerObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Sales\Model\Order\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment
     */
    protected $paymentResource;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

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
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment $paymentResource,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->cardRepository = $cardRepository;
        $this->paymentFactory = $paymentFactory;
        $this->paymentResource = $paymentResource;
        $this->remoteAddress = $remoteAddress;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getData('customer_data_object');
        /** @var array $delegateData */
        $delegateData = $observer->getData('delegate_data');

        if (isset($delegateData['__sales_assign_order_id'])
            && $customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
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
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                if ($activate === 1) {
                    $card->setActive(1);
                }

                $this->cardRepository->save($card);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // No-op: gracefully skip a card save if it fails.
            }
        }
    }
}
