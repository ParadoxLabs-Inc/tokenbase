<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 */

namespace ParadoxLabs\TokenBase\Model\Source;

/**
 * OrderStatus Class
 */
class OrderStatus extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * Limit order statuses to ones associated with the 'new', 'processing', or 'hold' states.
     *
     * @var string[]
     */
    protected $_stateStatuses = [
        \Magento\Sales\Model\Order::STATE_NEW,
        \Magento\Sales\Model\Order::STATE_PROCESSING,
        \Magento\Sales\Model\Order::STATE_HOLDED,
    ];
}
