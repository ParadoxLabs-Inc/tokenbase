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

namespace ParadoxLabs\TokenBase\Model\ResourceModel;

/**
 * CustomerCardRepository Class
 */
class CustomerCardRepository implements \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * GuestCardRepository constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder
    ) {
        $this->cardRepository = $cardRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
    }

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
    ) {
        // Validate original record so it can't be overwritten maliciously
        if ($card->getHash()) {
            $originalCard = $this->getByHash($customerId, $card->getHash());
            $this->validateCustomerCard($customerId, $originalCard);
        } elseif ($card->getId()) {
            $originalCard = $this->cardRepository->getById($card->getId());
            $this->validateCustomerCard($customerId, $originalCard);
        }

        $this->validateCustomerCard($customerId, $card);

        return $this->cardRepository->saveExtended($card, $address, $additional);
    }

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param int $customerId The customer ID.
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getByHash($customerId, $cardHash)
    {
        $card = $this->cardRepository->getByHash($cardHash);

        $this->validateCustomerCard($customerId, $card);

        return $card;
    }

    /**
     * Retrieve cards matching the specified criteria.
     *
     * @param int $customerId The customer ID.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        // Add mandatory filters to limit results to active cards belonging to the current user
        $customerFilter = $this->filterBuilder->setField('customer_id')
                                              ->setValue($customerId)
                                              ->setConditionType('eq')
                                              ->create();

        $activeFilter = $this->filterBuilder->setField('active')
                                            ->setValue(1)
                                            ->setConditionType('eq')
                                            ->create();

        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroups[] = $this->filterGroupBuilder->setFilters([$customerFilter])->create();
        $filterGroups[] = $this->filterGroupBuilder->setFilters([$activeFilter])->create();

        $searchCriteria->setFilterGroups($filterGroups);

        return $this->cardRepository->getList($searchCriteria);
    }

    /**
     * Delete card. Will accept hash only.
     *
     * @param int $customerId The customer ID.
     * @param string $cardHash
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function deleteByHash($customerId, $cardHash)
    {
        $card = $this->getByHash($customerId, $cardHash);

        return $this->cardRepository->delete($card);
    }

    /**
     * Do not allow guests to fetch or modify cards belonging to non-guests.
     *
     * @param int $customerId
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validateCustomerCard($customerId, \ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        if ((int)$card->getCustomerId() !== (int)$customerId
            || (int)$card->getActive() === 0) {
            throw new \Magento\Framework\Exception\InputException(__('You do not have permission for this action.'));
        }
    }
}
