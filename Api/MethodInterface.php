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

namespace ParadoxLabs\TokenBase\Api;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\VaultPaymentInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;

/**
 * Common actions and behavior for TokenBase payment methods
 *
 * @api
 */
interface MethodInterface extends VaultPaymentInterface
{
    /**
     * Set the payment config scope and reinitialize the API
     *
     * @param int $storeId
     * @return $this
     */
    public function setStore($storeId);

    /**
     * Get the current customer; fetch from session if necessary.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer();

    /**
     * Set the customer to use for payment/card operations.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomer(CustomerInterface $customer);

    /**
     * Initialize/return the API gateway class.
     *
     * @return \ParadoxLabs\TokenBase\Api\GatewayInterface
     */
    public function gateway();

    /**
     * Get the current card
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     */
    public function getCard();

    /**
     * Set the current payment card
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return $this
     */
    public function setCard(CardInterface $card);

    /**
     * @return \Magento\Payment\Model\Info
     */
    public function getInfoInstance();

    /**
     * @param \Magento\Payment\Model\InfoInterface $info
     * @return $this
     */
    public function setInfoInstance(InfoInterface $info);

    /**
     * Order payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     *
     */
    public function order(InfoInterface $payment, $amount);

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     *
     */
    public function authorize(InfoInterface $payment, $amount);

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     *
     */
    public function capture(InfoInterface $payment, $amount);

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     *
     */
    public function refund(InfoInterface $payment, $amount);

    /**
     * Cancel payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     *
     */
    public function cancel(InfoInterface $payment);

    /**
     * Void payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     *
     */
    public function void(InfoInterface $payment);

    /**
     * Fetch transaction info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId);

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null);
}
