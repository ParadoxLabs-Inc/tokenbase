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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Customer;

/**
 * Wrapper Class
 */
class Wrapper extends \Magento\Backend\Block\Template
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
     * Wrapper constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
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
