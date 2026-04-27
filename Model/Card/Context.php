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

namespace ParadoxLabs\TokenBase\Model\Card;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Card\Factory as CardFactory;
use ParadoxLabs\TokenBase\Model\Method\Factory as MethodFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory as CardCollectionFactory;

/**
 * Context Class -- this reduces the DI argument list for Card itself.
 */
class Context
{
    /**
     * Context constructor.
     *
     * @param Data $helper
     * @param MethodFactory $methodFactory
     * @param CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterfaceFactory $cardAdditionalFactory
     * @param CustomerInterfaceFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $addressRegionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param Session $checkoutSession *Proxy
     * @param RemoteAddress $remoteAddress
     * @param DataObjectProcessor $dataObjectProcessor
     * @param TimezoneInterface $dateProcessor
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        private readonly Data $helper,
        private readonly MethodFactory $methodFactory,
        private readonly CardFactory $cardFactory,
        private readonly CardCollectionFactory $cardCollectionFactory,
        private readonly CardAdditionalInterfaceFactory $cardAdditionalFactory,
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly RegionInterfaceFactory $addressRegionFactory,
        private readonly CollectionFactory $orderCollectionFactory,
        private readonly Session $checkoutSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly DataObjectProcessor $dataObjectProcessor,
        private readonly TimezoneInterface $dateProcessor,
        private readonly DataObjectHelper $dataObjectHelper
    ) {
    }

    /**
     * Get helper
     *
     * @return Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get methodFactory
     *
     * @return MethodFactory
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
     * @return CustomerInterfaceFactory
     */
    public function getCustomerFactory()
    {
        return $this->customerFactory;
    }

    /**
     * Get addressFactory
     *
     * @return AddressInterfaceFactory
     */
    public function getAddressFactory()
    {
        return $this->addressFactory;
    }

    /**
     * Get addressRegionFactory
     *
     * @return RegionInterfaceFactory
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
     * @return Session
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Get remoteAddress
     *
     * @return RemoteAddress
     */
    public function getRemoteAddress()
    {
        return $this->remoteAddress;
    }

    /**
     * Get dataObjectProcessor
     *
     * @return DataObjectProcessor
     */
    public function getDataObjectProcessor()
    {
        return $this->dataObjectProcessor;
    }

    /**
     * Get dateProcessor
     *
     * @return TimezoneInterface
     */
    public function getDateProcessor()
    {
        return $this->dateProcessor;
    }

    /**
     * Get customerRepository
     *
     * @return CustomerRepositoryInterface
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
     * @return DataObjectHelper
     */
    public function getDataObjectHelper()
    {
        return $this->dataObjectHelper;
    }
}
