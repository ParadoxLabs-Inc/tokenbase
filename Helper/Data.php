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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    protected $currentCustomer;

    /**
     * @var Address
     */
    protected $addressHelper;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Operation
     */
    protected $operationHelper;

    /**
     * @var array
     */
    protected $cardTypeTranslationMap;

    /**
     * @var array
     */
    protected $achAccountTypes = [
        'checking'         => 'Checking',
        'savings'          => 'Savings',
        'businessChecking' => 'Business Checking',
    ];

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $backendSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomerSession;

    /**
     * @var array
     */
    protected $methods;

    /**
     * @var array
     */
    protected $methodInstances = [];

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
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \Magento\Backend\Model\Session\Quote $backendSession *Proxy
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomerSession *Proxy
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper *Proxy
     * @param \ParadoxLabs\TokenBase\Helper\Operation $operationHelper
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
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Backend\Model\Session\Quote $backendSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomerSession,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \ParadoxLabs\TokenBase\Helper\Operation $operationHelper
    ) {
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->websiteFactory = $websiteFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->addressHelper = $addressHelper;
        $this->paymentFactory = $paymentFactory;
        $this->operationHelper = $operationHelper;
        $this->backendSession = $backendSession;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->currentCustomerSession = $currentCustomerSession;

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
        $storeId = $this->getCurrentStoreId();

        foreach ($this->getPaymentMethods() as $code => $data) {
            if (isset($data['group']) && $data['group'] === 'tokenbase') {
                $method = $this->getMethodInstance($code);

                if ((bool)$method->getConfigData('active', $storeId) === true) {
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
        if ($this->methods === null) {
            $this->methods = [];

            foreach ($this->getPaymentMethods() as $code => $data) {
                if (isset($data['group']) && $data['group'] === 'tokenbase') {
                    $this->methods[] = $code;
                }
            }

            return $this->methods;
        }

        return $this->methods;
    }

    /**
     * Get payment method instance
     *
     * @param string $code
     * @return \Magento\Payment\Model\MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethodInstance($code)
    {
        /**
         * Persist instances to reduce object instantiation -- ONLY FOR THIS OBJECT. \Magento\Payment\Helper\Data will
         * still create at each call, as will MethodFactory. @see \ParadoxLabs\TokenBase\Model\Method\Factory
         */
        if (!isset($this->methodInstances[$code])) {
            $this->methodInstances[$code] = parent::getMethodInstance($code);
        }

        return $this->methodInstances[$code];
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
                if ($this->backendSession->getStoreId() > 0) {
                    return $this->backendSession->getStoreId();
                } else {
                    return 0;
                }
            }
        }

        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return the current store object based on available info.
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore(
            $this->getCurrentStoreId()
        );
    }

    /**
     * Return current customer based on the available info. Caches value per-request.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCurrentCustomer()
    {
        if ($this->currentCustomer !== null) {
            return $this->currentCustomer;
        }

        $registryCustomer = $this->registry->registry('current_customer');

        if ($registryCustomer instanceof \Magento\Customer\Model\Customer) {
            $this->currentCustomer = $registryCustomer->getDataModel();
        } elseif ($registryCustomer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $this->currentCustomer = $registryCustomer;
        } elseif (!$this->getIsFrontend()) {
            $this->currentCustomer = $this->getCurrentBackendCustomer();
        } elseif ($this->currentCustomerSession->getCustomerId() > 0) {
            $this->currentCustomer = $this->customerRepository->getById(
                $this->currentCustomerSession->getCustomerId()
            );
        }

        if (($this->currentCustomer instanceof \Magento\Customer\Api\Data\CustomerInterface) === false) {
            $this->currentCustomer = $this->customerFactory->create();
        }

        return $this->currentCustomer;
    }

    /**
     * Get current customer in the adminhtml scope. Looks at order, quote, invoice, credit memo.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    protected function getCurrentBackendCustomer()
    {
        $customer = $this->customerFactory->create();

        if ($this->registry->registry('current_order') != null
            && $this->registry->registry('current_order')->getCustomerId() > 0) {
            $customer = $this->customerRepository->getById(
                $this->registry->registry('current_order')->getCustomerId()
            );
        } elseif ($this->registry->registry('current_invoice') != null
            && $this->registry->registry('current_invoice')->getOrder()->getCustomerId() > 0) {
            $customer = $this->customerRepository->getById(
                $this->registry->registry('current_invoice')->getOrder()->getCustomerId()
            );
        } elseif ($this->registry->registry('current_creditmemo') != null
            && $this->registry->registry('current_creditmemo')->getOrder()->getCustomerId() > 0) {
            $customer = $this->customerRepository->getById(
                $this->registry->registry('current_creditmemo')->getOrder()->getCustomerId()
            );
        } elseif ($this->backendSession->hasQuoteId()) {
            if ($this->backendSession->getQuote()->getCustomerId() > 0) {
                $customer = $this->customerRepository->getById(
                    $this->backendSession->getQuote()->getCustomerId()
                );
            } elseif ($this->backendSession->getQuote()->getCustomerEmail() != '') {
                $customer->setEmail($this->backendSession->getQuote()->getCustomerEmail());
            }
        }

        return $customer;
    }

    /**
     * Return active card model for edit (if any).
     *
     * @param string|null $method
     * @return \ParadoxLabs\TokenBase\Model\Card
     */
    public function getActiveCard($method = null)
    {
        $method = ($method === null) ? $this->registry->registry('tokenbase_method') : $method;

        if ($this->card === null) {
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
            if ($this->getIsFrontend() && $this->customerSession->hasTokenbaseFormData()) {
                $data = $this->customerSession->getTokenbaseFormData(true);

                if (isset($data['billing']) && !empty($data['billing'])) {
                    $address        = $this->addressHelper->buildAddressFromInput(
                        $data['billing'],
                        $this->card->getAddress()
                    );

                    $this->card->setAddress($address);
                }

                if (isset($data['payment']) && !empty($data['payment'])) {
                    $cardData = $data['payment'];
                    $cardData['method']     = $method;
                    $cardData['card_id']    = $data['id'];
                    // This bypasses the validation check in importData below. Does not matter otherwise.
                    $cardData['cc_cid']     = '000';

                    unset($cardData['cc_number'], $cardData['echeck_account_no'], $cardData['echeck_routing_no']);

                    /** @var \Magento\Quote\Model\Quote\Payment $newPayment */
                    $newPayment = $this->paymentFactory->create();
                    $newPayment->setQuote($this->checkoutSession->getQuote());
                    $newPayment->getQuote()->getBillingAddress()->setCountryId(
                        $this->card->getAddress('country_id')
                    );

                    try {
                        $newPayment->importData($cardData);
                    } catch (\Exception $e) {
                        // We're only trying to load prior-entered data for the form. Ignore validation errors.
                        $this->card->getId();
                    }

                    $this->card->importPaymentInfo($newPayment);
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
        $method = ($method === null) ? $this->registry->registry('tokenbase_method') : $method;

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
                if ($this->backendSession->getData('quote_id')
                    && $this->backendSession->getQuote()->getPayment()->getData('tokenbase_id') > 0
                    && !($this->registry->registry('current_customer') instanceof \Magento\Customer\Model\Customer)) {
                    // Case where we want to show a card that may not otherwise be (edit or reorder)
                    $tokenbaseId = $this->backendSession->getQuote()->getPayment()->getData('tokenbase_id');

                    if ($this->getCurrentCustomer()->getId() > 0) {
                        $this->cards[ $method ]->addFieldToFilter('customer_id', $this->getCurrentCustomer()->getId());
                        $this->cards[ $method ]->addFieldToFilter(
                            [
                                'id',
                                'active',
                            ],
                            [
                                ['eq' => $tokenbaseId],
                                ['eq' => 1],
                            ]
                        );
                    } else {
                        $this->cards[ $method ]->addFieldToFilter('id', $tokenbaseId);
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

            if ($method !== null) {
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
        // The REST and GraphQL APIs have to be considered part of the frontend.
        if ($this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND
            || $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_WEBAPI_REST
            || (defined('\Magento\Framework\App\Area::AREA_GRAPHQL')
                && $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_GRAPHQL)) {
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
        if ($this->cardTypeTranslationMap === null) {
            $this->cardTypeTranslationMap = $this->_paymentConfig->getCcTypes();
        }

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
        if ($code !== null) {
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
        $this->operationHelper->cleanupArray($array);
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
        return $this->operationHelper->getArrayValue($data, $path, $default);
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
        $this->operationHelper->log($code, $message, $debug);

        return $this;
    }
}
