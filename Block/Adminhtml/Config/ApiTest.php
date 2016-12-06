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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Config;

/**
 * ApiTest Class
 */
abstract class ApiTest extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeFactory = $storeFactory;
        $this->websiteFactory = $websiteFactory;

        parent::__construct($context, $data);
    }

    /**
     * Get store ID from current request scope.
     *
     * @return int
     */
    protected function getStoreId()
    {
        if ($this->storeId === null) {
            if ($this->_request->getParam('store') != '') {
                /** @var \Magento\Store\Model\Store $store */
                $store = $this->storeFactory->create();
                $store->load($this->_request->getParam('store'));

                $this->storeId = (int)$store->getId();
            } elseif ($this->_request->getParam('website') != '') {
                /** @var \Magento\Store\Model\Website $website */
                $website = $this->websiteFactory->create();
                $website->load($this->_request->getParam('website'));

                $this->storeId = $website->getDefaultStore()->getId();
            } else {
                $this->storeId = 0;
            }
        }

        return $this->storeId;
    }

    /**
     * Before rendering html, but after trying to load cache
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = (string)$this->testApi();

        if (strpos($html, 'success') !== false) {
            $html = '<strong style="color:#0a0;">' . $html . '</strong>';
        } else {
            $html = '<strong style="color:#D40707;">' . $html . '</strong>';
        }

        return $html;
    }

    /**
     * Method to test the API connection. Should return a string indicating success or error.
     *
     * @return mixed
     */
    abstract protected function testApi();
}
