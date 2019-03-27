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

namespace ParadoxLabs\TokenBase\Model\Api;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * GraphQL Class
 */
class GraphQL
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * GraphQL constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
}
