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
