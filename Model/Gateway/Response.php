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

namespace ParadoxLabs\TokenBase\Model\Gateway;

/**
 * Response object: Container for various response data (txn ID, status, etc.)
 */
class Response extends \Magento\Framework\Object
{
    /**
     * Mark response as fraud or not fraud
     *
     * @param bool $isFraud
     * @return $this
     */
    public function setIsFraud($isFraud)
    {
        return $this->setData('is_fraud', $isFraud);
    }

    /**
     * Get fraud status
     *
     * @return bool
     */
    public function getIsFraud()
    {
        return $this->getData('is_fraud') ? true : false;
    }

    /**
     * Mark response as error or successful
     *
     * @param bool $isError
     * @return $this
     */
    public function setIsError($isError)
    {
        return $this->setData('is_error', $isError);
    }

    /**
     * Get error status
     *
     * @return bool
     */
    public function getIsError()
    {
        return $this->getData('is_error') ? true : false;
    }
}
