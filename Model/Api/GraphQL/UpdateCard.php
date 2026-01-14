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

namespace ParadoxLabs\TokenBase\Model\Api\GraphQL;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface;
use ParadoxLabs\TokenBase\Model\Api\GraphQL;
use ParadoxLabs\TokenBase\Model\Card;

class UpdateCard implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface $guestCardRepository
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory
     */
    public function __construct(
        protected CustomerCardRepositoryInterface $customerCardRepository,
        protected GuestCardRepositoryInterface $guestCardRepository,
        protected GraphQL $graphQL,
        protected DataObjectHelper $dataObjectHelper,
        protected CustomerRepositoryInterface $customerRepository,
        protected CardInterfaceFactory $cardFactory
    ) {
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|\Magento\Framework\GraphQl\Query\Resolver\Value
     * @throws \Exception
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $this->graphQL->authenticate($context);

        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $cardData = $args['input'];

        /**
         * Get card
         */
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        $card = $this->getCard($context, $cardData);

        /**
         * Set/update card data
         */
        $this->updateCardData($context, $card, $cardData);
        $this->updatePaymentInfo($card, $cardData);

        /** @var \Magento\Customer\Model\Data\Address $address */
        $address = $this->updateAddressData($card, $cardData);
        $additional = $card->getAdditionalObject();
        $card = $card->getTypeInstance();

        /**
         * Save changes
         */
        if ($context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $this->customerCardRepository->saveExtended($context->getUserId(), $card, $address, $additional);
        } else {
            $this->guestCardRepository->saveExtended($card, $address, $additional);
        }

        /**
         * Output
         */
        return $this->graphQL->convertCardForOutput($card);
    }

    /**
     * Load or create card model
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param array $cardData
     * @return \ParadoxLabs\TokenBase\Model\Card
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCard(ContextInterface $context, array $cardData)
    {
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        if (isset($cardData['hash'])) {
            if ($context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
                $card = $this->customerCardRepository->getByHash($context->getUserId(), $cardData['hash']);
            } else {
                $card = $this->guestCardRepository->getByHash($cardData['hash']);
            }
        } else {
            $card = $this->cardFactory->create();
            $card->setMethod($cardData['method']);
        }

        return $card;
    }

    /**
     * Process card data (including customer assignment)
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param array $cardData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCardData(
        ContextInterface $context,
        Card $card,
        array $cardData
    ) {
        // Associate customer
        $customerId = $context->getUserId();
        if ($customerId > 0 && $context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $customer = $this->customerRepository->getById($customerId);
            $card->setCustomer($customer);
        } else {
            $card->setCustomerId(0);
        }

        // Ignore complex values. Assume all others are okay because GraphQL schema will reject any not allowed.
        $settableValues = array_diff_key(
            $cardData,
            [
                'address' => 1,
                'additional' => 1,
            ]
        );

        $this->dataObjectHelper->populateWithArray(
            $card,
            $settableValues,
            CardInterface::class
        );
    }

    /**
     * Process address data
     *
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param array $cardData
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function updateAddressData(Card $card, array $cardData)
    {
        $address = $card->getAddressObject();
        if (isset($cardData['address'])) {
            $this->dataObjectHelper->populateWithArray(
                $address,
                $cardData['address'],
                AddressInterface::class
            );

            if (isset($cardData['address']['region']['region_id'])) {
                $address->setRegionId($cardData['address']['region']['region_id']);
            }
        }

        return $address;
    }

    /**
     * Process payment data
     *
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param array $cardData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updatePaymentInfo(Card $card, array $cardData)
    {
        if (!isset($cardData['additional']) || empty($cardData['additional'])) {
            return;
        }

        $card->setAdditional($cardData['additional']);
    }
}
