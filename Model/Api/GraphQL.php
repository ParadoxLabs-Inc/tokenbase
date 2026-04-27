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

namespace ParadoxLabs\TokenBase\Model\Api;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;

class GraphQL
{
    /**
     * GraphQL constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     */
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly CartRepositoryInterface $cartRepository,
        protected readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
    ) {
    }

    /**
     * Verify the requester is authorized to request data.
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param bool $requireLogin
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     */
    public function authenticate(
        ContextInterface $context,
        $requireLogin = false
    ) {
        $isEnabled = (bool)$this->scopeConfig->getValue(
            'checkout/tokenbase/enable_public_api',
            ScopeInterface::SCOPE_STORE
        );

        if ($isEnabled !== true) {
            throw new GraphQlAuthorizationException(
                __('The TokenbaseCard API is not enabled.')
            );
        }

        if ($requireLogin === true
            && (!$context->getUserId() || $context->getUserType() === UserContextInterface::USER_TYPE_GUEST)) {
            throw new GraphQlAuthorizationException(
                __(
                    'Current customer does not have access to the resource "%1"',
                    'TokenbaseCard'
                )
            );
        }
    }

    /**
     * Convert the given card into an array format suitable for GraphQL.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return array
     */
    public function convertCardForOutput(CardInterface $card)
    {
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        $cardData                      = $card->toArray();
        $cardData['additional']        = $card->getAdditional();
        $cardData['address']           = $card->getAddress();
        $cardData['address']['street'] = explode("\n", (string) $cardData['address']['street']);
        $cardData['address']['region'] = [
            'region_code' => $cardData['address']['region_code'] ?? null,
            'region' => $cardData['address']['region'] ?? null,
            'region_id' => $cardData['address']['region_id'] ?? null,
        ];
        $cardData['label']             = $card->getLabel();

        return $cardData;
    }

    /**
     * Convert a single-dimensional assoc array into a key/value options array
     *
     * @param array $inputArray
     * @return array
     */
    public function toKeyValueArray($inputArray)
    {
        $output = [];
        if (is_array($inputArray)) {
            foreach ($inputArray as $key => $value) {
                $output[] = [
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ];
            }
        }

        return $output;
    }

    /**
     * @param int $customerId
     * @param string $quoteHash
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     */
    public function getQuote($customerId, $quoteHash)
    {
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($quoteHash);
            $quote   = $this->cartRepository->get($quoteId);
        } catch (NoSuchEntityException) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $quoteHash])
            );
        }

        if ((bool)$quote->getIsActive() === false) {
            throw new GraphQlNoSuchEntityException(__('The cart isn\'t active.'));
        }

        if ((int)$quote->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current user cannot perform operations on cart "%masked_cart_id"',
                    ['masked_cart_id' => $quoteHash]
                )
            );
        }

        return $quote;
    }
}
