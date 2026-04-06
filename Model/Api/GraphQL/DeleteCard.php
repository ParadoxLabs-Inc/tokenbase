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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface;
use ParadoxLabs\TokenBase\Model\Api\GraphQL;

class DeleteCard implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     */
    public function __construct(
        private readonly CustomerCardRepositoryInterface $customerCardRepository,
        private readonly GraphQL $graphQL
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
        if (!isset($args['hash']) || empty($args['hash'])) {
            throw new GraphQlInputException(__('Card "hash" value must be specified'));
        }

        $this->graphQL->authenticate($context, true);

        try {
            $this->customerCardRepository->deleteByHash($context->getUserId(), $args['hash']);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return true;
    }
}
