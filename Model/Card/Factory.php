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

namespace ParadoxLabs\TokenBase\Model\Card;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;

/**
 * Card factory
 */
class Factory
{
    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
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
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($className, array $data = [])
    {
        $card = $this->objectManager->create((string)$className, $data);

        if (!$card instanceof CardInterface) {
            throw new LocalizedException(
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
    public function getTypeInstance(CardInterface $card)
    {
        if ($card->getMethod() !== null) {
            // Get model from config for the card's payment method
            $cardModel = $this->scopeConfig->getValue(
                'payment/' . $card->getMethod() . '/card_model',
                ScopeInterface::SCOPE_STORE
            );

            $existingClass = str_replace('\\Interceptor', '', (string)$card::class);

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
