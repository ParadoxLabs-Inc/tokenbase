<?php declare(strict_types=1);
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
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Model;

use Magento\Framework\DataObject;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Model\InfoInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\GatewayInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory;
use ParadoxLabs\TokenBase\Model\Gateway\Xml;
use Throwable;

/**
 * Common API gateway methods, logging, exceptions, etc.
 */
abstract class AbstractGateway extends DataObject implements GatewayInterface
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var bool
     */
    protected $testMode;

    /**
     * @var bool
     */
    protected $verifySsl;

    /**
     * $fields defines validation for each API parameter or input.
     *
     * key => array(
     *    'maxLength' => int,
     *    'noSymbols' => true|false,
     *    'charMask'  => (allowed characters in regex form),
     *    'enum'      => array( values )
     * )
     *
     * @var array
     */
    protected $fields = [];

    /**
     * These hold parameters for each request.
     *
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var string
     */
    protected $lastRequest;

    /**
     * @var string|array
     */
    protected $lastResponse;

    /**
     * @var array|null
     */
    protected $lineItems;

    /**
     * @var string
     */
    protected $log = '';

    /**
     * @var string
     */
    protected $endpointLive = '';

    /**
     * @var string
     */
    protected $endpointTest = '';

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var bool
     */
    protected $haveAuthorized = false;

    /**
     * Constructor, yeah!
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Xml $xml
     * @param \ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory $responseFactory
     * @param array $data
     * @param \Magento\Framework\HTTP\ClientInterfaceFactory $communicatorFactory
     */
    public function __construct(
        protected Data $helper,
        protected Xml $xml,
        protected ResponseFactory $responseFactory,
        protected ClientInterfaceFactory $communicatorFactory,
        array $data = [],
    ) {
        parent::__construct($data);
    }

    /**
     * Initialize the gateway. Input is taken as an array for greater flexibility.
     *
     * @param array $parameters
     * @return $this
     */
    public function init(array $parameters)
    {
        $this->secretKey = $parameters['secret_key'] ?? '';
        $this->testMode  = isset($parameters['test_mode']) ? (bool)$parameters['test_mode'] : false;
        $this->verifySsl = isset($parameters['verify_ssl']) ? (bool)$parameters['verify_ssl'] : false;

        $this->defaults = [
            'login' => $parameters['login'],
            'password' => $parameters['password'],
        ];

        if (isset($parameters['endpoint'])) {
            $this->endpoint = $parameters['endpoint'];
        } else {
            $this->endpoint = ($this->testMode === true ? $this->endpointTest : $this->endpointLive);
        }

        $this->clearParameters();

        $this->initialized = true;

        return $this;
    }

    /**
     * Has the gateway been initialized/configured?
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Undo initialization
     *
     * @return $this
     */
    public function reset()
    {
        $this->defaults  = [];
        $this->secretKey = '';
        $this->testMode  = null;
        $this->endpoint  = null;

        $this->clearParameters();

        $this->initialized = false;

        return $this;
    }

    /**
     * Set the API parameters back to defaults, clearing any runtime values.
     *
     * @return $this
     */
    public function clearParameters()
    {
        $this->params    = $this->defaults;
        $this->log       = '';
        $this->lineItems = null;

        return $this;
    }

    /**
     * Set a parameter.
     *
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function setParameter($key, $val)
    {
        if (is_bool($val) || !empty($val)) {
            /**
             * Make sure we know this parameter
             */
            if (array_key_exists($key, $this->fields)) {
                /**
                 * Run validations
                 */

                if (isset($this->fields[ $key ]['noSymbols']) && $this->fields[ $key ]['noSymbols'] === true) {
                    /**
                     * Convert special characters to simple ascii equivalent
                     */
                    $val = htmlentities((string)$val, ENT_QUOTES, 'UTF-8');
                    $val = preg_replace(
                        '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
                        '$1',
                        $val
                    );
                    $val = preg_replace(['/[^0-9a-z \.\-]/i', '/\s{2,}/'], ' ', (string) $val);
                    $val = trim((string) $val);
                }

                if (isset($this->fields[ $key ]['charMask'])) {
                    /**
                     * Apply a regex character filter to the input.
                     */
                    $val = preg_replace('/[^' . $this->fields[ $key ]['charMask'] . ']/', '', (string)$val);
                }

                if (isset($this->fields[ $key ]['maxLength']) && $this->fields[ $key ]['maxLength'] > 0) {
                    /**
                     * Truncate if the value is too long
                     */
                    $this->params[ $key ] = substr((string)$val, 0, $this->fields[ $key ]['maxLength']);
                } elseif (isset($this->fields[ $key ]['enum'])) {
                    /**
                     * Error if value is not on the allowed list
                     */
                    if (in_array($val, $this->fields[ $key ]['enum'])) {
                        $this->params[ $key ] = $val;
                    } else {
                        throw new CommandException(
                            __(sprintf("Payment Gateway: Invalid value for '%s': '%s'", $key, $val))
                        );
                    }
                } else {
                    $this->params[ $key ] = $val;
                }
            } else {
                throw new CommandException(
                    __(sprintf("Payment Gateway: Unknown parameter '%s'", $key))
                );
            }
        } elseif ($val === null) {
            unset($this->params[ $key ]);
        }

        return $this;
    }

    /**
     * Get parameters. Debugging purposes.
     *
     * Implementation should mask or erase any confidential data from the response.
     * Card number, CVV, and password should never be logged in full.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Get a single parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParameter($key, $default = '')
    {
        return ($this->params[ $key ] ?? $default);
    }

    /**
     * Check whether parameter exists
     *
     * @param string $key
     * @return bool
     */
    public function hasParameter($key)
    {
        return (isset($this->params[ $key ]) && !empty($this->params[ $key ]));
    }

    /**
     * Get the last response value.
     *
     * @return string|array
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Print stored logs to the gateway log.
     *
     * @return $this
     */
    public function logLogs()
    {
        $this->helper->log($this->code, $this->log);

        return $this;
    }

    /**
     * Add line items, to be sent with relevant transactions.
     * Input should be a collection of items.
     *
     * @param array $items
     * @return $this
     */
    public function setLineItems($items)
    {
        $this->lineItems = $items;

        return $this;
    }

    /**
     * Format decimals to the appropriate precision.
     *
     * @param float $amount
     * @return string
     */
    public static function formatAmount($amount)
    {
        return sprintf('%01.2f', (float)$amount);
    }

    /**
     * Mask certain values in the XML for secure logging purposes.
     *
     * @param $string
     * @return mixed
     */
    protected function sanitizeLog($string)
    {
        return $string;
    }

    /**
     * Convert array to XML string. See \ParadoxLabs\TokenBase\Model\Gateway\Xml
     *
     * @param string $rootName
     * @param array $array
     * @return string
     */
    protected function arrayToXml($rootName, $array)
    {
        $xml = $this->xml->createXML($rootName, $array);

        return $xml->saveXML();
    }

    /**
     * Convert XML string to array. See \ParadoxLabs\TokenBase\Model\Gateway\Xml
     *
     * @param string $xml
     * @return array
     * @throws \Exception
     */
    protected function xmlToArray($xml)
    {
        try {
            return $this->xml->createArray($xml);
        } catch (Throwable $e) {
            $this->helper->log($this->code, $e->getMessage() . "\n" . $this->sanitizeLog($xml));

            throw $e;
        }
    }

    /**
     * These should be implemented by the child gateway.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return $this
     */
    public function setCard(CardInterface $card)
    {
        parent::setData('card', $card);

        return $this;
    }

    /**
     * Return the card set on the gateway (if any).
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     */
    public function getCard()
    {
        return parent::getData('card');
    }

    /**
     * Set authorization code for the next transaction
     *
     * @param string $authCode
     * @return $this
     */
    public function setAuthCode($authCode)
    {
        $this->setParameter('auth_code', $authCode);

        return $this;
    }

    /**
     * Have we already authorized? Used for certain capture cases.
     *
     * @return bool
     */
    public function getHaveAuthorized()
    {
        return $this->haveAuthorized;
    }

    /**
     * Set haveAuthorized state for next capture.
     *
     * @param $haveAuthorized
     * @return $this
     */
    public function setHaveAuthorized($haveAuthorized)
    {
        $this->haveAuthorized = (bool)$haveAuthorized;

        return $this;
    }

    /**
     * Get transaction ID.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getParameter('transaction_id');
    }

    /**
     * Set prior transaction ID for next transaction.
     *
     * @param $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->setParameter('transaction_id', $transactionId);

        return $this;
    }

    /**
     * Run an auth transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function authorize(InfoInterface $payment, $amount);

    /**
     * Run a capture transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function capture(InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a refund transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function refund(InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a void transaction for the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function void(InfoInterface $payment, $transactionId = null);

    /**
     * Fetch a transaction status update
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function fraudUpdate(InfoInterface $payment, $transactionId);
}
