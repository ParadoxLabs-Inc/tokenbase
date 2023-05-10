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
 * GuestCardRepository Class
 */
class GuestCardRepository implements \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * GuestCardRepository constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->cardRepository = $cardRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Save card with extended objects.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveExtended(
        \ParadoxLabs\TokenBase\Api\Data\CardInterface $card,
        \Magento\Customer\Api\Data\AddressInterface $address,
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
    ) {
        $this->validateEnabled();

        // Validate original record so it can't be overwritten maliciously
        if ($card->getHash()) {
            try {
                $originalCard = $this->getByHash($card->getHash());
                $this->validateGuestCard($originalCard);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // No-op: Ignore card hash does not exist
            }
        } elseif ($card->getId()) {
            $originalCard = $this->cardRepository->getById($card->getId());
            $this->validateGuestCard($originalCard);
        }

        $this->validateGuestCard($card);

        // Force guest card to inactive. Will be usable, but not visible, and automatically pruned.
        $card->setActive(0);

        return $this->cardRepository->saveExtended($card, $address, $additional);
    }

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getByHash($cardHash)
    {
        $this->validateEnabled();

        $card = $this->cardRepository->getByHash($cardHash);

        $this->validateGuestCard($card);

        return $card;
    }

    /**
     * Do not allow guests to fetch or modify cards belonging to non-guests.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validateGuestCard(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        if ((int)$card->getCustomerId() > 0) {
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
