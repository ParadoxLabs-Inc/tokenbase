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

namespace ParadoxLabs\TokenBase\Block\Info;

/**
 * ACH info block for TokenBase methods.
 */
class Ach extends Cc
{
    /**
     * @var bool
     */
    protected $isEcheck = true;

    /**
     * Prepare payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport  = parent::_prepareSpecificInformation($transport);
        $data       = [];

        if ($this->getIsSecureMode() === false && $this->isEcheck() === true) {
            /** @var \Magento\Sales\Model\Order\Payment\Info $info */
            $info = $this->getInfo();

            $accName    = $info->getData('echeck_account_name');

            if (!empty($accName)) {
                $data[(string)__('Name on Account')] = $accName;
            }
        }

        $transport->setData(array_merge($transport->getData(), $data));

        return $transport;
    }

    /**
     * Before rendering html, but after trying to load cache
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        if (!empty($this->getRequest()->getParam('payment'))) {
            // On multishipping checkout, persist ACH payment values (if any) onto the review step for final submit.
            // We have to do this because we don't want to save them to the DB or session, but can't lose them entirely.
            /** @var \Magento\Framework\View\Element\Template $childBlock */
            $childBlock = $this->getLayout()->createBlock(
                \Magento\Framework\View\Element\Template::class,
                $this->getNameInLayout() . '.multiship'
            );
            $childBlock->setTemplate('ParadoxLabs_TokenBase::info/ach.phtml');
            $childBlock->setData('method', $this->getMethod());

            $this->setChild(
                $childBlock->getNameInLayout(),
                $childBlock
            );
        }

        return parent::_beforeToHtml();
    }
}
