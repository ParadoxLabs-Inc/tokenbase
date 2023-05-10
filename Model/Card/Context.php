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

namespace ParadoxLabs\TokenBase\Model\Card;

/**
 * Context Class -- this reduces the DI argument list for Card itself.
 */
class Context
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    private $helper;

    /**
     * @var Factory
     */
    private $cardFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    private $cardCollectionFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    private $addressRegionFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $dateProcessor;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    private $methodFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory
     */
    private $cardAdditionalFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Context constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory
     * @param \ParadoxLabs\TokenBase\Model\Card\Factory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory $cardAdditionalFactory
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $addressRegionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory,
        \ParadoxLabs\TokenBase\Model\Card\Factory $cardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory $cardAdditionalFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $addressRegionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    ) {
        $this->helper = $helper;
        $this->methodFactory = $methodFactory;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->addressRegionFactory = $addressRegionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->remoteAddress = $remoteAddress;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->dateProcessor = $dateProcessor;
        $this->customerRepository = $customerRepository;
        $this->cardAdditionalFactory = $cardAdditionalFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Get helper
     *
     * @return \ParadoxLabs\TokenBase\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get methodFactory
     *
     * @return \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    public function getMethodFactory()
    {
        return $this->methodFactory;
    }

    /**
     * Get cardFactory
     *
     * @return Factory
     */
    public function getCardFactory()
    {
        return $this->cardFactory;
    }

    /**
     * Get cardCollectionFactory
     *
     * @return \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    public function getCardCollectionFactory()
    {
        return $this->cardCollectionFactory;
    }

    /**
     * Get customerFactory
     *
     * @return \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    public function getCustomerFactory()
    {
        return $this->customerFactory;
    }

    /**
     * Get addressFactory
     *
     * @return \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    public function getAddressFactory()
    {
        return $this->addressFactory;
    }

    /**
     * Get addressRegionFactory
     *
     * @return \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    public function getAddressRegionFactory()
    {
        return $this->addressRegionFactory;
    }

    /**
     * Get orderCollectionFactory
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public function getOrderCollectionFactory()
    {
        return $this->orderCollectionFactory;
    }

    /**
     * Get checkoutSession
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Get remoteAddress
     *
     * @return \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    public function getRemoteAddress()
    {
        return $this->remoteAddress;
    }

    /**
     * Get dataObjectProcessor
     *
     * @return \Magento\Framework\Reflection\DataObjectProcessor
     */
    public function getDataObjectProcessor()
    {
        return $this->dataObjectProcessor;
    }

    /**
     * Get dateProcessor
     *
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public function getDateProcessor()
    {
        return $this->dateProcessor;
    }

    /**
     * Get customerRepository
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
    public function getCustomerRepository()
    {
        return $this->customerRepository;
    }

    /**
     * Get cardAdditionalFactory
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory
     */
    public function getCardAdditionalFactory()
    {
        return $this->cardAdditionalFactory;
    }

    /**
     * Get dataObjectHelper
     *
     * @return \Magento\Framework\Api\DataObjectHelper
     */
    public function getDataObjectHelper()
    {
        return $this->dataObjectHelper;
    }
}
