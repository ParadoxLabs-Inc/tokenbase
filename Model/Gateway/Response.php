<?php
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Model\Gateway;

/**
 * Response object: Container for various response data (txn ID, status, etc.)
 */
class Response extends \Magento\Framework\DataObject
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

    /**
     * Get transaction response code
     *
     * @return string|int
     */
    public function getResponseCode()
    {
        return $this->getData('response_code');
    }

    /**
     * Get transaction descriptor
     *
     * @return string|int
     */
    public function getResponseReasonCode()
    {
        return $this->getData('response_reason_code');
    }

    /**
     * Get transaction type
     *
     * @return string
     */
    public function getTransactionType()
    {
        return $this->getData('transaction_type');
    }

    /**
     * Get transaction ID
     *
     * @return string|int
     */
    public function getTransactionId()
    {
        return $this->getData('transaction_id');
    }

    /**
     * Get authorization code
     *
     * @return string
     */
    public function getAuthCode()
    {
        return $this->getData('auth_code');
    }

    /**
     * Get transaction method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getData('method');
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getResponseReasonText()
    {
        return $this->getData('response_reason_text');
    }

    /**
     * Object data getter
     *
     * @param string $key
     * @param string|int $index
     * @return mixed
     */
    public function getData($key = '', $index = null)
    {
        $data = parent::getData(
            $key,
            $index
        );

        if (empty($key)) {
            return $this->flattenArray($data);
        }

        return $data;
    }

    /**
     * Turn multi-dimensional array into 1D, concatenating keys
     *
     * We do this transformation for sales_order_payment.additional_information, and particularly the admin txn
     * details view page, which only supports a single level associative array.
     *
     * @param mixed $array
     * @param string|null $prefix
     * @return array
     * @see http://stackoverflow.com/a/9546215/2336164
     */
    protected function flattenArray($array, $prefix = null)
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result += $this->flattenArray($value, $prefix . $key . '.');
            } elseif (is_bool($value)) {
                $result[$prefix . $key] = $value ? '1' : '0';
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
