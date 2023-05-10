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

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * GraphQL Class
 */
class GraphQL
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface
     */
    protected $maskedQuoteIdToQuoteId;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * GraphQL constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cartRepository = $cartRepository;

        if (interface_exists(\Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface::class)) {
            // Loading as such because this class does not exist until Magento 2.3
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->maskedQuoteIdToQuoteId = $om->get(\Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface::class);
        }
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
        \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context,
        $requireLogin = false
    ) {
        $isEnabled = (bool)$this->scopeConfig->getValue(
            'checkout/tokenbase/enable_public_api',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
    public function convertCardForOutput(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        $cardData                      = $card->toArray();
        $cardData['additional']        = $card->getAdditional();
        $cardData['address']           = $card->getAddress();
        $cardData['address']['street'] = explode("\n", $cardData['address']['street']);
        $cardData['address']['region'] = [
            'region_code' => isset($cardData['address']['region_code']) ? $cardData['address']['region_code'] : null,
            'region'      => isset($cardData['address']['region']) ? $cardData['address']['region'] : null,
            'region_id'   => isset($cardData['address']['region_id']) ? $cardData['address']['region_id'] : null,
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
        } catch (NoSuchEntityException $e) {
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
