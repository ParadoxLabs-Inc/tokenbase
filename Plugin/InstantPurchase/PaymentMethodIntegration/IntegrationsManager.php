<?php declare(strict_types=1);
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

namespace ParadoxLabs\TokenBase\Plugin\InstantPurchase\PaymentMethodIntegration;

use \Magento\InstantPurchase\PaymentMethodIntegration\IntegrationsManager as IntegrationsManagerOrig;

class IntegrationsManager
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\InstantPurchase\PaymentMethodIntegration\IntegrationFactory
     */
    protected $integrationFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    protected $methodFactory;

    /**
     * IntegrationsManager constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\InstantPurchase\PaymentMethodIntegration\IntegrationFactory $integrationFactory
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\InstantPurchase\PaymentMethodIntegration\IntegrationFactory $integrationFactory,
        \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory
    ) {
        $this->helper = $helper;
        $this->integrationFactory = $integrationFactory;
        $this->methodFactory = $methodFactory;
    }

    /**
     * Add TokenBase methods to the available methods for Instant Purchase.
     *
     * @param \Magento\InstantPurchase\PaymentMethodIntegration\IntegrationsManager $subject
     * @param array $result
     * @param int $storeId
     * @return array
     */
    public function afterGetList(IntegrationsManagerOrig $subject, array $result, int $storeId): array
    {
        $tokenbaseMethods = $this->helper->getActiveMethods();
        foreach ($tokenbaseMethods as $methodCode) {
            $method = $this->methodFactory->getMethodInstance($methodCode);
            if ($method->getConfigData('instant_purchase/supported') === '1') {
                $result[] = $this->integrationFactory->create($method, $storeId);
            }
        }

        return $result;
    }
}
