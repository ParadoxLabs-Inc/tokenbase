<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Observer;

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

        // Magento 2.0 compatibility
        if ($payment === null) {
            $payment = $method->getInfoInstance();
        }

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
            $payment->setData('echeck_routing_number', substr($data->getData('echeck_routing_no'), -4));
        }

        if ($data->getData('echeck_account_no') != '') {
            $last4 = substr($data->getData('echeck_account_no'), -4);

            $payment->setData('cc_last_4', $last4);
            $payment->setAdditionalInformation('echeck_account_number_last4', $last4);
        }
    }
}
