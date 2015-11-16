<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Observer\AdminNotification;

/**
 * Factory for the TokenBase Feed class
 */
class FeedFactory extends \Magento\AdminNotification\Model\FeedFactory
{
    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = '\\ParadoxLabs\\TokenBase\\Observer\\AdminNotification\\Feed'
    ) {
        parent::__construct($objectManager, $instanceName);
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \ParadoxLabs\TokenBase\Observer\AdminNotification\Feed
     */
    public function create(array $data = array())
    {
        return parent::create($data);
    }
}
