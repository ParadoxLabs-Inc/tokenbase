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

namespace ParadoxLabs\TokenBase\Block\Customer;

/**
 * Add 'payment data' link to the customer account.
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * Get href URL - force secure
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl($this->getPath(), ['_secure' => true]);
    }

    /**
     * Render block HTML - if and only if we have active tokenbase payment methods.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $activeMethods = $this->helper->getActiveMethods();

        if (!empty($activeMethods)) {
            return parent::_toHtml();
        }

        return '';
    }
}
