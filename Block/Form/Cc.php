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

namespace ParadoxLabs\TokenBase\Block\Form;

/**
 * Credit card input form on checkout for TokenBase methods.
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
     * @var \ParadoxLabs\TokenBase\Model\Resource\Card\Collection|array
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
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Get/load stored cards for the current customer and method.
     *
     * @return \ParadoxLabs\TokenBase\Model\Resource\Card\Collection|array
     */
    public function getStoredCards()
    {
        if (is_null($this->cards)) {
            /**
             * If logged in, fetch the method cards for the current customer.
             * If not, short circuit / return empty array.
             */
            $customer = $this->helper->getCurrentCustomer();

            if ($this->helper->getIsFrontend() !== true || ($customer && $customer->getId() > 0)) {
                $this->cards = $this->helper->getActiveCustomerCardsByMethod($this->getMethodCode());
            } else {
                $this->cards = array();
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

        return (count($cards) > 0 ? true : false);
    }

    /**
     * Get branding image for the current method. Put here so methods can avoid overriding the entire template.
     *
     * @return string
     */
    public function getBrandingImage()
    {
        return $this->brandingImage;
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
}
