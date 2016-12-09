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

namespace ParadoxLabs\TokenBase\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Payment method settings: Payment actions
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'order',
                'label' => __('Save info (do not authorize)'),
            ],
            [
                'value' => 'authorize',
                'label' => __('Authorize'),
            ],
            [
                'value' => 'capture',
                'label' => __('Authorize and Capture'),
            ],
        ];
    }
}
