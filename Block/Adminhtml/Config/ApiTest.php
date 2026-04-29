<?php declare(strict_types=1);
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
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Config;

use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use Override;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Method\Factory;

/**
 * ApiTest Class
 */
abstract class ApiTest extends Field
{
    /**
     * @var int
     */
    protected $storeId;

    /**
     * @param Context $context
     * @param Data $helper
     * @param StoreFactory $storeFactory
     * @param WebsiteFactory $websiteFactory
     * @param Factory $methodFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly Data $helper,
        protected readonly StoreFactory $storeFactory,
        protected readonly WebsiteFactory $websiteFactory,
        protected readonly Factory $methodFactory,
        array $data = []
    ) {
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
                /** @var Store $store */
                $store = $this->storeFactory->create();
                $store->load($this->_request->getParam('store'));

                $this->storeId = (int)$store->getId();
            } elseif ($this->_request->getParam('website') != '') {
                /** @var Website $website */
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
     * @param AbstractElement $element
     * @return string
     */
    #[Override]
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = (string)$this->testApi();

        if (str_contains($html, 'success')) {
            $html = '<strong style="color:#0a0;">' . $html . '</strong>';
        } else {
            $html = '<strong style="color:#D40707;">' . $html . '</strong>';
        }

        return $html;
    }

    /**
     * Determine whether the given string contains values outside the standard ASCII charset.
     *
     * @param string $string
     * @return bool
     */
    protected function containsInvalidCharacters($string)
    {
        return (bool)preg_match('/[^ -~]/i', (string)$string);
    }

    /**
     * Method to test the API connection. Should return a string indicating success or error.
     *
     * @return mixed
     */
    abstract protected function testApi();
}
