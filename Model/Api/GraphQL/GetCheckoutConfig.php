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
 * CheckoutConfig Class
 */
class GetCheckoutConfig implements \Magento\Framework\GraphQl\Query\ResolverInterface
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
        ?array $value = null,
        ?array $args = null
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
