<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Block\Info;

/**
 * ACH info block for TokenBase methods.
 */
class Ach extends Cc
{
    /**
     * @var bool
     */
    protected $isEcheck = true;

    /**
     * Prepare payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport  = parent::_prepareSpecificInformation($transport);
        $data       = [];

        if ($this->helper->getIsFrontend() === false && $this->isEcheck() === true) {
            /** @var \Magento\Sales\Model\Order\Payment\Info $info */
            $info = $this->getInfo();

            $accName    = $info->getData('echeck_account_name');

            if (!empty($accName)) {
                $data[(string)__('Name on Account')] = $accName;
            }
        }

        $transport->setData(array_merge($transport->getData(), $data));

        return $transport;
    }
}
