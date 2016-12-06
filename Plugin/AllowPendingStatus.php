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

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * AllowPendingStatus Class
 */
class AllowPendingStatus
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AllowPendingStatus constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve statuses available for state
     * Get all possible statuses, or for specified state, or specified states array
     * Add labels by default. Return plain array of statuses, if no labels.
     *
     * @param \Magento\Sales\Model\Order\Config $subject
     * @param \Closure $proceed
     * @param mixed $state
     * @param bool $addLabels
     * @return array
     */
    public function aroundGetStateStatuses(
        \Magento\Sales\Model\Order\Config $subject,
        \Closure $proceed,
        $state,
        $addLabels = true
    ) {
        $result = $proceed($state, $addLabels);

        /**
         * Okay. So this sucks. But if they set our payment method to order status 'pending',
         * we have to be able to actually assign that, and we don't want to make them jump through loops
         * to do so. So if request state is processing, make sure pending is an option.
         * It would be nice to check against the actual order payment method settings, except
         * we don't know the order or the payment method here.
         *
         * Note: This will not work for custom new order statuses. Any such will have to be
         * assigned to processing.
         */
        if (is_array($result) && $state == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            if (!isset($result['pending']) && !in_array('pending', $result)) {
                if ($addLabels === true) {
                    $result['pending'] = __('Pending');
                } else {
                    $result[] = 'pending';
                }

                $result   = array_unique($result);
            }
        }

        return $result;
    }
}
