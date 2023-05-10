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

namespace ParadoxLabs\TokenBase\Model\Method;

/**
 * Method Factory
 */
class Factory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig   = $scopeConfig;
    }

    /**
     * Creates instance of card model
     *
     * @param string $className
     * @param array $data
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($className, array $data = [])
    {
        $card = $this->objectManager->create($className, $data);

        if (!$card instanceof \ParadoxLabs\TokenBase\Api\MethodInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 class doesn\'t implement \ParadoxLabs\TokenBase\Api\MethodInterface', $className)
            );
        }

        return $card;
    }

    /**
     * Get a method instance by code. Pulls the model from configuration.
     *
     * @param string $methodCode
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethodInstance($methodCode)
    {
        // Get model from config for the given payment method
        $methodModel = $this->scopeConfig->getValue(
            'payment/' . $methodCode . '/method_model',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (empty($methodCode) || empty($methodModel)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Invalid methodCode: '%1'", $methodCode)
            );
        }

        // Create and initialize the instance via object man.
        return $this->create($methodModel);
    }
}
