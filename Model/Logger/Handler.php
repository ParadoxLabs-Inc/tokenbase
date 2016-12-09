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
     * @param \Monolog\Formatter\LineFormatter $lineFormatter
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        \Monolog\Formatter\LineFormatter $lineFormatter,
        $filePath = null
    ) {
        parent::__construct($filesystem, $filePath);

        $this->setFormatter($lineFormatter);
    }
}
