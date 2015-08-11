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

namespace ParadoxLabs\TokenBase\Controller\Paymentinfo;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Save the card create/edit form
 */
class Save extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
{
    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->paymentFactory   = $paymentFactory;
        $this->checkoutSession  = $checkoutSession;

        parent::__construct(
            $context,
            $customerSession,
            $resultPageFactory,
            $formKeyValidator,
            $registry,
            $cardFactory,
            $helper,
            $addressHelper
        );
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id            = $this->getRequest()->getParam('id');
        $method        = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true) {
            /**
             * Convert inputs into an address and payment object for storage.
             */
            try {
                /**
                 * Load the card and verify we are actually the cardholder before doing anything.
                 */
                $card       = $this->cardFactory->create();
                $card->setMethod($method);
                $card->loadByHash($id);
                $card       = $card->getTypeInstance();
                $customer   = $this->helper->getCurrentCustomer();

                if ($card && (empty($id) || ($card->getHash() == $id && $card->hasOwner($customer->getId())))) {
                    /**
                     * Process address data
                     */
                    $newAddrId    = intval($this->getRequest()->getParam('billing_address_id'));

                    if ($newAddrId > 0) {
                        // Existing address
                        $newAddr = $this->addressHelper->repository()->getById($newAddrId);

                        if ($newAddr->getCustomerId() != $customer->getId()) {
                            throw new LocalizedException(__('An error occurred. Please try again.'));
                        }
                    } else {
                        // New address
                        $newAddr = $this->addressHelper->buildAddressFromInput(
                            $this->getRequest()->getParam('billing', []),
                            is_array($card->getAddress()) ? $card->getAddress() : [],
                            true
                        );
                    }

                    /**
                     * Process payment data
                     */
                    $cardData = $this->getRequest()->getParam('payment');
                    $cardData['method']     = $method;
                    $cardData['card_id']    = $card->getHash();

                    if (isset($cardData['cc_number'])) {
                        $cardData['cc_last4'] = substr($cardData['cc_number'], -4);
                    }

                    /** @var \Magento\Quote\Model\Quote\Payment $newPayment */
                    $newPayment = $this->paymentFactory->create();
                    $newPayment->setQuote($this->checkoutSession->getQuote());
                    $newPayment->getQuote()->getBillingAddress()->setCountryId($newAddr->getCountryId());
                    $newPayment->importData($cardData);

                    /**
                     * Save payment data
                     */
                    $card->setMethod($method);
                    $card->setCustomer($customer);
                    $card->setAddress($newAddr);
                    $card->importPaymentInfo($newPayment);
                    $card->save();

                    $this->_getSession()->unsTokenbaseFormData();

                    $this->messageManager->addSuccess(__('Payment data saved successfully.'));
                } else {
                    $this->messageManager->addError(__('Invalid Request.'));
                }
            } catch (\Exception $e) {
                $this->_getSession()->setTokenbaseFormData($this->getRequest()->getParams());

                $this->helper->log($method, (string)$e);
                $this->messageManager->addError(__($e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Invalid Request.'));
        }

        $resultRedirect->setPath('*/*', ['method' => $method, '_secure' => true]);
        return $resultRedirect;
    }
}
