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
namespace ParadoxLabs\TokenBase\Api;

/**
 * Common API gateway methods, logging, exceptions, etc.
 *
 * @api
 */
interface GatewayInterface
{
    /**
     * Initialize the gateway. Input is taken as an array for greater flexibility.
     *
     * @param array $parameters
     * @return $this
     */
    public function init(array $parameters);

    /**
     * Has the gateway been initialized/configured?
     *
     * @return bool
     */
    public function isInitialized();

    /**
     * Undo initialization
     *
     * @return $this
     */
    public function reset();

    /**
     * Set the API parameters back to defaults, clearing any runtime values.
     *
     * @return $this
     */
    public function clearParameters();

    /**
     * Set a parameter.
     *
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setParameter($key, $val);

    /**
     * Get parameters. Debugging purposes.
     *
     * Implementation should mask or erase any confidential data from the response.
     * Card number, CVV, and password should never be logged in full.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Get a single parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParameter($key, $default = '');

    /**
     * Check whether parameter exists
     *
     * @param string $key
     * @return bool
     */
    public function hasParameter($key);

    /**
     * Get the last response value.
     *
     * @return string
     */
    public function getLastResponse();

    /**
     * Add line items, to be sent with relevant transactions.
     * Input should be a collection of items.
     *
     * @param array $items
     * @return $this
     */
    public function setLineItems($items);

    /**
     * These should be implemented by the child gateway.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return $this
     */
    public function setCard(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card);

    /**
     * Return the card set on the gateway (if any).
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     */
    public function getCard();

    /**
     * Set authorization code for the next transaction
     *
     * @param string $authCode
     * @return $this
     */
    public function setAuthCode($authCode);

    /**
     * Have we already authorized? Used for certain capture cases.
     *
     * @return bool
     */
    public function getHaveAuthorized();

    /**
     * Set haveAuthorized state for next capture.
     *
     * @param $haveAuthorized
     * @return $this
     */
    public function setHaveAuthorized($haveAuthorized);

    /**
     * Get transaction ID.
     *
     * @return string
     */
    public function getTransactionId();

    /**
     * Set prior transaction ID for next transaction.
     *
     * @param $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);

    /**
     * Run an auth transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /**
     * Run a capture transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a refund transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a void transaction for the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment, $transactionId = null);

    /**
     * Fetch a transaction status update
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    public function fraudUpdate(\Magento\Payment\Model\InfoInterface $payment, $transactionId);
}
