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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
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
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card $resource
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param Data\CardInterfaceFactory $dataCardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param Data\CardSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card $resource,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $dataCardFactory,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        Data\CardSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Payment\Helper\Data $paymentHelper
    ) {
        $this->resource = $resource;
        $this->cardFactory = $cardFactory;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCardFactory = $dataCardFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->paymentFactory = $paymentFactory;
        $this->quoteFactory = $quoteFactory;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Save Card data
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws PaymentException
     */
    public function save(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        if (get_class($card) === \ParadoxLabs\TokenBase\Model\Card::class) {
            /** @var \ParadoxLabs\TokenBase\Model\Card $card */
            $card = $card->getTypeInstance();
        }

        if ($card->getId() === 0 || $card->getId() === '0') {
            $card->setId(null);
        }

        $this->resource->save($card);

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

        $this->updatePaymentInfo($card);

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
            throw new NoSuchEntityException(__('Card with hash "%1" does not exist.', $cardHash));
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
                    ($sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
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
     * Two-mode operation:
     * - If the card is active, this will mark it inactive and queue for later removal (typically 120 days later).
     * - If the card is already inactive, this will delete it entirely.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Data\CardInterface $card)
    {
        try {
            if ((int)$card->getActive() === 0) {
                if (get_class($card) === \ParadoxLabs\TokenBase\Model\Card::class) {
                    /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                    $card = $card->getTypeInstance();
                }

                $this->resource->delete($card);
            } else {
                $card->queueDeletion();
                $this->resource->save($card);
            }
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

    /**
     * Process payment data before save. This allows new payment data to sync to the gateway.
     *
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updatePaymentInfo(
        \ParadoxLabs\TokenBase\Model\Card $card
    ) {
        $paymentData            = $card->getAdditional();
        $paymentData['method']  = $card->getMethod();
        $paymentData['card_id'] = $card->getId() > 0 ? $card->getHash() : '';

        // Skip validation and import if we're given an existing card ID and no new payment info.
        if (!empty($card->getPaymentId())
            && empty($paymentData['cc_number'])
            && empty($paymentData['token'])
            && empty($paymentData['acceptjs_value'])) {
            return;
        }

        if (isset($paymentData['cc_number'])) {
            $paymentData['cc_last4'] = substr($paymentData['cc_number'], -4);
            $paymentData['cc_bin']   = substr($paymentData['cc_number'], 0, 6);
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->paymentFactory->create();
        $payment->setQuote($quote);
        $payment->getQuote()->getBillingAddress()->setCountryId($card->getAddress('country_id'));
        $payment->importData($paymentData);

        $paymentMethod = $this->paymentHelper->getMethodInstance($card->getMethod());
        $paymentMethod->setInfoInstance($payment);
        $paymentMethod->validate();

        $card->importPaymentInfo($payment);
    }
}
