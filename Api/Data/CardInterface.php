<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */
namespace ParadoxLabs\TokenBase\Api\Data;

/**
 * Payment record storage
 *
 * @api
 */
interface CardInterface
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
     * @param \ParadoxLabs\TokenBase\Api\MethodInterface $method
     * @return $this
     */
    public function setMethodInstance(\ParadoxLabs\TokenBase\Api\MethodInterface $method);

    /**
     * Get the arbitrary method instance.
     *
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface Gateway-specific payment method
     * @throws \Magento\Framework\Exception\LocalizedException
     */
//    public function getMethodInstance();

    /**
     * Get the arbitrary type instance for this card.
     * Response will extend \ParadoxLabs\TokenBase\Model\Card.
     *
     * @return \ParadoxLabs\TokenBase\Model\Card|$this
     */
//    public function getTypeInstance();

    /**
     * Get the customer object (if any) for the card.
     *
     * @return \Magento\Customer\Model\Customer
     */
//    public function getCustomer();

    /**
     * Set the customer account (if any) for the card.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Payment\Model\InfoInterface|null $payment
     * @return $this
     */
    public function setCustomer(
        \Magento\Customer\Model\Customer $customer,
        \Magento\Payment\Model\InfoInterface $payment = null
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
     * Load card by security hash.
     *
     * @param $hash
     * @return $this
     */
    public function loadByHash($hash);

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
     * or a single parameter (associative array) to overwrite all data.
     *
     * @param string $key
     * @param string|null $value
     * @return $this
     */
    public function setAdditional($key, $value = null);

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
     * Get payment info instance (if any)
     *
     * @return \Magento\Payment\Model\InfoInterface|null
     */
//    public function getInfoInstance();

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
     * @return \Magento\Framework\Api\ExtensionAttributesInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\Framework\Api\ExtensionAttributesInterface $extensionAttributes
    );
}
