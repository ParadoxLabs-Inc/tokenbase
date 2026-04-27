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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Helper\Data;

class Wrapper extends Template
{
    /**
     * Wrapper constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly Registry $registry,
        protected readonly Data $helper,
        array $data = []
    ) {
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
            ScopeInterface::SCOPE_STORE
        );
    }
}
