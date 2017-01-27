<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <support@paradoxlabs.com>
 * @license        http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model;

/**
 * Payment record storage
 */
class CardImp extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \ParadoxLabs\TokenBase\Api\Data\CardInterface
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'tokenbase_card';

    /**
     * @var null|array
     */
    protected $address;

    /**
     * @var null|array
     */
    protected $additional;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    protected $methodFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\AbstractMethod
     */
    protected $method;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $instance;

    /**
     * @var Card\Factory
     */
    protected $cardFactory;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    protected $addressRegionFactory;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateProcessor;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    protected $unserialize;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param Card\Context $cardContext
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \ParadoxLabs\TokenBase\Model\Card\Context $cardContext,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->helper                   = $cardContext->getHelper();
        $this->methodFactory            = $cardContext->getMethodFactory();
        $this->cardFactory              = $cardContext->getCardFactory();
        $this->customerFactory          = $cardContext->getCustomerFactory();
        $this->customerRepository       = $cardContext->getCustomerRepository();
        $this->addressFactory           = $cardContext->getAddressFactory();
        $this->addressRegionFactory     = $cardContext->getAddressRegionFactory();
        $this->cardCollectionFactory    = $cardContext->getCardCollectionFactory();
        $this->orderCollectionFactory   = $cardContext->getOrderCollectionFactory();
        $this->checkoutSession          = $cardContext->getCheckoutSession();
        $this->remoteAddress            = $cardContext->getRemoteAddress();
        $this->dataProcessor            = $cardContext->getDataObjectProcessor();
        $this->dateProcessor            = $cardContext->getDateProcessor();
        $this->unserialize              = $cardContext->getUnserialize();
    }

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ParadoxLabs\TokenBase\Model\ResourceModel\Card');
    }

    /**
     * Set the method instance for this card. This is often necessary to route card data properly.
     *
     * @param \ParadoxLabs\TokenBase\Api\MethodInterface $method
     * @return $this
     */
    public function setMethodInstance(\ParadoxLabs\TokenBase\Api\MethodInterface $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the arbitrary method instance.
     *
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface Gateway-specific payment method
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethodInstance()
    {
        if ($this->method === null) {
            if ($this->hasData('method')) {
                $this->method = $this->methodFactory->getMethodInstance($this->getData('method'));
            } else {
                throw new \UnexpectedValueException('Payment method is unknown for the current card.');
            }
        }

        return $this->method;
    }

    /**
     * Get the specific type implementation for this card.
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface|$this
     */
    public function getTypeInstance()
    {
        if ($this->instance === null) {
            $this->instance = $this->cardFactory->getTypeInstance($this);
        } elseif (get_class($this) === get_class($this->instance)) {
            return $this;
        }

        return $this->instance;
    }

    /**
     * Set the customer account (if any) for the card.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Payment\Model\InfoInterface|null $payment
     * @return $this
     */
    public function setCustomer(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        \Magento\Payment\Model\InfoInterface $payment = null
    ) {
        if ($customer->getEmail() != '') {
            $this->setCustomerEmail($customer->getEmail());
            $this->setCustomerId($customer->getId());

            parent::setData('customer', $customer);
        } elseif ($payment !== null) {
            $model = null;

            /**
             * If we have no email, try to find it from current scope data.
             */

            /** @var \Magento\Sales\Model\Order\Payment $payment */

            if ($payment->getQuote() != null
                && $payment->getQuote()->getBillingAddress() != null
                && $payment->getQuote()->getBillingAddress()->getCustomerEmail() != '') {
                /** @var \Magento\Quote\Model\Quote $model */
                $model = $payment->getQuote();
            } elseif ($payment->getOrder() != null
                && ($payment->getOrder()->getCustomerEmail() != ''
                    || ($payment->getOrder()->getBillingAddress() != null
                        && $payment->getOrder()->getBillingAddress()->getCustomerEmail() != ''))) {
                /** @var \Magento\Sales\Model\Order $model */
                $model = $payment->getOrder();
            } else {
                /**
                 * This will fall back to checkout/session if onepage has no quote loaded.
                 * Should work for all checkouts that use normal Magento processes.
                 */
                /** @var \Magento\Quote\Model\Quote $model */
                $model = $this->checkoutSession->getQuote();
            }

            if ($model !== null) {
                if ($model->getCustomerEmail() == ''
                    && $model->getBillingAddress() instanceof \Magento\Framework\DataObject
                    && $model->getBillingAddress()->getEmail() != '') {
                    $model->setCustomerEmail($model->getBillingAddress()->getEmail());
                }

                if ($model->hasData('email')) {
                    $this->setCustomerEmail($model->getData('email'));
                } elseif ($model->hasData('customer_email')) {
                    $this->setCustomerEmail($model->getData('customer_email'));
                }

                $this->setCustomerId((int)$model->getCustomerId());
            }
        }

        return $this;
    }

    /**
     * Get the customer object (if any) for the card.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        if ($this->hasData('customer')) {
            return parent::getData('customer');
        }

        $customer = $this->customerFactory->create();

        if ($this->getData('customer_id') > 0) {
            $customer = $this->customerRepository->getById($this->getData('customer_id'));
        } else {
            $customer->setEmail($this->getData('customer_email'));
        }

        parent::setData('customer', $customer);

        return $customer;
    }

    /**
     * Set card payment data from a quote or order payment instance.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function importPaymentInfo(\Magento\Payment\Model\InfoInterface $payment)
    {
        if ($payment instanceof \Magento\Payment\Model\InfoInterface) {
            /** @var \Magento\Payment\Model\Info $payment */
            if ($payment->getAdditionalInformation('save') === 0) {
                $this->setData('active', 0);
            } elseif ($payment->getAdditionalInformation('save') === 1) {
                $this->setData('active', 1);
            }

            if ($payment->getData('cc_type') != '') {
                $this->setAdditional('cc_type', $payment->getData('cc_type'));
            }

            if ($payment->getData('cc_last_4') != '') {
                $this->setAdditional('cc_last4', $payment->getData('cc_last_4'));
            } elseif ($payment->getData('cc_last4') != '') {
                $this->setAdditional('cc_last4', $payment->getData('cc_last4'));
            }

            if ($payment->getData('cc_exp_year') > date('Y')
                || ($payment->getData('cc_exp_year') == date('Y') && $payment->getData('cc_exp_month') >= date('n'))) {
                $yr  = $payment->getData('cc_exp_year');
                $mo  = $payment->getData('cc_exp_month');
                $day = date('t', strtotime($payment->getData('cc_exp_year') . '-' . $payment->getData('cc_exp_month')));

                $this->setAdditional('cc_exp_year', $payment->getData('cc_exp_year'))
                    ->setAdditional('cc_exp_month', $payment->getData('cc_exp_month'))
                    ->setData('expires', sprintf('%s-%s-%s 23:59:59', $yr, $mo, $day));
            }

            $this->setData('info_instance', $payment);

            if ($this->getMethodInstance()->hasData('info_instance') !== true) {
                $this->getMethodInstance()->setInfoInstance($payment);
            }
        }

        return $this;
    }

    /**
     * Check whether customer has permission to use/modify this card. Guests, never.
     *
     * @param int $customerId
     * @return bool
     */
    public function hasOwner($customerId)
    {
        $customerId = (int)$customerId;

        if ($customerId < 1) {
            return false;
        }

        return $this->getData('customer_id') == $customerId;
    }

    /**
     * Check if card is connected to any pending orders.
     *
     * @return bool
     */
    public function isInUse()
    {
        $orders    = $this->orderCollectionFactory->create();
        $orders->addAttributeToSelect('*')
               ->addAttributeToFilter('customer_id', $this->getData('customer_id'))
               ->addAttributeToFilter('status', ['like' => 'pending%']);

        if (!empty($orders)) {
            foreach ($orders as $order) {
                /** @var \Magento\Sales\Model\Order $order */
                $payment = $order->getPayment();

                if ($payment->getMethod() == $this->getData('method')
                    && $payment->getData('tokenbase_id') == $this->getId()) {
                    // If we found an order with this card that is not complete, closed, or canceled,
                    // it is still active and the payment ID is important. No editey.
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Change last_use date to the current time.
     *
     * @return $this
     */
    public function updateLastUse()
    {
        $now = $this->dateProcessor->date(null, null, false);
        $this->setData('last_use', $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return $this;
    }

    /**
     * Delete this card, or hide and queue for deletion after the refund period.
     *
     * @return $this
     */
    public function queueDeletion()
    {
        $this->setData('active', 0);

        return $this;
    }

    /**
     * Get billing address or some part thereof.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getAddress($key = '')
    {
        if ($this->address === null && parent::getData('address')) {
            $this->address = $this->unserialize->unserialize(parent::getData('address'));
        }

        if ($key !== '') {
            return (isset($this->address[ $key ]) ? $this->address[ $key ] : null);
        }

        return $this->address;
    }

    /**
     * Return a customer address object containing the card address data.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressObject()
    {
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address    = $this->addressFactory->create();
        /** @var \Magento\Customer\Api\Data\RegionInterface $region */
        $region     = $this->addressRegionFactory->create();

        // ffs.

        $street = $this->getAddress('street');
        if (!is_array($street)) {
            $street = explode("\n", str_replace("\r", '', $street));
        }

        $region->setRegion($this->getAddress('region'));
        $region->setRegionCode($this->getAddress('region_code'));
        $region->setRegionId($this->getAddress('region_id'));

        $address->setId($this->getAddress('id'));
        $address->setCustomerId($this->getAddress('customer_id'));
        $address->setRegion($region);
        $address->setRegionId($this->getAddress('region_id'));
        $address->setCountryId($this->getAddress('country_id'));
        $address->setStreet($street);
        $address->setCompany($this->getAddress('company'));
        $address->setTelephone($this->getAddress('telephone'));
        $address->setFax($this->getAddress('fax'));
        $address->setPostcode($this->getAddress('postcode'));
        $address->setCity($this->getAddress('city'));
        $address->setFirstname($this->getAddress('firstname'));
        $address->setLastname($this->getAddress('lastname'));
        $address->setMiddlename($this->getAddress('middlename'));
        $address->setPrefix($this->getAddress('prefix'));
        $address->setSuffix($this->getAddress('suffix'));
        $address->setVatId($this->getAddress('vat_id'));

        return $address;
    }

    /**
     * Get additional card data.
     * If $key is set, will return that value or null;
     * otherwise, will return an array of all additional date.
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function getAdditional($key = null)
    {
        if ($this->additional === null) {
            $this->additional = $this->unserialize->unserialize(parent::getData('additional'));
        }

        if ($key !== null) {
            return (isset($this->additional[ $key ]) ? $this->additional[ $key ] : null);
        }

        return $this->additional;
    }

    /**
     * Set additional card data.
     * Can pass in a key-value pair to set one value,
     * or a single parameter (associative array) to overwrite all data.
     *
     * @param string|array $key
     * @param string|null $value
     * @return $this
     */
    public function setAdditional($key, $value = null)
    {
        if ($value !== null) {
            if ($this->additional === null) {
                $this->getAdditional();
            }

            $this->additional[ $key ] = $value;
        } elseif (is_array($key)) {
            $this->additional = $key;
        }

        parent::setData('additional', serialize($this->additional));

        return $this;
    }

    /**
     * Set the billing address for the card.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return $this
     */
    public function setAddress(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        // Convert address to array
        $addressData = $this->dataProcessor->buildOutputDataArray(
            $address,
            '\Magento\Customer\Api\Data\AddressInterface'
        );

        $addressData['region_code'] = $address->getRegion()->getRegionCode();
        $addressData['region']      = $address->getRegion()->getRegion();

        // Clean up
        $this->helper->cleanupArray($addressData);

        if (is_array($addressData['street'])) {
            $addressData['street'] = implode("\n", $addressData['street']);
        }

        $this->address = null;

        // Store
        parent::setData('address', serialize($addressData));

        return $this;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getData('customer_email');
    }

    /**
     * Set customer email
     *
     * @param string $email
     * @return $this
     */
    public function setCustomerEmail($email)
    {
        return $this->setData('customer_email', $email);
    }

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Set customer id
     *
     * @param int $id
     * @return $this
     */
    public function setCustomerId($id)
    {
        return $this->setData('customer_id', $id);
    }

    /**
     * Get customer ip
     *
     * @return string
     */
    public function getCustomerIp()
    {
        return $this->getData('customer_ip');
    }

    /**
     * Set customer ip
     *
     * @param string $ip
     * @return $this
     */
    public function setCustomerIp($ip)
    {
        return $this->setData('customer_ip', $ip);
    }

    /**
     * Get profile id
     *
     * @return string
     */
    public function getProfileId()
    {
        return $this->getData('profile_id');
    }

    /**
     * Set profile id
     *
     * @param string $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        return $this->setData('profile_id', $profileId);
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId()
    {
        return $this->getData('payment_id');
    }

    /**
     * Set payment id
     *
     * @param string $paymentId
     * @return $this
     */
    public function setPaymentId($paymentId)
    {
        return $this->setData('payment_id', $paymentId);
    }

    /**
     * Get method code
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getData('method');
    }

    /**
     * Set method code
     *
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        return $this->setData('method', $method);
    }

    /**
     * Get hash, generate if necessary
     *
     * @return string
     */
    public function getHash()
    {
        $hash = $this->getData('hash');

        if (empty($hash)) {
            $hash = sha1(
                'tokenbase'
                . time()
                . $this->getData('customer_id')
                . $this->getData('customer_email')
                . $this->getData('method')
                . $this->getData('profile_id')
                . $this->getData('payment_id')
            );

            $this->setHash($hash);
        }

        return $hash;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return $this
     */
    public function setHash($hash)
    {
        return $this->setData('hash', $hash);
    }

    /**
     * Get active
     *
     * @return string
     */
    public function getActive()
    {
        return $this->getData('active');
    }

    /**
     * Set active
     *
     * @param int|bool $active
     * @return $this
     */
    public function setActive($active)
    {
        return $this->setData('active', $active ? 1 : 0);
    }

    /**
     * Get created at date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set created at date
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get updated at date
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated at date
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * Get last use date
     *
     * @return string
     */
    public function getLastUse()
    {
        return $this->getData('last_use');
    }

    /**
     * Get expires
     *
     * @return string
     */
    public function getExpires()
    {
        return $this->getData('expires');
    }

    /**
     * Set expires
     *
     * @param string $expires
     * @return $this
     */
    public function setExpires($expires)
    {
        return $this->setData('expires', $expires);
    }

    /**
     * Set last use date
     *
     * @param $lastUse
     * @return $this
     */
    public function setLastUse($lastUse)
    {
        return $this->setData('last_use', $lastUse);
    }

    /**
     * Get payment info instance (if any)
     *
     * @return \Magento\Payment\Model\InfoInterface|null
     */
    public function getInfoInstance()
    {
        return $this->getData('info_instance');
    }

    /**
     * Set payment info instance
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function setInfoInstance(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->setData('info_instance', $payment);
    }

    /**
     * Get card label (formatted number).
     *
     * @param bool $includeType
     * @return string|\Magento\Framework\Phrase
     */
    public function getLabel($includeType = true)
    {
        if ($this->getAdditional('cc_last4')) {
            $cardType = '';

            if ($includeType === true && $this->getAdditional('cc_type')) {
                $cardType = $this->helper->translateCardType($this->getAdditional('cc_type'));
            }

            return trim(__(
                '%1 XXXX-%2',
                $cardType,
                $this->getAdditional('cc_last4')
            ));
        }

        return '';
    }

    /**
     * Finalize before saving. Instances should sync with the gateway here.
     *
     * Set $this->_dataSaveAllowed to false or throw exception to abort.
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();

        /**
         * If the payment ID has changed, look for any duplicate payment records that might be stored.
         */
        if ($this->getOrigData('payment_id') != $this->getData('payment_id')) {
            /** @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $collection */
            $collection = $this->cardCollectionFactory->create();
            $collection->addFieldToFilter('method', $this->getData('method'))
                       ->addFieldToFilter('profile_id', $this->getData('profile_id'))
                       ->addFieldToFilter('payment_id', $this->getData('payment_id'))
                       ->setPageSize(1)
                       ->setCurPage(1);
            
            if ($this->getId() > 0) {
                $collection->addFieldToFilter('id', ['neq' => $this->getId()]);
            }

            /** @var \ParadoxLabs\TokenBase\Model\Card $dupe */
            $dupe = $collection->getFirstItem();

            /**
             * If we find a duplicate, switch to that one, but retain the current info otherwise.
             */
            if ($dupe && $dupe->getId() > 0 && $dupe->getId() != $this->getId()) {
                $this->mergeCardOnto($dupe);
            }
        }

        /**
         * If we are on the frontend, record current IP.
         */
        if ($this->helper->getIsFrontend()) {
            $this->setCustomerIp($this->remoteAddress->getRemoteAddress());
        }

        /**
         * Create unique hash for security purposes.
         */
        $this->getHash();

        /**
         * Update dates.
         */
        $now = $this->dateProcessor->date(null, null, false);

        if ($this->isObjectNew()) {
            $this->setData('created_at', $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        $this->setData('updated_at', $now->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        return $this;
    }

    /**
     * Mege the current card info over the given one. Retain the given card's ID.
     *
     * It is assumed that the current card and the one given have the same gateway reference.
     *
     * @param Card $card Card to merge current data onto.
     * @return $this
     */
    protected function mergeCardOnto(\ParadoxLabs\TokenBase\Model\Card $card)
    {
        $this->helper->log(
            $this->getData('method'),
            __('Merging duplicate payment data into card %1', $card->getId())
        );

        $this->setId($card->getId());
        $this->isObjectNew(false);

        return $this;
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Framework\Api\ExtensionAttributesInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\Framework\Api\ExtensionAttributesInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Mapping methods for compatibility with \Magento\Vault\Api\Data\PaymentTokenInterface
     */

    /**
     * Get public hash
     *
     * @return string
     */
    public function getPublicHash()
    {
        return $this->getHash();
    }

    /**
     * Set public hash
     *
     * @param string $hash
     * @return $this
     */
    public function setPublicHash($hash)
    {
        return $this->setHash($hash);
    }

    /**
     * Get payment method code
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return $this->getMethod();
    }

    /**
     * Set payment method code
     *
     * @param string $code
     * @return $this
     */
    public function setPaymentMethodCode($code)
    {
        return $this->setMethod($code);
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->getAdditional('cc_type');
    }

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setAdditional('cc_type', $type);
    }

    /**
     * Get token expiration timestamp
     *
     * @return string|null
     */
    public function getExpiresAt()
    {
        return $this->getExpires();
    }

    /**
     * Set token expiration timestamp
     *
     * @param string $timestamp
     * @return $this
     */
    public function setExpiresAt($timestamp)
    {
        return $this->setExpires($timestamp);
    }

    /**
     * Get gateway token ID
     *
     * @return string
     */
    public function getGatewayToken()
    {
        return $this->getPaymentId();
    }

    /**
     * Set gateway token ID
     *
     * @param string $token
     * @return $this
     */
    public function setGatewayToken($token)
    {
        return $this->setPaymentId($token);
    }

    /**
     * Get token details
     *
     * @return string
     */
    public function getTokenDetails()
    {
        return $this->getAdditional();
    }

    /**
     * Set token details
     *
     * @param string $details
     * @return $this
     */
    public function setTokenDetails($details)
    {
        return $this->setAdditional($details);
    }

    /**
     * Gets is vault payment record active.
     *
     * @return bool Is active.
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsActive()
    {
        return $this->getActive();
    }

    /**
     * Sets is vault payment record active.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setActive($isActive);
    }

    /**
     * Gets is vault payment record visible.
     *
     * @return bool Is visible.
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVisible()
    {
        return $this->getActive();
    }

    /**
     * Sets is vault payment record visible.
     *
     * @param bool $isVisible
     * @return $this
     */
    public function setIsVisible($isVisible)
    {
        return $this->setActive($isVisible);
    }
}

/**
 * This is messy. Sorry. Supporting 2.1 Vault without breaking 2.0 compatibility.
 * The 2.1+ version implements \Magento\Vault; 2.0 does not.
 */
if (interface_exists('\Magento\Vault\Api\Data\PaymentTokenInterface')) {
    require_once 'Card/Card210.php';
} else {
    require_once 'Card/Card200.php';
}
