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

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Block\Address\Edit;
use Magento\Customer\Block\Widget\Name;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Data\Form\FormKey;
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
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $card;

    /**
     * @var \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Payment\Block\Form\Cc
     */
    protected $ccBlock;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $tokenbaseMethodFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        EncoderInterface $jsonEncoder,
        Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        CollectionFactory $countryCollectionFactory,
        Session $customerSession,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        CurrentCustomer $currentCustomer,
        DataObjectHelper $dataObjectHelper,
        protected Registry $registry,
        protected FormKey $formKey,
        protected Data $helper,
        protected CardFactory $cardFactory,
        protected Factory $tokenbaseMethodFactory,
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
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface
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
     * @return \ParadoxLabs\TokenBase\Model\Card
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
     * @return \Magento\Customer\Api\Data\AddressInterface
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
        /** @var \Magento\Customer\Block\Widget\Name $nameBlock */
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
        return $this->getUrl(
            '*/*/paymentinfoSave',
            [
                '_secure' => true,
                'id' => $this->getRequest()->getParam('id'),
            ]
        );
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
     * @return \Magento\Payment\Block\Form\Cc
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
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        return $this->helper->getCurrentCustomer();
    }

    /**
     * Return the Url to go back.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl(
            '*/*/paymentinfo',
            [
                '_secure' => true,
                'id' => $this->getRequest()->getParam('id'),
                'method' => $this->getCard()->getMethod(),
                'form_key' => $this->formKey->getFormKey(),
                'cancel' => 1,
            ]
        );
    }
}
