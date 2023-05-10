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

namespace ParadoxLabs\TokenBase\Model\Api;

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
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * GuestCardRepository constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->cardRepository = $cardRepository;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->scopeConfig = $scopeConfig;
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
        $this->validateEnabled();

        // Validate original record so it can't be overwritten maliciously
        if ($card->getHash()) {
            try {
                $originalCard = $this->getByHash($customerId, $card->getHash());
                $this->validateCustomerCard($customerId, $originalCard);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // No-op: Ignore card hash does not exist
            }
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
        $this->validateEnabled();

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
        $this->validateEnabled();

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
        $this->validateEnabled();

        $card = $this->getByHash($customerId, $cardHash);

        return $this->cardRepository->delete($card);
    }

    /**
     * Do not allow customers to fetch or modify cards belonging to others.
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

    /**
     * Verify that the public API is enabled.
     *
     * @return void
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    protected function validateEnabled()
    {
        $isEnabled = (bool)$this->scopeConfig->getValue(
            'checkout/tokenbase/enable_public_api',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($isEnabled !== true) {
            throw new \Magento\Framework\Exception\AuthorizationException(
                __('The public TokenBase API is not enabled.')
            );
        }
    }
}
