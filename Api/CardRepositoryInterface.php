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

namespace ParadoxLabs\TokenBase\Api;

/**
 * Card CRUD interface.
 *
 * @api
 */
interface CardRepositoryInterface
{
    /**
     * Save card.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card);

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
    );

    /**
     * Retrieve card. Will accept numeric ID or hash.
     *
     * @param string $cardId
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($cardId);

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByHash($cardHash);

    /**
     * Retrieve card.
     *
     * @param int $cardId
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load($cardId);

    /**
     * Retrieve cards matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete card.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(Data\CardInterface $card);

    /**
     * Delete card by ID.
     *
     * @param int $cardId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($cardId);
}
