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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Config;

abstract class ApiTest extends \Magento\Framework\View\Element\Text
{
    /**
     * @var \ParadoxLabs\TokenBAse\Helper\Data
     */
    protected $helper;

    /**
     * @var int
     */
    protected $storeId = 0;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
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
        if (is_null($this->storeId)) {
            if ($this->_request->getParam('store') != '') {
                $store = $this->storeFactory->create();
                $store->load($this->_request->getParam('store'));

                $this->storeId = (int)$store->getId();
            } elseif ($this->_request->getParam('website') != '') {
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
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $html = (string)$this->testApi();

        if (strpos($html, 'success') !== false) {
            $html = '<strong style="color:#0a0;">' . $html . '</strong>';
        } else {
            $html = '<strong class="error">' . $html . '</strong>';
        }

        $this->setText($html);

        return parent::_beforeToHtml();
    }

    abstract protected function testApi();
}
