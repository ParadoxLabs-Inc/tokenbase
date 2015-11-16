<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * Check for extension updates/notifications
 */
class AdminNotificationObserver extends \Magento\AdminNotification\Observer\PredispathAdminActionControllerObserver
{
    /**
     * We inject our feed factory via DI. magic.
     */
}
