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

namespace ParadoxLabs\TokenBase\Block\Customer;

use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * Add 'payment data' link to the customer account.
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
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

    /**
     * Get sort order for block.
     *
     * @return int
     * @since 101.0.0
     */
    public function getSortOrder()
    {
        return $this->getData(SortLinkInterface::SORT_ORDER);
    }
}
