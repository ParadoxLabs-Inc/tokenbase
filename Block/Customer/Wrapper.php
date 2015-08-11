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

namespace ParadoxLabs\TokenBase\Block\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

/**
 * Wrapper Class
 */
class Wrapper extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->registry = $registry;

        parent::__construct($context, $data);
    }

    /**
     * Get an array of active TokenBase methods.
     *
     * @return array
     */
    public function getActiveMethods()
    {
        return $this->helper->getActiveMethods();
    }

    /**
     * Get the currently active method.
     *
     * @return string
     */
    public function getCurrentMethod()
    {
        return $this->registry->registry('tokenbase_method');
    }

    /**
     * Get the payment method title from settings.
     *
     * @param string $method
     * @return string
     */
    public function getMethodTitle($method)
    {
        return $this->_scopeConfig->getValue(
            'payment/' . $method . '/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
