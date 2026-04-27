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

use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Api\MethodInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Block\Address\Edit;
use Magento\Customer\Block\Widget\Name;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\CardFactory;
use ParadoxLabs\TokenBase\Model\Method\Factory;
use Throwable;

class Form extends Edit
{
    /**
     * @var Card
     */
    protected $card;

    /**
     * @var MethodInterface
     */
    protected $method;

    /**
     * @var Cc
     */
    protected $ccBlock;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param EncoderInterface $jsonEncoder
     * @param Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param Session $customerSession *Proxy
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CurrentCustomer $currentCustomer *Proxy
     * @param DataObjectHelper $dataObjectHelper
     * @param Registry $registry
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param Factory $tokenbaseMethodFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        DirectoryHelper $directoryHelper,
        EncoderInterface $jsonEncoder,
        Config $configCacheType,
        RegionCollectionFactory $regionCollectionFactory,
        CollectionFactory $countryCollectionFactory,
        Session $customerSession,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        CurrentCustomer $currentCustomer,
        DataObjectHelper $dataObjectHelper,
        protected readonly Registry $registry,
        protected readonly Data $helper,
        protected readonly CardFactory $cardFactory,
        protected readonly Factory $tokenbaseMethodFactory,
        array $data = []
    ) {
        $this->method = $this->tokenbaseMethodFactory->getMethodInstance($this->getCode());

        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );
    }

    /**
     * Get the active payment method code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->registry->registry('tokenbase_method');
    }

    /**
     * Get the active payment method.
     *
     * @return MethodInterface
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the TokenBase helper.
     *
     * @return \ParadoxLabs\TokenBase\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Return active card model (or an empty card)
     *
     * @return Card
     */
    public function getCard()
    {
        if ($this->card === null) {
            try {
                $this->card = $this->helper->getActiveCard($this->getCode());
            } catch (Throwable) {
                $this->card = $this->cardFactory->create();
            }
        }

        return $this->card;
    }

    /**
     * Return the associated address.
     *
     * @return AddressInterface
     */
    public function getAddress()
    {
        return $this->getCard()->getAddressObject();
    }

    /**
     * Return the specified numbered street line.
     *
     * @param int $lineNumber
     * @return string
     */
    public function getStreetLine($lineNumber)
    {
        $street = $this->getAddress()->getStreet();

        return $street[ $lineNumber - 1 ] ?? '';
    }

    /**
     * Generate name block html.
     *
     * @return string
     */
    public function getNameBlockHtml()
    {
        /** @var Name $nameBlock */
        $nameBlock = $this->getLayout()
                          ->createBlock(Name::class);

        $nameBlock->setObject($this->getAddress());
        $nameBlock->setData('field_name_format', 'billing[%s]');

        return $nameBlock->toHtml();
    }

    /**
     * Return the form submit action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getUrl('*/*/save', ['_secure' => true]);
    }

    /**
     * Return the Url to go back.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/index', ['_secure' => true, 'method' => $this->getCode()]);
    }

    /**
     * Return whether or not this is a card edit.
     *
     * @return bool
     */
    public function isEdit()
    {
        return $this->getCard()->getId() > 0;
    }

    /**
     * @return Cc
     */
    public function getCcBlock()
    {
        if ($this->ccBlock === null) {
            $this->ccBlock = $this->getLayout()->createBlock(Cc::class);
            $this->ccBlock->setMethod($this->helper->getMethodInstance($this->getCode()));
        }

        return $this->ccBlock;
    }

    /**
     * Retrieve the Customer Data using the customer Id from the customer session.
     *
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->helper->getCurrentCustomer();
    }
}
