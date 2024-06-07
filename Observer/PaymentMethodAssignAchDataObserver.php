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

use Magento\Quote\Api\Data\PaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;

/**
 * PaymentMethodAssignAchDataObserver Class
 */
class PaymentMethodAssignAchDataObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var array
     */
    protected $achFields = [
        'echeck_account_name',
        'echeck_bank_name',
        'echeck_routing_no',
        'echeck_account_no',
        'echeck_account_type',
    ];

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(\ParadoxLabs\TokenBase\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Assign data to the payment instance for our methods.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\MethodInterface $method */
        $method = $observer->getData('method');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getData('payment_model');

        /** @var \Magento\Framework\DataObject $data */
        $data = $observer->getData('data');

        /**
         * Merge together data from additional_data array
         */
        if ($data->hasData('additional_data')) {
            foreach ($data->getData('additional_data') as $key => $value) {
                if ($data->getData($key) == false) {
                    $data->setData($key, $value);
                }
            }
        }

        foreach ($this->achFields as $field) {
            if ($data->hasData($field) && $data->getData($field) != '') {
                $payment->setData($field, $data->getData($field));

                if ($field !== 'echeck_routing_no' && $field !== 'echeck_account_no') {
                    $payment->setAdditionalInformation($field, $data->getData($field));
                }
            }
        }

        if ($data->getData('echeck_routing_no') != '') {
            $payment->setData('echeck_routing_number', substr((string)$data->getData('echeck_routing_no'), -4));
        }

        if ($data->getData('echeck_account_no') != '') {
            $last4 = substr((string)$data->getData('echeck_account_no'), -4);

            $payment->setData('cc_last_4', $last4);
            $payment->setAdditionalInformation('echeck_account_number_last4', $last4);

            if (empty($data->getData('card_id'))) {
                $payment->setData('tokenbase_id', null);

                $paymentAttributes = $payment->getExtensionAttributes();
                if ($paymentAttributes instanceof PaymentExtensionInterface
                    || $paymentAttributes instanceof OrderPaymentExtensionInterface) {
                    $paymentAttributes->setTokenbaseId(null);
                }
            }
        }
    }
}
