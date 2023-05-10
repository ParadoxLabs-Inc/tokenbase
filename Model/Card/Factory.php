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

namespace ParadoxLabs\TokenBase\Model\Card;

/**
 * Card factory
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
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($className, array $data = [])
    {
        $card = $this->objectManager->create((string)$className, $data);

        if (!$card instanceof \ParadoxLabs\TokenBase\Api\Data\CardInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 class doesn\'t implement \ParadoxLabs\TokenBase\Api\Data\CardInterface', $className)
            );
        }

        return $card;
    }

    /**
     * Get a card's type instance, using an existing loaded instance.
     *
     * This allows us to go from a generic collection/instance to the card's specific implementation.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     */
    public function getTypeInstance(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        if ($card->getMethod() !== null) {
            // Get model from config for the card's payment method
            $cardModel      = $this->scopeConfig->getValue(
                'payment/' . $card->getMethod() . '/card_model',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $existingClass = str_replace('\\Interceptor', '', (string)get_class($card));

            if ($existingClass !== $cardModel && !empty($cardModel)) {
                // Create and initialize the instance via object man.
                $typeInstance = $this->create($cardModel);
                $typeInstance->setData($card->getData());
                // Copy all origData to the new instance.
                $typeInstance->setOrigData(null, $card->getOrigData());
                $card = $typeInstance;
            }
        }

        return $card;
    }
}
