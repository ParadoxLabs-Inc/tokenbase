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
 * Interface CustomerCardRepositoryInterface
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
