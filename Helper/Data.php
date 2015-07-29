<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <magento@paradoxlabs.com>
 * @license        http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Helper;

/**
 * Class Data
 */
class Data extends \Magento\Payment\Helper\Data
{
    /**
     * @var \ParadoxLabs\TokenBase\Model\Card
     */
    protected $card;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Resource\Card\Collection[]
     */
    protected $cards;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Logger\Logger
     */
    protected $tokenbaseLogger;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Framework\App\Config\Initial $initialConfig
     * @param \Magento\Framework\App\State $appState
     * @param \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Framework\App\State $appState,
        \ParadoxLabs\TokenBase\Model\Logger\Logger $tokenbaseLogger
    ) {
        $this->appState = $appState;
        $this->tokenbaseLogger = $tokenbaseLogger;

        parent::__construct(
            $context,
            $layoutFactory,
            $paymentMethodFactory,
            $appEmulation,
            $paymentConfig,
            $initialConfig
        );
    }


    /**
     * Return active payment methods (if any) implementing tokenbase.
     *
     * @return array
     */
    public function getActiveMethods()
    {
        $methods = [];

        foreach ($this->getPaymentMethods() as $code => $data) {
            if (isset($data['group']) && $data['group'] == 'tokenbase'
                && isset($data['active']) && $data['active'] == 1) {
                $methods[] = $code;
            }
        }

        return $methods;
    }

    /**
     * Return all tokenbase-derived payment methods, without an active check.
     *
     * @return array
     */
    public function getAllMethods()
    {
        $methods = [];

        foreach ($this->getPaymentMethods() as $code => $data) {
            if (isset($data['group']) && $data['group'] == 'tokenbase') {
                $methods[] = $code;
            }
        }

        return $methods;
    }

    /**
     * Return store scope based on the available info... the admin panel makes this complicated.
     */
    public function getCurrentStoreId()
    {
        // TODO: this
        // ???

        return 1;
    }

    /**
     * Return current customer based on the available info.
     */
    public function getCurrentCustomer()
    {
        // TODO: this
        // ???

        return new \Magento\Customer\Model\Customer;
    }

    /**
     * Return active card model for edit (if any).
     *
     * @param string|null $method
     * @return \ParadoxLabs\TokenBase\Model\Card
     */
    public function getActiveCard($method = null)
    {
        // TODO: this
        // ???

        return $this->card;
    }

    /**
     * Get stored cards for the currently-active method.
     *
     * @param string|null $method
     * @return array
     */
    public function getActiveCustomerCardsByMethod($method = null)
    {
        // TODO: this
        // ???

        return $this->cards[ $method ];
    }

    /**
     * Check whether we are in the frontend area.
     *
     * @return bool
     */
    public function getIsFrontend()
    {
        if ($this->appState->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND) {
            return true;
        }

        return false;
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
     * Write a message to the logs, nice and abstractly.
     *
     * @param string $code
     * @param mixed $message
     * @return $this
     */
    public function log($code, $message)
    {
        if (is_object($message)) {
            if ($message instanceof \Magento\Framework\Object) {
                $message = $message->getData();
                
                $this->cleanupArray($message);
            } else {
                $message = (array)$message;
            }
        }
        
        if (is_array($message)) {
            $message = print_r($message, 1);
        }

        $this->tokenbaseLogger->info(sprintf('[%s] %s', $code, $message));

        return $this;
    }
}
