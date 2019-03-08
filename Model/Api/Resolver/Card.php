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

namespace ParadoxLabs\TokenBase\Model\Api\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Card Class
 */
class Card implements \Magento\Framework\GraphQl\Query\ResolverInterface
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
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->customerCardRepository = $customerCardRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
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
        $this->authenticate($context);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $cards          = $this->customerCardRepository->getList($context->getUserId(), $searchCriteria)->getItems();

        /** @var \ParadoxLabs\TokenBase\Model\Card[] $cards */
        $output = [];
        foreach ($cards as $card) {
            $output[] = $this->convertCardForGraphQL($card);
        }

        return $output;
    }

    /**
     * @param $context
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     */
    private function authenticate($context)
    {
        /** @var ContextInterface $context */
        if ((!$context->getUserId()) || $context->getUserType() === UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                __(
                    'Current customer does not have access to the resource "%1"',
                    'TokenbaseCard'
                )
            );
        }
    }

    /**
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return array
     */
    protected function convertCardForGraphQL(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        $cardData                      = $card->toArray();
        $cardData['additional']        = $card->getAdditional();
        $cardData['address']           = $card->getAddress();
        $cardData['address']['street'] = explode("\n", $cardData['address']['street']);
        $cardData['address']['region'] = [
            'region_code' => $cardData['address']['region_code'],
            'region' => $cardData['address']['region'],
            'region_id' => $cardData['address']['region_id'],
        ];
        $cardData['label']             = $card->getLabel();

        return $cardData;
    }
}
