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

use Magento\Backend\Block\Template\Context;
use Magento\Payment\Model\MethodInterface;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Framework\Phrase;
use Magento\Backend\Block\Template;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Registry;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Card;
use Throwable;

class Cards extends Template
{
    /**
     * @var MethodInterface
     */
    protected $method;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Registry $registry
     * @param Mapper $addressMapper
     * @param Config $addressConfig
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly Registry $registry,
        protected readonly Mapper $addressMapper,
        protected readonly Config $addressConfig,
        protected readonly Data $helper,
        array $data = []
    ) {
        $this->method = $this->helper->getMethodInstance($this->registry->registry('tokenbase_method'));

        parent::__construct($context, $data);
    }

    /**
     * Get stored cards for the currently-active method.
     *
     * @return array|Collection
     */
    public function getCards()
    {
        return $this->helper->getActiveCustomerCardsByMethod($this->method->getCode());
    }

    /**
     * Get currently-active card (if any)
     *
     * @return CardInterface|null
     */
    public function getCurrentCard()
    {
        return $this->registry->registry('active_card');
    }

    /**
     * Get the active payment method title.
     *
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        return $this->method->getConfigData('title');
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param AddressInterface $address
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedCardAddress(AddressInterface $address)
    {
        try {
            /** @var RendererInterface $renderer */
            $renderer    = $this->addressConfig->getFormatByCode('html')->getRenderer();
            $addressData = $this->addressMapper->toFlatArray($address);

            return $renderer->renderArray($addressData);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Get CC type label (if applicable).
     *
     * @param Card $card
     * @return Phrase|null
     */
    public function getCcTypeLabel(Card $card)
    {
        return $card->getType() ? $this->helper->translateCardType($card->getType()) : null;
    }
}
