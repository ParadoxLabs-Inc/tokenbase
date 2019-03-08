<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Api;

/**
 * Interface CustomerCardRepositoryInterface
 *
 * @package ParadoxLabs\TokenBase\Api
 */
interface CustomerCardRepositoryInterface
{
    /**
     * Save card with extended objects.
     *
     * @param int $customerId The customer ID.
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveExtended(
        $customerId,
        \ParadoxLabs\TokenBase\Api\Data\CardInterface $card,
        \Magento\Customer\Api\Data\AddressInterface $address,
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
    );

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param int $customerId The customer ID.
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getByHash($customerId, $cardHash);

    /**
     * Retrieve cards matching the specified criteria.
     *
     * @param int $customerId The customer ID.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete card. Will accept hash only.
     *
     * @param int $customerId The customer ID.
     * @param string $cardHash
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function deleteByHash($customerId, $cardHash);
}
