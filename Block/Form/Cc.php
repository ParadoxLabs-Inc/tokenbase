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

namespace ParadoxLabs\TokenBase\Block\Form;

/**
 * Credit card input form on checkout for TokenBase methods.
 *
 * TODO: admin form is not selecting a card by default
 */
class Cc extends \Magento\Payment\Block\Form\Cc
{
    /**
     * @var string
     */
    protected $_template = 'ParadoxLabs_TokenBase::form/cc.phtml';

    /**
     * @var string
     */
    protected $brandingImage = '';

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection|array
     */
    protected $cards;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    protected $tokenbaseMethodFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    protected $tokenbaseMethod;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $tokenbaseMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \ParadoxLabs\TokenBase\Model\Method\Factory $tokenbaseMethodFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->tokenbaseMethodFactory = $tokenbaseMethodFactory;

        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Get/load stored cards for the current customer and method.
     *
     * @return \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection|array
     */
    public function getStoredCards()
    {
        if ($this->cards === null) {
            /**
             * If logged in, fetch the method cards for the current customer.
             * If not, short circuit / return empty array.
             */
            $customer = $this->helper->getCurrentCustomer();

            $this->cards = [];

            if ($this->helper->getIsFrontend() !== true || ($customer && $customer->getId() > 0)) {
                $this->cards = $this->helper->getActiveCustomerCardsByMethod($this->getMethodCode());
            }
        }

        return $this->cards;
    }

    /**
     * Check whether we have any cards stored.
     *
     * @return bool
     */
    public function haveStoredCards()
    {
        $cards = $this->getStoredCards();

        return (!empty($cards) ? true : false);
    }

    /**
     * Get branding image for the current method. Done thus so methods can avoid overriding the entire template.
     *
     * @return string|false
     */
    public function getBrandingImage()
    {
        if ($this->brandingImage && $this->getMethod()->getConfigData('show_branding') == 1) {
            return $this->getViewFileUrl($this->brandingImage);
        }

        return false;
    }

    /**
     * Check whether we are logged in or registering, or just a guest.
     *
     * @return bool
     */
    public function isGuestCheckout()
    {
        if ($this->helper->getIsFrontend() !== true) {
            return false;
        } elseif ($this->customerSession->isLoggedIn() !== true
                && $this->checkoutSession->getQuote()->getCheckoutMethod() != 'register') {
            return true;
        }

        return false;
    }

    /**
     * Expose helper to the template.
     *
     * @return \ParadoxLabs\TokenBase\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get Tokenbase payment method instance.
     *
     * @return \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    public function getTokenbaseMethod()
    {
        if ($this->tokenbaseMethod === null) {
            $this->tokenbaseMethod = $this->tokenbaseMethodFactory->getMethodInstance(
                $this->getMethodCode()
            );
        }

        return $this->tokenbaseMethod;
    }
}
