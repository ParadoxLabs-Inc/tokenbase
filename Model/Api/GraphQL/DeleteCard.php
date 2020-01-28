<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Api\GraphQL;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

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
 * DeleteCard Class
 */
class DeleteCard implements \ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface
     */
    private $customerCardRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Api\GraphQL
     */
    private $graphQL;

    /**
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository,
        \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
    ) {
        $this->customerCardRepository = $customerCardRepository;
        $this->graphQL = $graphQL;
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
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['hash']) || empty($args['hash'])) {
            throw new GraphQlInputException(__('Card "hash" value must be specified'));
        }

        $this->graphQL->authenticate($context, true);

        try {
            $this->customerCardRepository->deleteByHash($context->getUserId(), $args['hash']);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return true;
    }
}
