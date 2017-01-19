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
    public function create($className, $data = [])
    {
        $card = $this->objectManager->create($className, $data);

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

            $existingClass = str_replace('\\Interceptor', '', get_class($card));

            if ($existingClass !== $cardModel) {
                // Create and initialize the instance via object man.
                $typeInstance = $this->create($cardModel);
                $typeInstance->setData($card->getData());
                $card = $typeInstance;
            }
        }

        return $card;
    }
}
