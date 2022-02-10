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

namespace ParadoxLabs\TokenBase\Helper;

/**
 * Operation helper -- common data things
 */
class Operation extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Monolog\Logger
     */
    protected $tokenbaseLogger;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Monolog\Logger $tokenbaseLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Monolog\Logger $tokenbaseLogger
    ) {
        parent::__construct($context);

        $this->tokenbaseLogger = $tokenbaseLogger;
    }

    /**
     * Recursively cleanup array from objects
     *
     * @param $array
     * @return void
     */
    public function cleanupArray(&$array)
    {
        if (!$array) {
            return;
        }

        foreach ($array as $key => $value) {
            if (is_object($value)) {
                unset($array[ $key ]);
            } elseif (is_array($value)) {
                $this->cleanupArray($array[ $key ]);
            }
        }
    }

    /**
     * Pull a value from a nested array safely (without notices, default fallback)
     *
     * @param  array  $data    source array
     * @param  string $path    path to pull, separated by slashes
     * @param  string $default default response (if key DNE)
     * @return mixed           target value or default
     */
    public function getArrayValue($data, $path, $default = '')
    {
        $keys = explode('/', (string)$path);
        $val =& $data;

        foreach ($keys as $key) {
            if (!isset($val[$key])) {
                return $default;
            }

            $val =& $val[$key];
        }

        return $val;
    }

    /**
     * Write a message to the logs, nice and abstractly.
     *
     * @param string $code
     * @param mixed $message
     * @param bool $debug
     * @return $this
     */
    public function log($code, $message, $debug = false)
    {
        if (is_object($message)) {
            if ($message instanceof \Magento\Framework\Phrase) {
                $message = (string)$message;
            } elseif ($message instanceof \Magento\Framework\DataObject) {
                $message = $message->getData();

                $this->cleanupArray($message);
            } else {
                $message = (array)$message;
            }
        }

        if (is_array($message)) {
            $message = print_r($message, true);
        }

        if ($debug === true) {
            $this->tokenbaseLogger->debug(
                sprintf('%s [%s]: %s', $code, $this->_remoteAddress->getRemoteAddress(), $message)
            );
        } else {
            $this->tokenbaseLogger->info(
                sprintf('%s [%s]: %s', $code, $this->_remoteAddress->getRemoteAddress(), $message)
            );
        }

        return $this;
    }
}
