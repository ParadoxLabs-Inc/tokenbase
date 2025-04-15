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
namespace ParadoxLabs\TokenBase\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Payment record storage
 *
 * @api
 */
interface CardInterface extends ExtensibleDataInterface, PaymentTokenInterface
{
    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $cardId
     * @return CardInterface
     */
    public function setId($cardId);

    /**
     * Set the method instance for this card. This is often necessary to route card data properly.
     *
     * @param \ParadoxLabs\TokenBase\Api\MethodInterface|\Magento\Payment\Model\MethodInterface $method
     * @return $this
     */
    public function setMethodInstance($method);

    /**
     * Set the customer account (if any) for the card.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Payment\Model\InfoInterface|null $payment
     * @return $this
     */
    public function setCustomer(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        ?\Magento\Payment\Model\InfoInterface $payment = null
    );

    /**
     * Set card payment data from a quote or order payment instance.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function importPaymentInfo(\Magento\Payment\Model\InfoInterface $payment);

    /**
     * Check whether customer has permission to use/modify this card.
     *
     * @param int $customerId
     * @return bool
     */
    public function hasOwner($customerId);

    /**
     * Check if card is connected to any pending orders.
     *
     * @return bool
     */
    public function isInUse();

    /**
     * Change last_use date to the current time.
     *
     * @return $this
     */
    public function updateLastUse();

    /**
     * Delete this card, or hide and queue for deletion after the refund period.
     *
     * @return $this
     */
    public function queueDeletion();

    /**
     * Get additional card data.
     * If $key is set, will return that value or null;
     * otherwise, will return an array of all additional date.
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function getAdditional($key = null);

    /**
     * Set additional card data.
     * Can pass in a key-value pair to set one value,
     * or a single parameter (associative array or CardAdditional instance) to overwrite all data.
     *
     * @param string|array|\ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $key
     * @param string|null $value
     * @return $this
     */
    public function setAdditional($key, $value = null);

    /**
     * Get additional card data, in object form. Used to expose keys to API.
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface
     */
    public function getAdditionalObject();

    /**
     * Get billing address or some part thereof.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getAddress($key = '');

    /**
     * Set the billing address for the card.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return $this
     */
    public function setAddress(\Magento\Customer\Api\Data\AddressInterface $address);

    /**
     * Return a customer address object containing the card address data.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function getAddressObject();

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail();

    /**
     * Set customer email
     *
     * @param string $email
     * @return $this
     */
    public function setCustomerEmail($email);

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer id
     *
     * @param int $id
     * @return $this
     */
    public function setCustomerId($id);

    /**
     * Get customer ip
     *
     * @return string
     */
    public function getCustomerIp();

    /**
     * Set customer ip
     *
     * @param string $ip
     * @return $this
     */
    public function setCustomerIp($ip);

    /**
     * Get profile id
     *
     * @return string
     */
    public function getProfileId();

    /**
     * Set profile id
     *
     * @param string $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId();

    /**
     * Set payment id
     *
     * @param string $paymentId
     * @return $this
     */
    public function setPaymentId($paymentId);

    /**
     * Get method code
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set method code
     *
     * @param string $method
     * @return $this
     */
    public function setMethod($method);

    /**
     * Get hash, generate if necessary
     *
     * @return string
     */
    public function getHash();

    /**
     * Set hash
     *
     * @param string $hash
     * @return $this
     */
    public function setHash($hash);

    /**
     * Get active
     *
     * @return string
     */
    public function getActive();

    /**
     * Set active
     *
     * @param int|bool $active
     * @return $this
     */
    public function setActive($active);

    /**
     * Get created at date
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at date
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at date
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set updated at date
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get last use date
     *
     * @return string
     */
    public function getLastUse();

    /**
     * Set last use date
     *
     * @param $lastUse
     * @return $this
     */
    public function setLastUse($lastUse);

    /**
     * Get expires
     *
     * @return string
     */
    public function getExpires();

    /**
     * Set expires
     *
     * @param string $expires
     * @return $this
     */
    public function setExpires($expires);

    /**
     * Set payment info instance
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function setInfoInstance(\Magento\Payment\Model\InfoInterface $payment);

    /**
     * Get card label (formatted number).
     *
     * @param bool $includeType
     * @return string|\Magento\Framework\Phrase
     */
    public function getLabel($includeType = true);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\ParadoxLabs\TokenBase\Api\Data\CardExtensionInterface $extensionAttributes);
}
