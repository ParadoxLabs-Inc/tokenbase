<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Block\Info;

/**
 * Credit card info block for TokenBase methods.
 */
class Cc extends \Magento\Payment\Block\Info\Cc
{
    /**
     * @var bool
     */
    protected $isEcheck = false;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = \Magento\Payment\Block\Info::_prepareSpecificInformation($transport);
        $data = [];

        /** @var \Magento\Sales\Model\Order\Payment $info */
        $info = $this->getInfo();

        $this->_eventManager->dispatch('tokenbase_before_load_payment_info', [
            'method'    => $info->getMethod(),
            'customer'  => $this->helper->getCurrentCustomer(),
            'transport' => $transport,
            'info'      => $info,
        ]);

        // If this is an eCheck, show different info.
        if ($this->isEcheck() === true) {
            if ($info->getData('echeck_bank_name') != '') {
                $data[(string)__('Bank Name')] = $info->getData('echeck_bank_name');
            } elseif ($info->getAdditionalInformation('echeck_bank_name') != '') {
                $data[(string)__('Bank Name')] = $info->getAdditionalInformation('echeck_bank_name');
            }

            $data[(string)__('Account Number')] = sprintf(
                'x-%s',
                $info->getAdditionalInformation('echeck_account_number_last4')
            );
        } else {
            $ccType = $this->getCcTypeName();
            if (!empty($ccType) && $ccType != 'N/A') {
                $data[(string)__('Credit Card Type')] = $ccType;
            }

            if ($info->getCcLast4()) {
                $data[(string)__('Credit Card Number')] = sprintf('XXXX-%s', $info->getCcLast4());
            }
        }

        // If this is admin, show different info.
        if ($this->helper->getIsFrontend() !== true) {
            $data[(string)__('Transaction ID')] = $info->getAdditionalInformation('transaction_id');
        }

        $transport->setData(array_merge($data, $transport->getData()));

        $this->_eventManager->dispatch('tokenbase_after_load_payment_info', [
            'method'    => $info->getMethod(),
            'customer'  => $this->helper->getCurrentCustomer(),
            'transport' => $transport,
            'info'      => $info,
        ]);

        return $transport;
    }

    /**
     * Return whether the current method/transaction is echeck
     *
     * @return bool
     */
    protected function isEcheck()
    {
        return $this->isEcheck;
    }
}
