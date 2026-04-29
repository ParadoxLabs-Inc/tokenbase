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

namespace ParadoxLabs\TokenBase\Model;

use Magento\Payment\Model\Info;
use ParadoxLabs\TokenBase\Api\GatewayInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Api\MethodInterface;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Gateway\Response;
use Throwable;

/**
 * Common actions and behavior for TokenBase payment methods
 */
abstract class AbstractMethod extends DataObject implements MethodInterface
{
    /**
     * @var Info
     */
    protected $infoInstance;

    /**
     * @var CustomerInterface|null
     */
    protected $customer;

    /**
     * @var Card
     */
    protected $card;

    /**
     * @param Repository $transactionRepository
     * @param Data $helper
     * @param AbstractGateway $gateway
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory
     * @param CardRepositoryInterface $cardRepository
     * @param Address $addressHelper *Proxy
     * @param ConfigInterface $config
     * @param Registry $registry
     * @param string $methodCode
     * @param array $data
     * @throws CommandException
     */
    public function __construct(
        protected readonly Repository $transactionRepository,
        protected readonly Data $helper,
        protected readonly AbstractGateway $gateway,
        protected readonly CardInterfaceFactory $cardFactory,
        protected readonly CardRepositoryInterface $cardRepository,
        protected readonly Address $addressHelper,
        protected readonly ConfigInterface $config,
        protected readonly Registry $registry,
        protected readonly string $methodCode = '',
        array $data = []
    ) {
        if (empty($this->methodCode)) {
            throw new CommandException(__("Missing argument 'methodCode'"));
        }

        $this->setStore($this->helper->getCurrentStoreId());

        parent::__construct(
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
        if ($storeId instanceof ScopeInterface) {
            $storeId = $storeId->getId();
        }

        $this->setData('store', (int)$storeId);

        $this->gateway->reset();

        return $this;
    }

    /**
     * Set the customer to use for payment/card operations.
     *
     * @param CustomerInterface $customer
     * @return $this
     */
    public function setCustomer(CustomerInterface $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get the current customer; fetch from session if necessary.
     *
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        if ($this->customer === null || $this->customer->getId() < 1) {
            $this->setCustomer($this->helper->getCurrentCustomer());
        }

        return $this->customer;
    }

    /**
     * Get the given key from payment method configuration.
     *
     * @param string $key
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigData($key, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getData('store');
        }

        return $this->config->getValue($key, $storeId);
    }

    /**
     * @param InfoInterface $info
     * @return $this
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->infoInstance = $info;

        return $this;
    }

    /**
     * @return InfoInterface
     */
    public function getInfoInstance()
    {
        return $this->infoInstance;
    }

    /**
     * Initialize/return the API gateway class.
     *
     * @return GatewayInterface
     * @api
     */
    public function gateway()
    {
        if ($this->gateway->isInitialized() !== true) {
            $this->gateway->init([
                'login' => $this->getConfigData('login'),
                'password' => $this->getConfigData('trans_key'),
                'secret_key' => $this->getConfigData('secret_key'),
                'test_mode' => $this->getConfigData('test'),
                'verify_ssl' => $this->getConfigData('verify_ssl'),
            ]);
        }

        return $this->gateway;
    }

    /**
     * Load the given card by ID, authenticate, and store with the object.
     *
     * @param int|string $cardId
     * @param bool $byHash
     * @return CardInterface
     * @throws CommandException
     */
    public function loadAndSetCard($cardId, $byHash = false)
    {
        $this->log(sprintf('loadAndSetCard(%s, %s)', $cardId, var_export($byHash, true)));

        try {
            $card = $this->cardRepository->getById($cardId);

            $isOrder      = $this->getInfoInstance() instanceof OrderPayment;
            $isQuote      = $this->getInfoInstance() instanceof QuotePayment;
            $orderMatches = $isOrder && $card->getCustomerId() == $this->getInfoInstance()->getOrder()->getCustomerId();
            $quoteMatches = $isQuote && $card->getCustomerId() == $this->getInfoInstance()->getQuote()->getCustomerId();

            if ($card
                && $card->getId() > 0
                && ($byHash === false || $card->getHash() == $cardId)
                && $card->getMethod() === $this->methodCode
                && (empty($card->getCustomerId())
                    || $orderMatches
                    || $quoteMatches
                    || ($isOrder === false && $isQuote === false))) {
                $card->setMethodInstance($this);
                $this->setCard($card);

                return $this->getCard();
            }
        } catch (StateException $e) {
            $this->log($e->getMessage());
            throw $e;
        } catch (Throwable) {
            // Any error is inability to load card -- handle same as auth failure.
        }

        /**
         * This error will be thrown if the card does not exist OR if we don't have permission to use it.
         */
        $this->log(sprintf('Unable to load payment data. Please check the form and try again.'));

        throw new CommandException(
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
     * @param CardInterface $card
     * @return $this
     */
    public function setCard(CardInterface $card)
    {
        $this->log(sprintf('setCard(%s)', $card->getId()));

        /** @var Card $card */
        $card = $card->getTypeInstance();
        $card->setMethodInstance($this);

        if ($this->getInfoInstance() instanceof InfoInterface) {
            $card->setInfoInstance($this->getInfoInstance());
        } else {
            $this->setInfoInstance($card->getInfoInstance());
        }

        $this->card = $card;

        $this->gateway()->setCard($card);

        $this->getInfoInstance()->setData('tokenbase_id', $card->getId())
             ->setData('cc_type', $card->getType())
             ->setData('cc_last_4', $card->getAdditional('cc_last4'))
             ->setData('cc_exp_month', $card->getAdditional('cc_exp_month'))
             ->setData('cc_exp_year', $card->getAdditional('cc_exp_year'));

        if ($this->getConfigData('can_store_bin') == 1) {
            $this->getInfoInstance()->setAdditionalInformation('cc_bin', $card->getAdditional('cc_bin'));
        }

        return $this;
    }

    /**
     * Run an 'order' transaction
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function order(InfoInterface $payment, $amount)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('order(%s %s, %s)', $payment::class, $payment->getId(), $amount));

        $this->loadOrCreateCard($payment);
        $this->resyncStoredCard($payment);

        /**
         * There is no transaction ID, no transaction info, and no transaction. So...yeah.
         */
        $paymentData = [
            'profile_id' => $this->getCard()->getProfileId(),
            'payment_id' => $this->getCard()->getPaymentId(),
        ];

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $paymentData
        );

        if ($payment->getOrder()->getStatus() != $this->getConfigData('order_status')) {
            $payment->getOrder()->setStatus($this->getConfigData('order_status'));
        }

        $payment->setAdditionalInformation(
            array_replace_recursive($payment->getAdditionalInformation(), $paymentData)
        );
        $payment->setIsTransactionClosed(false);

        $this->getCard()->updateLastUse();
        $this->card = $this->cardRepository->save($this->getCard());

        $this->log(json_encode($paymentData));

        return $this;
    }

    /**
     * Authorize a transaction
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('authorize(%s %s, %s)', $payment::class, $payment->getId(), $amount));

        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /**
         * Check for existing authorization, and void it if so.
         */
        $priorAuth = $payment->getAuthorizationTransaction();
        if ($priorAuth != false) {
            $parentTransactionId = $payment->getParentTransactionId();
            $payment->setData('parent_transaction_id', $priorAuth->getTxnId());

            $this->void($payment);

            $payment->setData('parent_transaction_id', $parentTransactionId);

            $this->getCard()->setData('no_validate', true);
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

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response->getData()
        );

        if ($response->getData('is_fraud') === true) {
            $payment->setIsTransactionPending(true)
                    ->setIsFraudDetected(true)
                    ->setTransactionAdditionalInfo('is_transaction_fraud', true);
        } elseif ($payment->getOrder()->getStatus() != $this->getConfigData('order_status')) {
            $payment->getOrder()->setStatus($this->getConfigData('order_status'));
        }

        $payment->setTransactionId($this->getValidTransactionId($payment, $response->getData('transaction_id')))
                ->setAdditionalInformation(
                    array_replace_recursive($payment->getAdditionalInformation(), $response->getData())
                )
                ->setIsTransactionClosed(false);

        $this->getCard()->updateLastUse();
        $this->getCard()->setData('no_sync', true);
        $this->card = $this->cardRepository->save($this->getCard());

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Capture a transaction [authorize if necessary]
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('capture(%s %s, %s)', $payment::class, $payment->getId(), $amount));

        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /**
         * Check for existing auth code.
         */
        $authTxn = $payment->getAuthorizationTransaction();
        if ($authTxn instanceof TransactionInterface
            && $authTxn->getIsClosed() == 0
            && !empty($authTxn->getTxnId())
            && !str_ends_with((string)$authTxn->getTxnId(), '-auth')) {
            $this->gateway()->setHaveAuthorized(true);

            $authTxnInfo = $authTxn->getAdditionalInformation(
                Transaction::RAW_DETAILS
            );

            if (is_array($authTxnInfo) && isset($authTxnInfo['auth_code'])) {
                $this->gateway()->setAuthCode($authTxnInfo['auth_code']);
            }

            if ($payment->getParentTransactionId() != '') {
                $this->gateway()->setTransactionId($payment->getParentTransactionId());
            } else {
                $this->gateway()->setTransactionId($authTxn->getTxnId());
            }

            $this->getCard()->setData('no_validate', true);
        } else {
            $this->gateway()->setHaveAuthorized(false);
        }

        /**
         * Grab transaction ID from the invoice in case partial invoicing.
         */
        $this->captureGetInvoiceInfo($payment);

        /**
         * Process transaction and results
         */
        $this->resyncStoredCard($payment);

        $this->beforeCapture($payment, $amount);
        $response = $this->gateway()->capture($payment, $amount);
        $this->afterCapture($payment, $amount, $response);

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response->getData()
        );

        if ($response->getData('is_fraud') === true) {
            $payment->setIsTransactionPending(true)
                    ->setIsFraudDetected(true)
                    ->setTransactionAdditionalInfo('is_transaction_fraud', true);
        } elseif ($this->gateway()->getHaveAuthorized() === false
            && $payment->getOrder()->getStatus() != $this->getConfigData('order_status')) {
            $payment->getOrder()->setStatus($this->getConfigData('order_status'));
        }

        // Set transaction id iff different from the last txn id -- use Magento's generated ID otherwise.
        if ($payment->getParentTransactionId() != $response->getTransactionId()) {
            $payment->setTransactionId($this->getValidTransactionId($payment, $response->getTransactionId()));
        }

        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(1);

        $payment->setAdditionalInformation(
            array_replace_recursive($payment->getAdditionalInformation(), $response->getData())
        );

        $this->getCard()->updateLastUse();
        $this->getCard()->setData('no_sync', true);
        $this->card = $this->cardRepository->save($this->getCard());

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Refund a transaction
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('refund(%s %s, %s)', $payment::class, $payment->getId(), $amount));

        $this->loadOrCreateCard($payment);

        if ($amount <= 0) {
            return $this;
        }

        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getData('creditmemo');

        /**
         * Grab transaction ID from the order
         */
        if ($payment->getParentTransactionId() != '') {
            $txnId = $payment->getParentTransactionId();
        } else {
            if ($creditmemo && $creditmemo->getInvoice()->getTransactionId() != '') {
                $txnId = $creditmemo->getInvoice()->getTransactionId();
            } else {
                $txnId = $payment->getLastTransId();
            }
        }

        $transactionId = substr(
            (string)$txnId,
            0,
            strcspn((string)$txnId, '-')
        );

        $this->gateway()->setTransactionId($transactionId);

        /**
         * Add line items.
         */
        if ($this->getConfigData('send_line_items')) {
            if ($creditmemo) {
                $this->gateway()->setLineItems($creditmemo->getAllItems());
            } else {
                $this->gateway()->setLineItems($payment->getOrder()->getAllVisibleItems());
            }
        }

        /**
         * Process transaction and results
         */
        $this->beforeRefund($payment, $amount);
        $response = $this->gateway()->refund($payment, $amount);
        $this->afterRefund($payment, $amount, $response);

        $payment->setAdditionalInformation(
            array_replace_recursive($payment->getAdditionalInformation(), $response->getData())
        );

        $payment->setIsTransactionClosed(true);

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response->getData()
        );

        if ($response->getTransactionId() != '' && $response->getTransactionId() != $transactionId) {
            $payment->setTransactionId($this->getValidTransactionId($payment, $response->getTransactionId()));
        } else {
            $payment->setTransactionId($this->getValidTransactionId($payment, $transactionId . '-refund'));
        }

        if ($creditmemo
            && $creditmemo->getInvoice()
            && $creditmemo->getInvoice()->getBaseTotalRefunded() < $creditmemo->getInvoice()->getBaseGrandTotal()) {
            $payment->setShouldCloseParentTransaction(0);
        } else {
            $payment->setShouldCloseParentTransaction(1);
        }

        $this->getCard()->updateLastUse();
        $this->getCard()->setData('no_sync', true);
        $this->card = $this->cardRepository->save($this->getCard());

        $this->log(json_encode($response->getData()));

        return $this;
    }

    /**
     * Void a payment
     *
     * @param InfoInterface $payment
     * @return $this
     */
    public function void(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('void(%s %s)', $payment::class, $payment->getId()));

        try {
            $this->loadOrCreateCard($payment);

            /**
             * Short-circuit if we don't have a real transaction ID. That means reauth not working or failed.
             * Not doing this can result in voiding a valid (potentially already-captured) transaction. Bad.
             */
            if (str_contains((string)$payment->getParentTransactionId(), '-auth')) {
                $this->log(
                    sprintf(
                        'Skipping void; do not have a valid auth transaction ID. (%s)',
                        $payment->getParentTransactionId()
                    )
                );

                return $this;
            }

            /**
             * Grab transaction ID from the order
             */
            $this->gateway()->setTransactionId($payment->getParentTransactionId());

            /**
             * Process transaction and results
             */
            $this->beforeVoid($payment);
            $response = $this->gateway()->void($payment);
            $this->afterVoid($payment, $response);

            $payment->setAdditionalInformation(
                array_replace_recursive($payment->getAdditionalInformation(), $response->getData())
            );

            $payment->setTransactionAdditionalInfo(
                Transaction::RAW_DETAILS,
                $response->getData()
            );

            $this->log(json_encode($response->getData()));
        } catch (Throwable $exception) {
            $this->log($exception->getMessage());
            // Ignore void errors, let Magento proceed like it happened. Most likely the auth already expired.
        }

        $payment->setShouldCloseParentTransaction(1)
                ->setIsTransactionClosed(true);

        if ($this->getCard() instanceof CardInterface) {
            $this->getCard()->updateLastUse();
            $this->getCard()->setData('no_sync', true);
            $this->card = $this->cardRepository->save($this->getCard());
        }

        return $this;
    }

    /**
     * Cancel a payment
     *
     * @param InfoInterface $payment
     * @return $this
     */
    public function cancel(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('cancel(%s %s)', $payment::class, $payment->getId()));

        return $this->void($payment);
    }

    /**
     * Fetch transaction info -- fraud detection
     *
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        $this->log('fetchTransactionInfo(' . $transactionId . ')');

        /** @var OrderPayment $payment */

        $this->loadOrCreateCard($payment);

        /**
         * Process transaction and results
         */
        $this->beforeFraudUpdate($payment, $transactionId);
        $response = $this->gateway()->fraudUpdate($payment, $transactionId);
        $this->afterFraudUpdate($payment, $transactionId, $response);

        if ($response->getData('is_approved')) {
            $payment->setData('parent_transaction_id', $transactionId);

            $transaction = $payment->getAuthorizationTransaction();
            if ($transaction instanceof TransactionInterface) {
                $transaction->setAdditionalInformation('is_transaction_fraud', false);
            }

            $payment->setIsTransactionApproved(true);
        } elseif ($response->getData('is_denied')) {
            $payment->setIsTransactionDenied(true);
        }

        $this->log(json_encode($response->getData()));

        return $response->getData();
    }

    /**
     * Get invoice info on capture.
     *
     * @param InfoInterface $payment
     * @return $this
     */
    protected function captureGetInvoiceInfo(InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        /** @var OrderPayment $payment */
        $invoice = null;

        if ($payment->hasData('invoice')
            && $payment->getData('invoice') instanceof Invoice) {
            $invoice = $payment->getData('invoice');
        } else {
            $invoice = $this->registry->registry('current_invoice');
        }

        if ($invoice !== null) {
            if ($invoice->getTransactionId() != '') {
                $this->gateway()->setTransactionId($invoice->getTransactionId());
            }

            if ($this->getConfigData('send_line_items')) {
                $this->gateway()->setLineItems($invoice->getAllItems());
            }
        } elseif ($this->getConfigData('send_line_items')) {
            $this->gateway()->setLineItems($payment->getOrder()->getAllVisibleItems());
        }

        return $this;
    }

    /**
     * We can't have two transactions with the same ID. Make sure that doesn't happen.
     *
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return string
     */
    protected function getValidTransactionId(InfoInterface $payment, $transactionId)
    {
        /** @var OrderPayment $payment */

        $baseId    = $transactionId;
        $increment = 1;

        /**
         * Try to load a transaction by ID, incrementing until we get one that does not exist.
         * will try txnId, txnId-1, txnId-2, etc.
         */
        do {
            $found = false;

            $transaction = $this->transactionRepository->getByTransactionId(
                $transactionId,
                $payment->getId(),
                $payment->getOrder()->getId()
            );

            if ($transaction !== false) {
                $found         = true;
                $transactionId = $baseId . '-' . ($increment++);
            }
        } while ($found == true);

        return $transactionId;
    }

    /**
     * Given the current object/payment, load the paying card, or create
     * one if none exists.
     *
     * @param InfoInterface $payment
     * @return CardInterface
     * @throws CommandException
     */
    protected function loadOrCreateCard(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('loadOrCreateCard(%s %s)', $payment::class, $payment->getId()));

        if ($this->getCard() !== null) {
            $this->setCard($this->getCard());

            return $this->getCard();
        }

        if ($payment->getData('tokenbase_card') instanceof CardInterface) {
            $this->setCard($payment->getData('tokenbase_card'));

            return $this->getCard();
        }

        if ($payment->hasData('tokenbase_id') && $payment->getData('tokenbase_id')) {
            return $this->loadAndSetCard($payment->getData('tokenbase_id'));
        }

        if ($payment->hasAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH)) {
            try {
                return $this->loadAndSetCard(
                    $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH),
                    true
                );
            } catch (CommandException) {
                // Unable to load TokenBase card by Vault hash; fall through
            }
        }

        if ($this->paymentContainsCard($payment) === true) {
            /** @var Card $card */
            $card = $this->cardFactory->create();
            $card->setMethod($this->methodCode)
                 ->setMethodInstance($this);

            $card = $card->getTypeInstance();

            $card->setCustomer($this->getCustomer(), $payment)
                 ->importPaymentInfo($payment);

            if ($payment->getOrder()) {
                /** @var \Magento\Sales\Model\Order\Address $billingAddress */
                $billingAddress     = $payment->getOrder()->getBillingAddress();
                $billingAddressData = (array)$billingAddress->getData();

                // AddressInterface requires an array for street
                $billingAddressData['street'] = explode(
                    "\n",
                    str_replace("\r", '', (string)($billingAddressData['street'] ?? ''))
                );

                /** @var AddressInterface $billingAddress */
                $billingAddress = $this->addressHelper->buildAddressFromInput($billingAddressData);

                $card->setAddress($billingAddress);
            } else {
                throw new CommandException(
                    __('Could not find billing address.')
                );
            }

            $card = $this->cardRepository->save($card);

            $this->setCard($card);

            return $card;
        }

        /**
         * This error will be thrown if we were unable to load a card and had no data to create one.
         */
        $this->log(sprintf('Invalid payment data provided. Please check the form and try again.'));

        throw new CommandException(
            __('Invalid payment data provided. Please check the form and try again.')
        );
    }

    /**
     * Return boolean whether given payment object includes new card info.
     *
     * @param InfoInterface $payment
     * @return bool
     */
    protected function paymentContainsCard(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        if ($payment->hasData('cc_number') && $payment->hasData('cc_exp_year') && $payment->hasData('cc_exp_month')) {
            return true;
        }

        return false;
    }

    /**
     * Resync billing address et al. before auth/capture.
     *
     * @param InfoInterface $payment
     * @return $this
     */
    protected function resyncStoredCard(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('resyncStoredCard(%s %s)', $payment::class, $payment->getId()));

        if ($this->getCard() instanceof CardInterface
            && $this->getCard()->getId() > 0) {
            $haveChanges = false;

            /**
             * Any changes that we can see? Check the payment info and main address fields.
             */
            if ($this->getCard()->getOrigData('additional') !== null
                && $this->getCard()->getOrigData('additional') != $this->getCard()->getData('additional')) {
                $haveChanges = true;
            }

            if ($payment->getOrder()) {
                $address = $payment->getOrder()->getBillingAddress();
            } elseif ($payment->getData('billing_address')) {
                $address = $payment->getData('billing_address');
            }

            if (isset($address) && $address instanceof AddressModelInterface) {
                $fields = [
                    'firstname',
                    'lastname',
                    'company',
                    'street',
                    'city',
                    'country_id',
                    'region',
                    'region_id',
                    'postcode',
                    'telephone',
                    'prefix',
                    'middlename',
                    'suffix',
                ];

                foreach ($fields as $field) {
                    if ($this->getCard()->getAddress($field) != $address->getData($field)) {
                        $addrData = $address->getData();
                        $newAddr  = $this->addressHelper->buildAddressFromInput($addrData);
                        $this->getCard()->setAddress($newAddr);

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

                $this->card = $this->cardRepository->save($this->getCard());

                $this->registry->unregister('tokenbase_ensure_checkout_card_save');
                $this->registry->register('tokenbase_ensure_checkout_card_save', $this->getCard());
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
        $this->helper->log($this->methodCode, $message);

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return void
     */
    protected function beforeAuthorize(InfoInterface $payment, $amount)
    {
        $this->handleShippingAddress($payment);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @param Response $response
     * @return void
     */
    protected function afterAuthorize(
        InfoInterface $payment,
        $amount,
        Response $response
    ) {
        $this->storeTransactionStatuses($payment, $response);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return void
     */
    protected function beforeCapture(InfoInterface $payment, $amount)
    {
        $this->handleShippingAddress($payment);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @param Response $response
     * @return void
     */
    protected function afterCapture(
        InfoInterface $payment,
        $amount,
        Response $response
    ) {
        /** @var OrderPayment $payment */

        /**
         * If this is a pre-auth capture for less than the total value of the order,
         * try to reauthorize any remaining balance.
         */
        $outstanding = round($payment->getOrder()->getBaseTotalDue() - $amount, 4);
        if ($outstanding > 0) {
            $wasTransId   = $payment->getTransactionId();
            $wasParentId  = $payment->getParentTransactionId();
            $authResponse = null;
            $message      = false;

            if ((int)$this->getConfigData('reauthorize_partial_invoice') === 1) {
                try {
                    $this->log(sprintf('afterCapture(): Reauthorizing for %s', $outstanding));

                    $this->gateway()->clearParameters();
                    $this->gateway()->setCard($this->gateway()->getCard());
                    $this->handleShippingAddress($payment);
                    $this->gateway()->setHaveAuthorized(true);

                    $authResponse = $this->gateway()->authorize($payment, $outstanding);
                } catch (Throwable) {
                    // Reauth failed: Take no action
                    $this->log('afterCapture(): Reauthorization not successful. Continuing with original transaction.');
                }
            }

            /**
             * Even if the auth didn't go through, we need to create a new 'transaction'
             * so we can still do an online capture for the remainder.
             */
            if ($authResponse !== null) {
                $payment->setTransactionId(
                    $this->getValidTransactionId($payment, $authResponse->getTransactionId())
                );

                $payment->setTransactionAdditionalInfo(
                    Transaction::RAW_DETAILS,
                    $authResponse->getData()
                );

                $message = __(
                    'Reauthorized outstanding amount of %1.',
                    $payment->formatPrice($outstanding)
                );
            } else {
                $payment->setTransactionId(
                    $this->getValidTransactionId($payment, $response->getTransactionId() . '-auth')
                );
            }

            $payment->setData('parent_transaction_id', null);
            $payment->setIsTransactionClosed(false);

            $transaction = $payment->addTransaction(
                Transaction::TYPE_AUTH,
                $payment->getOrder(),
                false
            );

            if ($message !== null) {
                $payment->addTransactionCommentsToOrder($transaction, $message);
            }

            $payment->setTransactionId($wasTransId);
            $payment->setData('parent_transaction_id', $wasParentId);
        }

        $this->storeTransactionStatuses($payment, $response);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return void
     */
    protected function beforeRefund(InfoInterface $payment, $amount)
    {
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @param Response $response
     * @return void
     */
    protected function afterRefund(
        InfoInterface $payment,
        $amount,
        Response $response
    ) {
    }

    /**
     * @param InfoInterface $payment
     * @return void
     */
    protected function beforeVoid(InfoInterface $payment)
    {
    }

    /**
     * @param InfoInterface $payment
     * @param Response $response
     * @return void
     */
    protected function afterVoid(
        InfoInterface $payment,
        Response $response
    ) {
    }

    /**
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return void
     */
    protected function beforeFraudUpdate(InfoInterface $payment, $transactionId)
    {
    }

    /**
     * @param InfoInterface $payment
     * @param string $transactionId
     * @param Response $response
     * @return void
     */
    protected function afterFraudUpdate(
        InfoInterface $payment,
        $transactionId,
        Response $response
    ) {
    }

    /**
     * Set shipping address on the gateway before running the transaction.
     *
     * @param InfoInterface $payment
     * @return $this
     */
    protected function handleShippingAddress(InfoInterface $payment)
    {
        return $this;
    }

    /**
     * Store response statuses persistently.
     *
     * @param InfoInterface $payment
     * @param Response $response
     * @return InfoInterface
     */
    protected function storeTransactionStatuses(
        InfoInterface $payment,
        Response $response
    ) {
        return $payment;
    }

    /**
     * Retrieve payment method code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->methodCode;
    }

    /**
     * Get payment method code (for Vault)
     *
     * @return string
     */
    public function getProviderCode()
    {
        return $this->methodCode;
    }

    /**
     * Retrieve block type for method form generation
     *
     * @return string
     * @deprecated
     */
    public function getFormBlockType()
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return '';
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Store id getter
     *
     * @return int
     */
    public function getStore()
    {
        return $this->getData('store');
    }

    /**
     * @inheritdoc
     */
    public function canOrder()
    {
        return $this->canPerformCommand('order');
    }

    /**
     * @inheritdoc
     */
    public function canAuthorize()
    {
        return $this->canPerformCommand('authorize');
    }

    /**
     * @inheritdoc
     */
    public function canCapture()
    {
        return $this->canPerformCommand('capture');
    }

    /**
     * @inheritdoc
     */
    public function canCapturePartial()
    {
        return $this->canPerformCommand('capture_partial');
    }

    /**
     * @inheritdoc
     */
    public function canCaptureOnce()
    {
        return $this->canPerformCommand('capture_once');
    }

    /**
     * @inheritdoc
     */
    public function canRefund()
    {
        return $this->canPerformCommand('refund');
    }

    /**
     * @inheritdoc
     */
    public function canRefundPartialPerInvoice()
    {
        return $this->canPerformCommand('refund_partial_per_invoice');
    }

    /**
     * @inheritdoc
     */
    public function canVoid()
    {
        return $this->canPerformCommand('void');
    }

    /**
     * @inheritdoc
     */
    public function canUseInternal()
    {
        return (bool)$this->getConfiguredValue('can_use_internal');
    }

    /**
     * @inheritdoc
     */
    public function canUseCheckout()
    {
        return (bool)$this->getConfiguredValue('can_use_checkout');
    }

    /**
     * @inheritdoc
     */
    public function canEdit()
    {
        return (bool)$this->getConfiguredValue('can_edit');
    }

    /**
     * @inheritdoc
     */
    public function canFetchTransactionInfo()
    {
        return $this->canPerformCommand('fetch_transaction_info');
    }

    /**
     * @inheritdoc
     */
    public function canReviewPayment()
    {
        return $this->canPerformCommand('review_payment');
    }

    /**
     * @inheritdoc
     */
    public function isGateway()
    {
        return (bool)$this->getConfiguredValue('is_gateway');
    }

    /**
     * @inheritdoc
     */
    public function isOffline()
    {
        return (bool)$this->getConfiguredValue('is_offline');
    }

    /**
     * @inheritdoc
     */
    public function isInitializeNeeded()
    {
        return (bool)(int)$this->getConfiguredValue('can_initialize');
    }

    /**
     * @inheritdoc
     * @deprecated
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isActive($storeId = null)
    {
        return (bool)$this->getConfiguredValue('active', $storeId);
    }

    /**
     * @inheritdoc
     * @deprecated
     */
    public function canUseForCountry($country)
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return false;
    }

    /**
     * @inheritdoc
     * @deprecated
     */
    public function canUseForCurrency($currencyCode)
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return false;
    }

    /**
     * Whether payment command is supported and can be executed
     *
     * @param string $commandCode
     * @return bool
     */
    private function canPerformCommand($commandCode)
    {
        return (bool)$this->getConfiguredValue('can_' . $commandCode);
    }

    /**
     * Unifies configured value handling logic
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    private function getConfiguredValue($field, $storeId = null)
    {
        return $this->getConfigData($field, $storeId);
    }

    /**
     * Retrieve block type for display method information
     *
     * @return string
     * @deprecated
     */
    public function getInfoBlockType()
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return '';
    }

    /**
     * Validate payment method information object
     *
     * @return $this
     * @throws LocalizedException
     * @deprecated
     */
    public function validate()
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return $this;
    }

    /**
     * Attempt to accept a payment that us under review
     *
     * @param InfoInterface $payment
     * @return bool
     * @throws LocalizedException
     */
    public function acceptPayment(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('acceptPayment(%s %s)', $payment::class, $payment->getId()));

        return false;
    }

    /**
     * Attempt to deny a payment that us under review
     *
     * @param InfoInterface $payment
     * @return bool
     * @throws LocalizedException
     */
    public function denyPayment(InfoInterface $payment)
    {
        /** @var OrderPayment $payment */

        $this->log(sprintf('denyPayment(%s %s)', $payment::class, $payment->getId()));

        return false;
    }

    /**
     * Assign data to info model instance
     *
     * @param DataObject $data
     * @return $this
     * @deprecated
     */
    public function assignData(DataObject $data)
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return $this;
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated
     */
    public function initialize($paymentAction, $stateObject)
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return $this;
    }

    /**
     * Get config payment action url
     *
     * @return string
     * @deprecated
     */
    public function getConfigPaymentAction()
    {
        /**
         * Don't use this method. Get an Adapter instance instead.
         *
         * @see \Magento\Payment\Model\Method\Adapter
         */
        return '';
    }
}
