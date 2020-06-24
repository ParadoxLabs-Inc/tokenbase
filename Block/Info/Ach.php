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

        if ($this->getIsSecureMode() === false && $this->isEcheck() === true) {
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

    /**
     * Before rendering html, but after trying to load cache
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        if (!empty($this->getRequest()->getParam('payment'))) {
            // On multishipping checkout, persist ACH payment values (if any) onto the review step for final submit.
            // We have to do this because we don't want to save them to the DB or session, but can't lose them entirely.
            /** @var \Magento\Framework\View\Element\Template $childBlock */
            $childBlock = $this->getLayout()->createBlock(
                \Magento\Framework\View\Element\Template::class,
                $this->getNameInLayout() . '.multiship'
            );
            $childBlock->setTemplate('ParadoxLabs_TokenBase::info/ach.phtml');
            $childBlock->setData('method', $this->getMethod());

            $this->setChild(
                $childBlock->getNameInLayout(),
                $childBlock
            );
        }

        return parent::_beforeToHtml();
    }
}
