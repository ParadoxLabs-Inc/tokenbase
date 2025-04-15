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

namespace ParadoxLabs\TokenBase\Model\Api\GraphQL;

/**
 * Soft dependency: Supporting 2.3 GraphQL without breaking <2.3 compatibility.
 * 2.3+ implements \Magento\Framework\GraphQL; lower does not.
 */
if (!interface_exists('\ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface')) {
    if (interface_exists('\Magento\Framework\GraphQl\Query\ResolverInterface')) {
        class_alias(
            '\Magento\Framework\GraphQl\Query\ResolverInterface',
            '\ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface'
        );
    } else {
        class_alias(
            '\ParadoxLabs\TokenBase\Model\Api\GraphQL\FauxResolverInterface',
            '\ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface'
        );
    }
}

/**
 * Card Class
 */
class GetCards implements \ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface
     */
    private $customerCardRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Api\GraphQL
     */
    private $graphQL;

    /**
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
    ) {
        $this->customerCardRepository = $customerCardRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->graphQL                = $graphQL;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return mixed|\Magento\Framework\GraphQl\Query\Resolver\Value
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $this->graphQL->authenticate($context, true);

        /** @var \ParadoxLabs\TokenBase\Model\Card[] $cards */
        $cards  = $this->getCards(
            $context->getUserId(),
            isset($args['hash']) ? $args['hash'] : null
        );
        $output = [];
        foreach ($cards as $card) {
            $output[] = $this->graphQL->convertCardForOutput($card);
        }

        return $output;
    }

    /**
     * @param int $customerId
     * @param string|null $hash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getCards($customerId, $hash)
    {
        $searchCriteria = $this->searchCriteriaBuilder;

        // Filter results by a specific hash if given
        if (!empty($hash)) {
            $searchCriteria->addFilter('hash', $hash);
        }

        $searchCriteria = $searchCriteria->create();

        return $this->customerCardRepository->getList($customerId, $searchCriteria)->getItems();
    }
}
