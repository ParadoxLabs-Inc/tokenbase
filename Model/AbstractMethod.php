<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <magento@paradoxlabs.com>
 * @license        http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model;

/**
 * Common actions and behavior for TokenBase payment methods
 */
abstract class AbstractMethod extends \Magento\Payment\Model\Method\Cc
{
    /**
     * @var string
     */
    protected $_code = 'tokenbase';

    /**
     * @var string
     */
    protected $_formBlockType = 'ParadoxLabs\TokenBase\Block\Form\Cc';

    /**
     * @var string
     */
    protected $_infoBlockType = 'ParadoxLabs\TokenBase\Block\Info\Cc';

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isGateway = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canOrder = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canFetchTransactionInfo = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canReviewPayment = false;

    /**
     * This may happen when amount is captured, but not settled
     * @var bool
     */
    protected $_canCancelInvoice = true;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Model\AbstractGateway
     */
    protected $gateway;

    /**
     * @var \Magento\Customer\Model\Customer|null
     */
    protected $customer;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $card;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\AddressFactory
     */
    protected $addressHelperFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\AbstractGateway $gateway
     * @param CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Helper\AddressFactory $addressHelperFactory
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Model\AbstractGateway $gateway,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Helper\AddressFactory $addressHelperFactory,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->gateway = $gateway;
        $this->cardFactory = $cardFactory;
        $this->addressHelperFactory = $addressHelperFactory;
        
        $this->setStore($this->helper->getCurrentStoreId());
        
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Set the payment config scope and reinitialize the API
     *
     * @param int $storeId
     * @return $this
     */
    public function setStore($storeId)
    {
        // Whelp.
        if ($storeId instanceof \Magento\Framework\App\ScopeInterface) {
            $storeId = $storeId->getId();
        }

        $this->setData('store', (int)$storeId);

        $this->gateway->reset();

        return $this;
    }

    /**
     * Set the customer to use for payment/card operations.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return $this
     */
    public function setCustomer(\Magento\Customer\Model\Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get the current customer; fetch from session if necessary.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        if (is_null($this->customer) || $this->customer->getId() < 1) {
            $this->setCustomer($this->helper->getCurrentCustomer());
        }

        return $this->customer;
    }

    /**
     * Initialize/return the API gateway class.
     *
     * @return \ParadoxLabs\TokenBase\Model\AbstractGateway
     */
    public function gateway()
    {
        if ($this->gateway->isInitialized() !== true) {
            $this->gateway->init(array(
                'login'      => $this->getConfigData('login'),
                'password'   => $this->getConfigData('trans_key'),
                'secret_key' => $this->getConfigData('secrey_key'),
                'test_mode'  => $this->getConfigData('test'),
            ));
        }

        return $this->gateway;
    }

    /**
     * Load the given card by ID, authenticate, and store with the object.
     *
     * @param int|string $cardId
     * @param bool $byHash
     * @return Card
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function loadAndSetCard($cardId, $byHash = false)
    {
        $this->log(sprintf('loadAndSetCard(%s, %s)', $cardId, var_export($byHash, 1)));

        /** @var Card $card */
        $card = $this->cardFactory->create();
        
        if ($byHash === true) {
            $card->loadByHash($cardId);
        } else {
            $card->load($cardId);
        }

        if ($card && $card->getId() > 0) {
            $this->setCard($card);

            return $this->getCard();
        }

        /**
         * This error will be thrown if the card does not exist OR if we don't have permission to use it.
         */
        $this->log(sprintf('Unable to load payment data. Please check the form and try again.'));

        throw new \Magento\Framework\Exception\PaymentException(
            __('Unable to load payment data. Please check the form and try again.')
        );
    }

    /**
     * Get the current card
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set the current payment card
     *
     * @param Card $card
     * @return $this
     */
    public function setCard(\ParadoxLabs\TokenBase\Model\Card $card)
    {
        $this->log(sprintf('setCard(%s)', $card->getId()));

        $this->card = $card;

        $this->gateway()->setCard($card);

        $this->getInfoInstance()->setData('tokenbase_id', $card->getId())
                                ->setData('cc_type', $card->getAdditional('cc_type'))
                                ->setData('cc_last_4', $card->getAdditional('cc_last4'))
                                ->setData('cc_exp_month', $card->getAdditional('cc_exp_month'))
                                ->setData('cc_exp_year', $card->getAdditional('cc_exp_year'));

        return $this;
    }

    /**
     * Update the CC info during the checkout process.
     *
     * @param \Magento\Framework\Object|mixed $data
     * @return $this
     */
    public function assignData($data)
    {
        $this->log(sprintf('assignData(%s)', $data->getData('card_id')));

        if (!($data instanceof \Magento\Framework\Object)) {
            $data = new \Magento\Framework\Object($data);
        }

        parent::assignData($data);

        /** @var \Magento\Sales\Model\Order\Payment\Info $info */
        $info = $this->getInfoInstance();

        if ($data->hasData('card_id') && $data->getData('card_id') != '') {
            /**
             * Load and validate the chosen card.
             *
             * If we are in checkout, force load by hash rather than numeric ID. Bit harder to guess.
             */
            if ($this->helper->getIsFrontend() || !is_numeric($data->getData('card_id'))) {
                $this->loadAndSetCard($data->getData('card_id'), true);
            } else {
                $this->loadAndSetCard($data->getData('card_id'));
            }

            /**
             * Overwrite data if necessary
             */
            if ($data->hasData('cc_type') && $data->getData('cc_type') != '') {
                $info->setData('cc_type', $data->getData('cc_type'));
            }

            if ($data->hasData('cc_last4') && $data->getData('cc_last4') != '') {
                $info->setData('cc_last_4', $data->getData('cc_last4'));
            }

            if ($data->getData('cc_exp_year') != ''  && $data->getData('cc_exp_month') != '') {
                $info->setData('cc_exp_year', $data->getData('cc_exp_year'))
                     ->setData('cc_exp_month', $data->getData('cc_exp_month'));
            }
        } else {
            $info->unsetData('tokenbase_id');
        }

        if ($data->hasData('save')) {
            $info->setAdditionalInformation('save', (int)$data->getData('save'));
        }

        return $this;
    }

    /**
     * Check whether void is available for the given order.
     *
     * @return bool
     */
    public function canVoid()
    {
        if (parent::canVoid()) {
            /** @var \Magento\Sales\Model\Order\Payment\Info $payment */
            $payment = $this->getInfoInstance();
            
            /** @var \Magento\Sales\Model\Order $order */
            $order   = $payment->getOrder();

            if (($order instanceof \Magento\Sales\Model\Order) && $order->canCancel()) {
                /**
                 * Bad convention: Auth code is stored as the second part of ext_order_id.
                 * If there is no auth code, it has already been voided or is not relevant.
                 */
                $transactionId = explode(':', $order->getExtOrderId(), 2);

                if (!isset($transactionId[1]) || empty($transactionId[1])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Validate the transaction inputs.
     * @return $this
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function validate()
    {
        $this->log(sprintf('validate(%s)', $this->getInfoInstance()->getCardId()));

        /** @var \Magento\Sales\Model\Order\Payment\Info $info */
        $info = $this->getInfoInstance();

        /**
         * If no tokenbase ID, we must have a new card. Make sure all the details look valid.
         */
        if ($info->hasData('tokenbase_id') === false) {
            return parent::validate();
        } else {
            /**
             * If there is an ID, this might be an edit. Validate there too, as much as we can.
             */
            if ($info->getData('cc_number') != '' && substr($info->getData('cc_number'), 0, 4) != 'XXXX') {
                // remove credit card number delimiters such as "-" and space
                $info->setData('cc_number', preg_replace('/[\-\s]+/', '', $info->getData('cc_number')));

                if (strlen($info->getData('cc_number')) < 13
                    || !is_numeric($info->getData('cc_number'))
                    || !$this->validateCcNum($info->getData('cc_number'))) {
                    throw new \Magento\Framework\Exception\PaymentException(
                        __('Invalid Credit Card Number')
                    );
                }
            }

            if ($info->getData('cc_exp_year') != '' && $info->getData('cc_exp_month') != '') {
                if (!$this->_validateExpDate($info->getData('cc_exp_year'), $info->getData('cc_exp_month'))) {
                    throw new \Magento\Framework\Exception\PaymentException(
                        __('Incorrect credit card expiration date.')
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Authorize a transaction
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->log(sprintf('authorize(%s %s, %s)', get_class($payment), $payment->getId(), $amount));

        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /**
         * Check for existing authorization, and void it if so.
         */
        $transactionId = explode(':', $payment->getOrder()->getExtOrderId());
        if (!empty($transactionId[1])) {
            $this->void($payment);
        }

        /**
         * Process transaction and results
         */
        $this->resyncStoredCard($payment);

        if ($this->getConfigData('send_line_items')) {
            $this->gateway()->setLineItems($payment->getOrder()->getAllVisibleItems());
        }

        $this->beforeAuthorize($payment, $amount);
        $response = $this->gateway()->authorize($payment, $amount);
        $this->afterAuthorize($payment, $amount, $response);

        if ($response->getData('is_fraud') === true) {
            $payment->setIsTransactionPending(true)
                    ->setIsFraudDetected(true)
                    ->setTransactionAdditionalInfo('is_transaction_fraud', true);
        } else {
            $payment->getOrder()->setStatus($this->getConfigData('order_status'));
        }

        $payment->getOrder()->setExtOrderId(sprintf(
            '%s:%s',
            $response->getData('transaction_id'),
            $response->getData('auth_code')
        ));

        $payment->setTransactionId($response->getData('transaction_id'))
                ->setAdditionalInformation(array_merge($payment->getAdditionalInformation(), $response->getData()))
                ->setIsTransactionClosed(0);

        $this->getCard()->updateLastUse()->save();

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Capture a transaction [authorize if necessary]
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->log(sprintf('capture(%s %s, %s)', get_class($payment), $payment->getId(), $amount));

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /**
         * Check for existing auth code.
         */
        $transactionId = explode(':', $payment->getOrder()->getExtOrderId());
        if (!empty($transactionId[1])) {
            $this->gateway()->setHaveAuthorized(true);
            $this->gateway()->setAuthCode($transactionId[1]);
            $this->gateway()->setTransactionId($transactionId[0]);
        } else {
            $this->gateway()->setHaveAuthorized(false);
        }

        /**
         * Grab transaction ID from the invoice in case partial invoicing.
         */
        $realTransactionId    = null;

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = null;

        if ($payment->hasData('invoice')
            && $payment->getData('invoice') instanceof \Magento\Sales\Model\Order\Invoice) {
            $invoice = $payment->getData('invoice');
        } else {
            $invoice = $this->_registry->registry('current_invoice');
        }

        if (!is_null($invoice)) {
            if ($invoice->getTransactionId() != '') {
                $realTransactionId = $invoice->getTransactionId();
            }

            if ($this->getConfigData('send_line_items')) {
                $this->gateway()->setLineItems($invoice->getAllItems());
            }
        } elseif ($this->getConfigData('send_line_items')) {
            $this->gateway()->setLineItems($payment->getOrder()->getAllVisibleItems());
        }

        /**
         * Process transaction and results
         */
        $this->resyncStoredCard($payment);

        $this->beforeCapture($payment, $amount);
        $response = $this->gateway()->capture($payment, $amount, $realTransactionId);
        $this->afterCapture($payment, $amount, $response);

        if ($response->getData('is_fraud') === true) {
            $payment->setIsTransactionPending(true)
                    ->setIsFraudDetected(true)
                    ->setTransactionAdditionalInfo('is_transaction_fraud', true);
        } elseif ($this->gateway()->getHaveAuthorized() === false) {
            $payment->getOrder()->setStatus($this->getConfigData('order_status'))
                                      ->setExtOrderId(sprintf(
                                          '%s:%s',
                                          $response->getTransactionId(),
                                          $response->getAuthCode()
                                      ));
        }

        if ($response->getData('is_fraud') !== true) {
            $payment->setIsTransactionClosed(1);
        }

        $payment->setTransactionId($response->getData('transaction_id'))
                ->setAdditionalInformation(array_merge($payment->getAdditionalInformation(), $response->getData()));

        $this->getCard()->updateLastUse()->save();

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Refund a transaction
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->log(sprintf('refund(%s %s, %s)', get_class($payment), $payment->getId(), $amount));

        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /**
         * Grab transaction ID from the order
         */
        $transactionId = explode(':', $payment->getOrder()->getExtOrderId());

        $this->gateway()->setTransactionId($transactionId[0]);

        /**
         * Grab transaction ID from the invoice in case partial invoicing.
         */
        $realTransactionId    = null;
        $creditmemo           = $payment->getData('creditmemo');
        if (!is_null($creditmemo)) {
            if ($creditmemo->getData('invoice')->getTransactionId() != '') {
                $realTransactionId = $creditmemo->getData('invoice')->getTransactionId();
            }

            if ($this->getConfigData('send_line_items')) {
                $this->gateway()->setLineItems($creditmemo->getAllItems());
            }
        } elseif ($this->getConfigData('send_line_items')) {
            $this->gateway()->setLineItems($payment->getOrder()->getAllVisibleItems());
        }

        /**
         * Process transaction and results
         */
        $this->beforeRefund($payment, $amount);
        $response = $this->gateway()->refund($payment, $amount, $realTransactionId);
        $this->afterRefund($payment, $amount, $response);

        $payment->setIsTransactionClosed(1)
                ->setAdditionalInformation(array_merge($payment->getAdditionalInformation(), $response->getData()));

        $this->getCard()->updateLastUse()->save();

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Void a payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->log(sprintf('void(%s %s)', get_class($payment), $payment->getId()));

        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $this->loadOrCreateCard($payment);

        /**
         * Grab transaction ID from the order
         */
        $transactionId = explode(':', $payment->getOrder()->getExtOrderId());

        $this->gateway()->setTransactionId($transactionId[0]);

        /**
         * Process transaction and results
         */
        $this->beforeVoid($payment);
        $response = $this->gateway()->void($payment);
        $this->afterVoid($payment, $response);

        if ($response->getData('transaction_id') != '' && $response->getData('transaction_id') != '0') {
            $transactionId = $response->getData('transaction_id');
        } else {
            $transactionId = $transactionId[0].'-2';
        }

        $payment->getOrder()->setExtOrderId($transactionId);

        $payment->getOrder()->addStatusToHistory(
            false,
            __('Voided transaction ID "%1"', $this->gateway()->getTransactionId()),
            false
        );

        $payment->setTransactionId($transactionId)
                ->setAdditionalInformation(array_merge($payment->getAdditionalInformation(), $response->getData()))
                ->setShouldCloseParentTransaction(1)
                ->setIsTransactionClosed(1)
                ->save();

        $this->getCard()->updateLastUse()->save();

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Cancel a payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->log(sprintf('cancel(%s %s)', get_class($payment), $payment->getId()));

        return $this->void($payment);
    }

    /**
     * Fetch transaction info -- fraud detection
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $this->log('fetchTransactionInfo('.$transactionId.')');

        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $this->loadOrCreateCard($payment);

        /**
         * Process transaction and results
         */
        $this->beforeFraudUpdate($payment, $transactionId);
        $response = $this->gateway()->fraudUpdate($payment, $transactionId);
        $this->afterFraudUpdate($payment, $transactionId, $response);

        if ($response->getData('is_approved')) {
            $transaction = $payment->getTransaction($transactionId);
            $transaction->setAdditionalInformation('is_transaction_fraud', false);

            $payment->setIsTransactionApproved(true);
        } elseif ($response->getData('is_denied')) {
            $payment->setIsTransactionDenied(true);
        }

        $this->log(json_encode($response->getData()));

        return parent::fetchTransactionInfo($payment, $transactionId);
    }

    /**
     * Given the current object/payment, load the paying card, or create
     * one if none exists.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return Card
     * @throws \Magento\Framework\Exception\PaymentException
     */
    protected function loadOrCreateCard(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->log(sprintf('loadOrCreateCard(%s %s)', get_class($payment), $payment->getId()));

        if (!is_null($this->getCard())) {
            $this->setCard($this->getCard());

            return $this->getCard();
        } elseif ($payment->hasData('tokenbase_id') && $payment->getData('tokenbase_id')) {
            return $this->loadAndSetCard($payment->getData('tokenbase_id'));
        } elseif ($this->paymentContainsCard($payment) === true) {
            /** @var \ParadoxLabs\TokenBase\Model\Card $card */
            $card = $this->cardFactory->create();
            $card->setMethod($this->_code)
                 ->setMethodInstance($this)
                 ->setCustomer($this->getCustomer(), $payment)
                 ->importPaymentInfo($payment);

            if ($payment->getOrder()) {
                /** @var \Magento\Sales\Model\Order\Address $billingAddress */
                $billingAddress     = $payment->getOrder()->getBillingAddress();
                $billingAddressData = $billingAddress->getData();

                // AddressInterface requires an array for street
                $billingAddressData['street'] = explode("\n", $billingAddressData['street']);

                /** @var \ParadoxLabs\TokenBase\Helper\Address $addressHelper */
                // Instantiated in this way to avoid session instance except when absolutely necessary
                $addressHelper      = $this->addressHelperFactory->create();

                /** @var \Magento\Customer\Api\Data\AddressInterface $billingAddress */
                $billingAddress     = $addressHelper->buildAddressFromInput($billingAddressData);

                $card->setAddress($billingAddress);
            } else {
                throw new \Magento\Framework\Exception\PaymentException(
                    __('Could not find billing address.')
                );
            }

            $card->save();

            $this->setCard($card);

            return $card;
        }

        /**
         * This error will be thrown if we were unable to load a card and had no data to create one.
         */
        $this->log(sprintf('Invalid payment data provided. Please check the form and try again.'));

        throw new \Magento\Framework\Exception\PaymentException(
            __('Invalid payment data provided. Please check the form and try again.')
        );
    }

    /**
     * Return boolean whether given payment object includes new card info.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    protected function paymentContainsCard(\Magento\Payment\Model\InfoInterface $payment)
    {
        if ($payment->hasData('cc_number') && $payment->hasData('cc_exp_year') && $payment->hasData('cc_exp_month')) {
            return true;
        }

        return false;
    }

    /**
     * Resync billing address et al. before auth/capture.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    protected function resyncStoredCard(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->log(sprintf('resyncStoredCard(%s %s)', get_class($payment), $payment->getId()));

        if ($this->getCard() instanceof \ParadoxLabs\TokenBase\Model\Card && $this->getCard()->getId() > 0) {
            $haveChanges = false;

            /**
             * Any changes that we can see? Check the payment info and main address fields.
             */
            if ($this->getCard()->getOrigData('additional') != null
                && $this->getCard()->getOrigData('additional') != $this->getCard()->getData('additional')) {
                $haveChanges = true;
            }

            if ($payment->getOrder()) {
                $address = $payment->getOrder()->getBillingAddress();
            } elseif ($payment->getData('billing_address')) {
                $address = $payment->getData('billing_address');
            }

            if (isset($address) && $address instanceof \Magento\Customer\Model\Address\AbstractAddress) {
                $fields = array(
                    'firstname',
                    'lastname',
                    'company',
                    'street',
                    'city',
                    'country_id',
                    'region',
                    'region_id',
                    'postcode',
                );

                foreach ($fields as $field) {
                    if ($this->getCard()->getAddress($field) != $address->getData($field)) {
                        $this->getCard()->setAddress($address);

                        $haveChanges = true;
                        break;
                    }
                }
            }

            if ($haveChanges === true) {
                if ($this->hasData('info_instance') !== true) {
                    $this->setInfoInstance($payment);
                }

                $this->getCard()->setMethodInstance($this);
                $this->getCard()->setInfoInstance($payment);

                $this->getCard()->save();
            }
        }

        return $this;
    }

    /**
     * Write log message for this payment method
     *
     * @param $message
     * @return $this
     */
    protected function log($message)
    {
        $this->helper->log($this->_code, $message);

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return void
     */
    protected function beforeAuthorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Response $response
     * @return void
     */
    protected function afterAuthorize(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount,
        \ParadoxLabs\TokenBase\Model\Gateway\Response $response
    ) {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return void
     */
    protected function beforeCapture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Response $response
     * @return void
     */
    protected function afterCapture(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount,
        \ParadoxLabs\TokenBase\Model\Gateway\Response $response
    ) {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return void
     */
    protected function beforeRefund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Response $response
     * @return void
     */
    protected function afterRefund(
        \Magento\Payment\Model\InfoInterface $payment,
        $amount,
        \ParadoxLabs\TokenBase\Model\Gateway\Response $response
    ) {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return void
     */
    protected function beforeVoid(\Magento\Payment\Model\InfoInterface $payment)
    {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Response $response
     * @return void
     */
    protected function afterVoid(
        \Magento\Payment\Model\InfoInterface $payment,
        \ParadoxLabs\TokenBase\Model\Gateway\Response $response
    ) {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return void
     */
    protected function beforeFraudUpdate(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Response $response
     * @return void
     */
    protected function afterFraudUpdate(
        \Magento\Payment\Model\InfoInterface $payment,
        $transactionId,
        \ParadoxLabs\TokenBase\Model\Gateway\Response $response
    ) {
    }
}
