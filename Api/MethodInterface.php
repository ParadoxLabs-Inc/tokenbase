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
namespace ParadoxLabs\TokenBase\Api;

/**
 * Common actions and behavior for TokenBase payment methods
 *
 * @api
 */
interface MethodInterface
{
    /**
     * Get the current customer; fetch from session if necessary.
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer();

    /**
     * Set the customer to use for payment/card operations.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return $this
     */
    public function setCustomer(\Magento\Customer\Model\Customer $customer);

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
    public function setCard(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card);
}
