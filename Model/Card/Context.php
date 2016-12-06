<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
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
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

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
     * @var \Magento\Checkout\Model\Session\Proxy
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
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    private $unserialize;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Context constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param Factory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $addressRegionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Payment\Helper\Data $paymentHelper,
        \ParadoxLabs\TokenBase\Model\Card\Factory $cardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $addressRegionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor,
        \Magento\Framework\Unserialize\Unserialize $unserialize
    ) {
        $this->helper = $helper;
        $this->paymentHelper = $paymentHelper;
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
        $this->unserialize = $unserialize;
        $this->customerRepository = $customerRepository;
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
     * Get paymentHelper
     *
     * @return \Magento\Payment\Helper\Data
     */
    public function getPaymentHelper()
    {
        return $this->paymentHelper;
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
     * @return \Magento\Checkout\Model\Session\Proxy
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
     * Get unserialize
     *
     * @return \Magento\Framework\Unserialize\Unserialize
     */
    public function getUnserialize()
    {
        return $this->unserialize;
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
}
