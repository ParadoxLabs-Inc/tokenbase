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

namespace ParadoxLabs\TokenBase\Model\Logger;

use Magento\Framework\Filesystem\DriverInterface;

/**
 * Custom payment gateway logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/tokenbase.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null
    ) {
        parent::__construct($filesystem, $filePath);

        // Change the message format, and hide empty context/extra info.
        $format = "[%datetime%] %message% %context% %extra%\n";

        $this->setFormatter(new \Monolog\Formatter\LineFormatter($format, null, true, true));
    }
}
