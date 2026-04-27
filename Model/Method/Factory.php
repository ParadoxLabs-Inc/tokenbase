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

namespace ParadoxLabs\TokenBase\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Api\MethodInterface;

/**
 * Method Factory
 */
class Factory
{
    /**
     * Construct
     *
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        /**
         * Object manager
         */
        private readonly ObjectManagerInterface $objectManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Creates instance of card model
     *
     * @param string $className
     * @param array $data
     * @return MethodInterface
     * @throws LocalizedException
     */
    public function create($className, array $data = [])
    {
        $card = $this->objectManager->create($className, $data);

        if (!$card instanceof MethodInterface) {
            throw new LocalizedException(
                __('%1 class doesn\'t implement \ParadoxLabs\TokenBase\Api\MethodInterface', $className)
            );
        }

        return $card;
    }

    /**
     * Get a method instance by code. Pulls the model from configuration.
     *
     * @param string $methodCode
     * @return MethodInterface
     * @throws LocalizedException
     */
    public function getMethodInstance($methodCode)
    {
        // Get model from config for the given payment method
        $methodModel = $this->scopeConfig->getValue(
            'payment/' . $methodCode . '/method_model',
            ScopeInterface::SCOPE_STORE
        );

        if (empty($methodCode) || empty($methodModel)) {
            throw new LocalizedException(
                __("Invalid methodCode: '%1'", $methodCode)
            );
        }

        // Create and initialize the instance via object man.
        return $this->create($methodModel);
    }
}
