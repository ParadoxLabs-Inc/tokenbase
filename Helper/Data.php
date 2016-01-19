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

namespace ParadoxLabs\TokenBase\Helper;

/**
 * Class Data
 */
class Data extends \Magento\Payment\Helper\Data
{
    /**
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $card;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection[]
     */
    protected $cards = [];

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Logger\Logger
     */
    protected $tokenbaseLogger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $currentCustomer;

    /**
     * @var AddressFactory
     */
    protected $addressHelperFactory;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var array
     */
    protected $cardTypeTranslationMap = [
        'AE'    => 'American Express',
        'DI'    => 'Discover',
        'DC'    => 'Diners Club',
        'JCB'   => 'JCB',
        'MC'    => 'MasterCard',
        'VI'    => 'Visa',
    ];

    /**
     * @var array
     */
    protected $achAccountTypes = [
        'checking'         => 'Checking',
        'savings'          => 'Savings',
        'businessChecking' => 'Business Checking',
    ];

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Framework\App\Config\Initial $initialConfig
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger
     * @param \ParadoxLabs\TokenBase\Helper\AddressFactory $addressHelperFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger,
        \ParadoxLabs\TokenBase\Helper\AddressFactory $addressHelperFactory
    ) {
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->objectManager = $objectManager;
        $this->websiteFactory = $websiteFactory;
        $this->customerFactory = $customerFactory;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->tokenbaseLogger = $tokenbaseLogger;
        $this->addressHelperFactory = $addressHelperFactory;
        $this->paymentFactory = $paymentFactory;

        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );
    }

    /**
     * Return active payment methods (if any) implementing tokenbase.
     *
     * @api
     *
     * @return array
     */
    public function getActiveMethods()
    {
        $methods = [];

        foreach ($this->getPaymentMethods() as $code => $data) {
            if (isset($data['group']) && $data['group'] == 'tokenbase') {
                $method = $this->getMethodInstance($code);

                if ($method->getConfigData('active') == 1) {
                    $methods[] = $code;
                }
            }
        }

        return $methods;
    }

    /**
     * Return all tokenbase-derived payment methods, without an active check.
     *
     * @api
     *
     * @return array
     */
    public function getAllMethods()
    {
        $methods = [];

        foreach ($this->getPaymentMethods() as $code => $data) {
            if (isset($data['group']) && $data['group'] == 'tokenbase') {
                $methods[] = $code;
            }
        }

        return $methods;
    }

    /**
     * Return store scope based on the available info... the admin panel makes this complicated.
     *
     * @return int
     */
    public function getCurrentStoreId()
    {
        if (!$this->getIsFrontend()) {
            if ($this->registry->registry('current_order') != null) {
                return $this->registry->registry('current_order')->getStoreId();
            } elseif ($this->registry->registry('current_customer') != null) {
                $storeId = $this->registry->registry('current_customer')->getStoreId();

                // Customers registered through the admin will have store_id=0 with a valid website_id. Try to use that.
                if ($storeId < 1) {
                    /** @var \Magento\Store\Model\Website $website */

                    $websiteId  = $this->registry->registry('current_customer')->getWebsiteId();
                    $website    = $this->websiteFactory->create();
                    $store      = $website->load($websiteId)->getDefaultStore();

                    if ($store instanceof \Magento\Store\Model\Store) {
                        $storeId = $store->getId();
                    }
                }

                return $storeId;
            } elseif ($this->registry->registry('current_invoice') != null) {
                return $this->registry->registry('current_invoice')->getStoreId();
            } elseif ($this->registry->registry('current_creditmemo') != null) {
                return $this->registry->registry('current_creditmemo')->getStoreId();
            } else {
                // Don't like to use the object manager directly but this is how the core does it.
                // @see \Magento\Sales\Controller\Adminhtml\Order\Create::_getSession()

                /** @var \Magento\Backend\Model\Session\Quote $backendSession */
                $backendSession = $this->objectManager->get('Magento\Backend\Model\Session\Quote');

                if ($backendSession->getStoreId() > 0) {
                    return $backendSession->getStoreId();
                } else {
                    return 0;
                }
            }
        }

        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return current customer based on the available info.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCurrentCustomer()
    {
        if (!is_null($this->currentCustomer)) {
            return $this->currentCustomer;
        }

        $customer = $this->customerFactory->create();

        if (!$this->getIsFrontend()) {
            if ($this->registry->registry('current_order') != null) {
                $customer->load($this->registry->registry('current_order')->getCustomerId());
            } elseif ($this->registry->registry('current_customer') != null) {
                $customer = $this->registry->registry('current_customer');
            } elseif ($this->registry->registry('current_invoice') != null) {
                $customer->load($this->registry->registry('current_invoice')->getCustomerId());
            } elseif ($this->registry->registry('current_creditmemo') != null) {
                $customer->load($this->registry->registry('current_creditmemo')->getCustomerId());
            } else {
                // Don't like to use the object manager directly but this is how the core does it.
                // We don't necessarily want to inject it since that would initialize the session every time.
                // @see \Magento\Sales\Controller\Adminhtml\Order\Create::_getSession()

                /** @var \Magento\Backend\Model\Session\Quote $backendSession */
                $backendSession = $this->objectManager->get('Magento\Backend\Model\Session\Quote');

                if ($backendSession->hasQuoteId()) {
                    if ($backendSession->getQuote()->getCustomerId() > 0) {
                        $customer->load($backendSession->getQuote()->getCustomerId());
                    } elseif ($backendSession->getQuote()->getCustomerEmail() != '') {
                        $customer->setData('email', $backendSession->getQuote()->getCustomerEmail());
                    }
                }
            }
        } elseif ($this->registry->registry('current_customer') != null) {
            $customer = $this->registry->registry('current_customer');
        } else {
            // We don't necessarily want to inject this since that would initialize the session every time.
            /** @var \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer */
            $currentCustomer = $this->objectManager->get('Magento\Customer\Helper\Session\CurrentCustomer');
            if ($currentCustomer->getCustomerId() > 0) {
                $customer = $currentCustomer->getCustomer();
            }
        }

        $this->currentCustomer = $customer;

        return $this->currentCustomer;
    }

    /**
     * Return active card model for edit (if any).
     *
     * @param string|null $method
     * @return \ParadoxLabs\TokenBase\Model\Card
     */
    public function getActiveCard($method = null)
    {
        $method = is_null($method) ? $this->registry->registry('tokenbase_method') : $method;

        if (is_null($this->card)) {
            if ($this->registry->registry('active_card')) {
                $this->card = $this->registry->registry('active_card');
            } else {
                $this->card = $this->cardFactory->create();
                $this->card->setMethod($method);

                /**
                 * Make sure we have the derivative card object for purposes of gateway syncing, etc.
                 */
                $this->card = $this->card->getTypeInstance();
            }

            /**
             * Import prior form data from the session, if possible.
             */
            if ($this->getIsFrontend()) {
                $session = $this->objectManager->get('Magento\Customer\Model\Session');
                if ($session->hasTokenbaseFormData()) {
                    $data = $session->getTokenbaseFormData(true);

                    if (isset($data['billing']) && count($data['billing']) > 0) {
                        /** @var \ParadoxLabs\TokenBase\Helper\Address $addressHelper */
                        $addressHelper  = $this->addressHelperFactory->create();

                        $address        = $addressHelper->buildAddressFromInput(
                            $data['billing'],
                            $this->card->getAddress()
                        );

                        $this->card->setAddress($address);
                    }

                    if (isset($data['payment']) && count($data['payment']) > 0) {
                        $cardData = $data['payment'];
                        $cardData['method']     = $method;
                        $cardData['card_id']    = $data['id'];
                        // This bypasses the validation check in importData below. Does not matter otherwise.
                        $cardData['cc_cid']     = '000';

                        unset($cardData['cc_number']);
                        unset($cardData['echeck_account_no']);
                        unset($cardData['echeck_routing_no']);

                        /** @var \Magento\Checkout\Model\Session $checkoutSession */
                        $checkoutSession = $this->objectManager->get('Magento\Checkout\Model\Session');

                        /** @var \Magento\Quote\Model\Quote\Payment $newPayment */
                        $newPayment = $this->paymentFactory->create();
                        $newPayment->setQuote($checkoutSession->getQuote());
                        $newPayment->getQuote()->getBillingAddress()->setCountryId(
                            $this->card->getAddress('country_id')
                        );

                        try {
                            $newPayment->importData($cardData);
                        } catch (\Exception $e) {
                            // We're only trying to load prior-entered data for the form. Ignore validation errors.
                        }

                        $this->card->importPaymentInfo($newPayment);
                    }
                }
            }
        }

        return $this->card;
    }

    /**
     * Get stored cards for the currently-active method.
     *
     * @param string|null $method
     * @return \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection|array
     */
    public function getActiveCustomerCardsByMethod($method = null)
    {
        $method = is_null($method) ? $this->registry->registry('tokenbase_method') : $method;

        if (!isset($this->cards[ $method ])) {
            $this->_eventManager->dispatch(
                'tokenbase_before_load_active_cards',
                [
                    'method'    => $method,
                    'customer'  => $this->getCurrentCustomer(),
                ]
            );

            $this->cards[ $method ] = $this->cardCollectionFactory->create();

            if (!$this->getIsFrontend()) {
                /** @var \Magento\Backend\Model\Session\Quote $backendSession */
                $backendSession = $this->objectManager->get('Magento\Backend\Model\Session\Quote');

                if ($backendSession->hasQuoteId()
                    && $backendSession->getQuote()->getPayment()->getData('tokenbase_id') > 0
                    && !($this->registry->registry('current_customer') instanceof \Magento\Customer\Model\Customer)) {
                    // Case where we want to show a card that may not otherwise be (edit or reorder)
                    $tokenbaseId = $backendSession->getQuote()->getPayment()->getData('tokenbase_id');

                    if ($this->getCurrentCustomer()->getId() > 0) {
                        // Manual select -- only because collections don't let us do the complex condition. (soz.)
                        $this->cards[$method]->getSelect()->where(
                            sprintf(
                                "(id='%s' and customer_id='%s') or (active=1 and customer_id='%s')",
                                $tokenbaseId,
                                $this->getCurrentCustomer()->getId(),
                                $this->getCurrentCustomer()->getId()
                            )
                        );
                    } else {
                        $this->cards[$method]->addFieldToFilter('id', $tokenbaseId);
                    }
                } elseif ($this->getCurrentCustomer()->getId() > 0) {
                    // Case where we want to show a customer's stored cards (if any)
                    $this->cards[ $method ]->addFieldToFilter('active', 1)
                                           ->addFieldToFilter('customer_id', $this->getCurrentCustomer()->getId());
                } else {
                    // Guest
                    return [];
                }
            } elseif ($this->getCurrentCustomer()->getId() > 0) {
                $this->cards[ $method ]->addFieldToFilter('active', 1)
                                       ->addFieldToFilter('customer_id', $this->getCurrentCustomer()->getId());
            } else {
                return [];
            }

            if (!is_null($method)) {
                $this->cards[ $method ]->addFieldToFilter('method', $method);
                $this->cards[ $method ]->addFieldToFilter('payment_id', ['notnull' => true]);
                $this->cards[ $method ]->addFieldToFilter('payment_id', ['neq' => '']);
            }

            $this->_eventManager->dispatch(
                'tokenbase_after_load_active_cards',
                [
                    'method'    => $method,
                    'customer'  => $this->getCurrentCustomer(),
                    'cards'     => $this->cards[ $method ],
                ]
            );
        }

        return $this->cards[ $method ];
    }

    /**
     * Check whether we are in the frontend area.
     *
     * @return bool
     */
    public function getIsFrontend()
    {
        // The REST API has to be considered part of the frontend, as standard checkout uses it.
        if ($this->appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND
            || $this->appState->getAreaCode() == 'webapi_rest') {
            return true;
        }

        return false;
    }

    /**
     * Check whether we are in a card management account area.
     *
     * It's not flawless, but more reliable than trying to detect checkout.
     *
     * @return bool
     */
    public function getIsAccount()
    {
        if ($this->registry->registry('tokenbase_method') !== null) {
            return true;
        }

        return false;
    }

    /**
     * Turn the given internal card type ID into a proper translated label.
     *
     * @api
     *
     * @param $type
     * @return \Magento\Framework\Phrase
     */
    public function translateCardType($type)
    {
        if (isset($this->cardTypeTranslationMap[ $type ])) {
            return __($this->cardTypeTranslationMap[ $type ]);
        }

        return __($type);
    }

    /**
     * Return valid ACH account types.
     *
     * @param string $code
     * @return string|array|null
     */
    public function getAchAccountTypes($code = null)
    {
        if (!is_null($code)) {
            if (isset($this->achAccountTypes[$code])) {
                return $this->achAccountTypes[$code];
            }

            return $code;
        }

        return $this->achAccountTypes;
    }

    /**
     * Map CC Type to Magento's. Should be implemented by the child method.
     *
     * @api
     *
     * @param string $type
     * @return string|null
     */
    public function mapCcTypeToMagento($type)
    {
        return $type;
    }

    /**
     * Recursively cleanup array from objects
     *
     * @param $array
     * @return void
     */
    public function cleanupArray(&$array)
    {
        if (!$array) {
            return;
        }

        foreach ($array as $key => $value) {
            if (is_object($value)) {
                unset($array[ $key ]);
            } elseif (is_array($value)) {
                $this->cleanupArray($array[ $key ]);
            }
        }
    }

    /**
     * Pull a value from a nested array safely (without notices, default fallback)
     *
     * @param  array  $data    source array
     * @param  string $path    path to pull, separated by slashes
     * @param  string $default default response (if key DNE)
     * @return mixed           target value or default
     */
    public function getArrayValue($data, $path, $default = '')
    {
        $path = explode('/', $path);
        $val =& $data;

        foreach ($path as $key) {
            if (!isset($val[$key])) {
                return $default;
            }

            $val =& $val[$key];
        }

        return $val;
    }

    /**
     * Write a message to the logs, nice and abstractly.
     *
     * @param string $code
     * @param mixed $message
     * @param bool $debug
     * @return $this
     */
    public function log($code, $message, $debug = false)
    {
        if (is_object($message)) {
            if ($message instanceof \Magento\Framework\DataObject) {
                $message = $message->getData();

                $this->cleanupArray($message);
            } else {
                $message = (array)$message;
            }
        }

        if (is_array($message)) {
            $message = print_r($message, 1);
        }

        if ($debug === true) {
            $this->tokenbaseLogger->debug(
                sprintf('%s [%s]: %s', $code, $this->_remoteAddress->getRemoteAddress(), $message)
            );
        } else {
            $this->tokenbaseLogger->info(
                sprintf('%s [%s]: %s', $code, $this->_remoteAddress->getRemoteAddress(), $message)
            );
        }

        return $this;
    }
}
