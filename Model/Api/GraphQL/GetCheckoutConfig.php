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
 * CheckoutConfig Class
 */
class GetCheckoutConfig implements \ParadoxLabs\TokenBase\Model\Api\GraphQL\ResolverInterface
{
    /**
     * @var \Magento\Payment\Model\CcGenericConfigProvider[]
     */
    private $configProviders;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Api\GraphQL
     */
    private $graphQL;

    /**
     * CheckoutConfig constructor.
     *
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     * @param array $configProviders
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL,
        array $configProviders = []
    ) {
        $this->configProviders = $configProviders;
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
        // Validate
        $this->graphQL->authenticate($context);

        $method = isset($args['method']) ? $args['method'] : '';
        if (empty($method) || !isset($this->configProviders[$method])) {
            throw new \Magento\Framework\GraphQl\Exception\GraphQlInputException(
                __('Invalid TokenBase method provided.')
            );
        }

        // Get checkout config for the given payment method
        $paymentConfig = $this->configProviders[$method]->getConfig();

        if (!isset($paymentConfig['payment'][$method])) {
            return [];
        }

        // Merge in ccform values
        foreach ($paymentConfig['payment']['ccform'] as $key => $methods) {
            if (isset($methods[$method])) {
                $paymentConfig['payment'][$method][$key] = is_array($methods[$method])
                    ? $this->graphQL->toKeyValueArray($methods[$method])
                    : $methods[$method];
            }
        }

        // Output
        $paymentConfig['payment'][$method]['method'] = $method;
        return $paymentConfig['payment'][$method];
    }
}
