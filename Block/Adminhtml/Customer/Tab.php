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

use Magento\Framework\Phrase;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;
use ParadoxLabs\TokenBase\Helper\Data;

class Tab extends TabWrapper
{
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        /**
         * Core registry
         */
        protected readonly Registry $coreRegistry,
        protected readonly Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    #[\Override]
    public function canShowTab()
    {
        $activeMethods = $this->helper->getActiveMethods();

        if (empty($activeMethods)) {
            return false;
        }

        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Return Tab label
     *
     * @return Phrase
     */
    #[\Override]
    public function getTabLabel()
    {
        return __('Payment Options');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    #[\Override]
    public function getTabUrl()
    {
        return $this->getUrl('customer/*/paymentinfo', ['_current' => true]);
    }
}
