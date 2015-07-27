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

namespace ParadoxLabs\TokenBase\Model;

/**
 * Common API gateway methods, logging, exceptions, etc.
 */
// TODO: this
abstract class AbstractGateway extends \Magento\Framework\Model\AbstractModel
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
     * $fields sets validation for each input.
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
    protected $fields           = array();

    /**
     * These hold parameters for each request.
     *
     * @var array
     */
    protected $params           = array();

    /**
     * @var array
     */
    protected $defaults         = array();

    /**
     * @var string
     */
    protected $lastRequest;

    /**
     * @var string
     */
    protected $lastResponse;

    /**
     * @var array|null
     */
    protected $lineItems        = null;

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
     * Initialize the gateway. Input is taken as an array for greater flexibility.
     *
     * @param array $parameters
     * @return $this
     */
    public function init(array $parameters)
    {
        $this->secretKey    = isset($parameters['secret_key']) ? $parameters['secret_key'] : '';
        $this->testMode     = isset($parameters['test_mode']) ? (bool)$parameters['test_mode'] : false;

        $this->defaults     = array(
            'login'     => $parameters['login'],
            'password'  => $parameters['password']
        );

        if (isset($parameters['endpoint'])) {
            $this->endpoint = $parameters['endpoint'];
        } else {
            $this->endpoint = ($this->testMode === true ? $this->endpointTest : $this->endpointLive);
        }

        $this->clearParameters();

        return $this;
    }

    /**
     * Set the API parameters back to defaults, clearing any runtime values.
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
     */
    public function setParameter($key, $val)
    {
        if (!empty($val)) {
            /**
             * Make sure we know this parameter
             */
            if (in_array($key, array_keys($this->fields))) {
                /**
                 * Run validations
                 */

                if (isset($this->fields[ $key ]['noSymbols']) && $this->fields[ $key ]['noSymbols'] === true) {
                    /**
                     * Convert special characters to simple ascii equivalent
                     */
                    $val = htmlentities($val, ENT_QUOTES, 'UTF-8');
                    $val = preg_replace('/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i', '$1', $val);
                    $val = preg_replace(array( '/[^0-9a-z \.]/i', '/-+/' ), ' ', $val);
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
                        //                        Mage::throwException( __( sprintf( "Payment Gateway: Invalid value for '%s': '%s'", $key, $val ) ) );
                    }
                } else {
                    $this->params[ $key ] = $val;
                }
            } else {
                //                Mage::throwException( __( sprintf( "Payment Gateway: Unknown parameter '%s'", $key ) ) );
            }
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
    public function getParameter($key, $default='')
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
        return (isset($this->params[ $key ]) && !empty($this->params[ $key ]) ? true : false);
    }

    /**
     * Get the last response value.
     *
     * @return string
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
        //        log( $this->code, $this->log );

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
        return sprintf("%01.2f", (float) $amount);
    }

    /**
     * Convert array to XML string. See tokenbase/gateway_xml
     *
     * @param $rootName
     * @param $array
     */
    // TODO: This
    protected function arrayToXml($rootName, $array)
    {
        //        $xml = Mage::getModel('tokenbase/gateway_xml')->createXML( $rootName, $array );

//        return $xml->saveXML();
    }

    /**
     * Convert XML string to array. See tokenbase/gateway_xml
     *
     * @param $xml
     */
    // TODO: This
    protected function xmlToArray($xml)
    {
        //        return Mage::getModel('tokenbase/gateway_xml')->createArray( $xml );
    }

    /**
     * These should be implemented by the child gateway.
     * @param Card $card
     * @return $this
     */
    public function setCard(\ParadoxLabs\Tokenbase\Model\Card $card)
    {
        return parent::setData('card', $card);
    }

    public function isInitialized()
    {
    }

    public function reset()
    {
    }

    /**
     * @param $payment
     * @param $amount
     * @return \Magento\Framework\Object
     */
    public function authorize($payment, $amount)
    {
    }

    public function setHaveAuthorized($true)
    {
    }

    public function setAuthCode($int)
    {
    }

    public function setTransactionId($int)
    {
    }

    /**
     * @param $payment
     * @param $amount
     * @param $realTransactionId
     * @return \Magento\Framework\Object
     */
    public function capture($payment, $amount, $realTransactionId)
    {
    }

    public function getHaveAuthorized()
    {
    }

    public function refund($payment, $amount, $realTransactionId)
    {
    }

    /**
     * @param $payment
     * @return \Magento\Framework\Object
     */
    public function void($payment)
    {
    }

    /**
     * @param $payment
     * @param $transactionId
     * @return \Magento\Framework\Object
     */
    public function fraudUpdate($payment, $transactionId)
    {
    }
}
