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

namespace ParadoxLabs\TokenBase\Model\ResourceModel;

use ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterfaceFactory;
use ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Model\Quote\PaymentFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data;
use ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Model\CardFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card as CardResource;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory;
use Throwable;

class CardRepository implements CardRepositoryInterface
{
    /**
     * @param CardResource $resource
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param Data\CardInterfaceFactory $dataCardFactory
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param Data\CardSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param PaymentFactory $paymentFactory
     * @param CartInterfaceFactory $quoteFactory
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        protected readonly CardResource $resource,
        protected readonly CardFactory $cardFactory,
        protected readonly CardInterfaceFactory $dataCardFactory,
        protected readonly CollectionFactory $cardCollectionFactory,
        protected readonly CardSearchResultsInterfaceFactory $searchResultsFactory,
        protected readonly DataObjectHelper $dataObjectHelper,
        protected readonly DataObjectProcessor $dataObjectProcessor,
        protected readonly PaymentFactory $paymentFactory,
        protected readonly CartInterfaceFactory $quoteFactory,
        protected readonly \Magento\Payment\Helper\Data $paymentHelper
    ) {
    }

    /**
     * Save Card data
     *
     * @param CardInterface $card
     * @return CardInterface
     * @throws LocalizedException
     */
    public function save(CardInterface $card)
    {
        if ($card::class === \ParadoxLabs\TokenBase\Model\Card::class) {
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
     * @param CardInterface $card
     * @param AddressInterface $address
     * @param CardAdditionalInterface $additional
     * @return CardInterface
     */
    public function saveExtended(
        CardInterface $card,
        AddressInterface $address,
        CardAdditionalInterface $additional
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
     * @return CardInterface
     * @throws NoSuchEntityException
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
     * @return CardInterface
     * @throws LocalizedException
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
     * @return CardInterface
     * @throws NoSuchEntityException
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
     * @return CardSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var Collection $collection */
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

        /** @var CardSearchResultsInterface $searchResults */
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
     * - If the card is active, this will mark it inactive and queue for later removal (typically 180 days later).
     * - If the card is already inactive, this will delete it entirely.
     *
     * @param CardInterface $card
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CardInterface $card)
    {
        try {
            if ((int)$card->getActive() === 0) {
                if ($card::class === \ParadoxLabs\TokenBase\Model\Card::class) {
                    /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                    $card = $card->getTypeInstance();
                }

                $this->resource->delete($card);
            } else {
                $card->queueDeletion();
                $this->resource->save($card);
            }
        } catch (Throwable $exception) {
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
     * @param FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(
        FilterGroup $filterGroup,
        Collection $collection
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
     * @throws LocalizedException
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
            && empty($paymentData['acceptjs_value'])
            && !$card->hasDataChanges()) {
            return;
        }

        if (isset($paymentData['cc_number'])) {
            $paymentData['cc_last4'] = substr((string)$paymentData['cc_number'], -4);
            $paymentData['cc_bin']   = substr((string)$paymentData['cc_number'], 0, 6);
        }

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();

        /** @var Payment $payment */
        $payment = $this->paymentFactory->create();
        $payment->setQuote($quote);
        $payment->getQuote()->getBillingAddress()->setCountryId($card->getAddress('country_id'));
        $payment->setData('tokenbase_source', 'paymentinfo');
        $payment->importData($paymentData);

        $paymentMethod = $this->paymentHelper->getMethodInstance($card->getMethod());
        $paymentMethod->setInfoInstance($payment);
        $paymentMethod->validate();

        $card->importPaymentInfo($payment);
    }
}
