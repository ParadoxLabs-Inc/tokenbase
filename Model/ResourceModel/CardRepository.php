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

namespace ParadoxLabs\TokenBase\Model\ResourceModel;

use ParadoxLabs\TokenBase\Api\Data;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Class CardRepository
 */
class CardRepository implements CardRepositoryInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card
     */
    protected $resource;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var Data\CardSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory
     */
    protected $dataCardFactory;

    /**
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card $resource
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param Data\CardInterfaceFactory $dataCardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param Data\CardSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card $resource,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $dataCardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        Data\CardSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->resource = $resource;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCardFactory = $dataCardFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * Save Card data
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws CouldNotSaveException
     */
    public function save(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        try {
            $this->resource->save($card);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $card;
    }

    /**
     * Save card with extended objects.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     */
    public function saveExtended(
        \ParadoxLabs\TokenBase\Api\Data\CardInterface $card,
        \Magento\Customer\Api\Data\AddressInterface $address,
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
    ) {
        $card->setAddress($address);
        $card->setAdditional($additional);

        return $this->save($card);
    }

    /**
     * Load Card data by given ID
     *
     * @param string $cardId
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($cardId)
    {
        $card = $this->cardFactory->create();

        if (!is_numeric($cardId)) {
            $this->resource->load($card, $cardId, 'hash');
        } else {
            $this->resource->load($card, $cardId);
        }

        if (!$card->getId()) {
            throw new NoSuchEntityException(__('Card with id "%1" does not exist.', $cardId));
        }

        return $card;
    }

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByHash($cardHash)
    {
        $card = $this->cardFactory->create();

        $this->resource->load($card, $cardHash, 'hash');

        if (!$card->getId()) {
            throw new NoSuchEntityException(__('Card with id "%1" does not exist.', $cardHash));
        }

        return $card;
    }

    /**
     * Load Card data by given Card Identity
     *
     * @param string $cardId
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function load($cardId)
    {
        return $this->getById($cardId);
    }

    /**
     * Load Card data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param SearchCriteriaInterface $criteria
     * @return \ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $collection */
        $collection = $this->cardCollectionFactory->create();

        // Add filters from root filter group to the collection
        foreach ($criteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        // Add sort order(s)
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $collection->load();

        /** @var \ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * Delete Card
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CardInterface $card)
    {
        try {
            $this->resource->delete($card);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete Card by given Card Identity
     *
     * @param string $cardId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($cardId)
    {
        return $this->delete($this->getById($cardId));
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $collection
    ) {
        $fields = [];
        $conds  = [];

        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ?: 'eq';
            $fields[]  = $filter->getField();
            $conds[]   = [$condition => $filter->getValue()];
        }

        if ($fields) {
            $collection->addFieldToFilter($fields, $conds);
        }
    }
}
