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

namespace ParadoxLabs\TokenBase\Model;

use ParadoxLabs\TokenBase\Api\GatewayInterface;

/**
 * Common API gateway methods, logging, exceptions, etc.
 */
abstract class AbstractGateway extends \Magento\Framework\DataObject implements GatewayInterface
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
    protected $fields           = [];

    /**
     * These hold parameters for each request.
     *
     * @var array
     */
    protected $params           = [];

    /**
     * @var array
     */
    protected $defaults         = [];

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
    protected $log              = '';

    /**
     * @var string
     */
    protected $endpointLive     = '';

    /**
     * @var string
     */
    protected $endpointTest     = '';

    /**
     * @var bool
     */
    protected $initialized      = false;

    /**
     * @var bool
     */
    protected $haveAuthorized   = false;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Gateway\Xml
     */
    protected $xml;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var
     */
    protected $httpClientFactory;

    /**
     * Constructor, yeah!
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\Gateway\Xml $xml
     * @param \ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory $responseFactory
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param array $data
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Model\Gateway\Xml $xml,
        \ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory $responseFactory,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        array $data = []
    ) {
        $this->helper           = $helper;
        $this->responseFactory  = $responseFactory;
        $this->xml              = $xml;
        $this->httpClientFactory = $httpClientFactory;

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
        $this->secretKey    = isset($parameters['secret_key']) ? $parameters['secret_key'] : '';
        $this->testMode     = isset($parameters['test_mode']) ? (bool)$parameters['test_mode'] : false;
        $this->verifySsl    = isset($parameters['verify_ssl']) ? (bool)$parameters['verify_ssl'] : false;

        $this->defaults     = [
            'login'     => $parameters['login'],
            'password'  => $parameters['password']
        ];

        if (isset($parameters['endpoint'])) {
            $this->endpoint = $parameters['endpoint'];
        } else {
            $this->endpoint = ($this->testMode === true ? $this->endpointTest : $this->endpointLive);
        }

        $this->clearParameters();

        $this->initialized  = true;

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
        $this->defaults     = [];
        $this->secretKey    = '';
        $this->testMode     = null;
        $this->endpoint     = null;

        $this->clearParameters();

        $this->initialized  = false;

        return $this;
    }

    /**
     * Set the API parameters back to defaults, clearing any runtime values.
     *
     * @return $this
     */
    public function clearParameters()
    {
        $this->params       = $this->defaults;
        $this->log          = '';
        $this->lineItems    = null;

        return $this;
    }

    /**
     * Set a parameter.
     *
     * @param string $key
     * @param mixed $val
     * @return $this
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setParameter($key, $val)
    {
        if (!empty($val)) {
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
                    $val = htmlentities($val, ENT_QUOTES, 'UTF-8');
                    $val = preg_replace(
                        '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
                        '$1',
                        $val
                    );
                    $val = preg_replace(['/[^0-9a-z \.\-]/i', '/\s{2,}/'], ' ', $val);
                    $val = trim($val);
                }

                if (isset($this->fields[ $key ]['charMask'])) {
                    /**
                     * Apply a regex character filter to the input.
                     */
                    $val = preg_replace('/[^' . $this->fields[ $key ]['charMask'] . ']/', '', $val);
                }

                if (isset($this->fields[ $key ]['maxLength']) && $this->fields[ $key ]['maxLength'] > 0) {
                    /**
                     * Truncate if the value is too long
                     */
                    $this->params[ $key ] = substr($val, 0, $this->fields[ $key ]['maxLength']);
                } elseif (isset($this->fields[ $key ]['enum'])) {
                    /**
                     * Error if value is not on the allowed list
                     */
                    if (in_array($val, $this->fields[ $key ]['enum'])) {
                        $this->params[ $key ] = $val;
                    } else {
                        throw new \Magento\Framework\Exception\PaymentException(
                            __(sprintf("Payment Gateway: Invalid value for '%s': '%s'", $key, $val))
                        );
                    }
                } else {
                    $this->params[ $key ] = $val;
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(sprintf("Payment Gateway: Unknown parameter '%s'", $key))
                );
            }
        } elseif ($val === null) {
            unset($this->params[$key]);
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
        return (isset($this->params[ $key ]) ? $this->params[ $key ] : $default);
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
        return sprintf('%01.2f', (float) $amount);
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
        } catch (\Exception $e) {
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
    public function setCard(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
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
    abstract public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /**
     * Run a capture transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a refund transaction for $amount with the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount, $transactionId = null);

    /**
     * Run a void transaction for the given payment info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function void(\Magento\Payment\Model\InfoInterface $payment, $transactionId = null);

    /**
     * Fetch a transaction status update
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return \ParadoxLabs\TokenBase\Model\Gateway\Response
     */
    abstract public function fraudUpdate(\Magento\Payment\Model\InfoInterface $payment, $transactionId);
}
