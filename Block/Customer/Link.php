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

namespace ParadoxLabs\TokenBase\Block\Customer;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;
use Override;
use ParadoxLabs\TokenBase\Helper\Data;

/**
 * Add 'payment data' link to the customer account.
 */
class Link extends Current implements SortLinkInterface
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        protected readonly Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * Get href URL - force secure
     *
     * @return string
     */
    #[Override]
    public function getHref()
    {
        return $this->getUrl($this->getPath(), ['_secure' => true]);
    }

    /**
     * Render block HTML - if and only if we have active tokenbase payment methods.
     *
     * @return string
     */
    #[Override]
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
