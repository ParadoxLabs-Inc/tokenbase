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

namespace ParadoxLabs\TokenBase\Controller\Paymentinfo;

use Magento\Framework\Controller\Result\Redirect;
use ParadoxLabs\TokenBase\Model\Card;
use Magento\Quote\Model\Quote\Payment;
use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data;
use Magento\Quote\Model\Quote\PaymentFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Controller\Paymentinfo;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Model\CardFactory;
use Throwable;

/**
 * Save the card create/edit form
 */
class Save extends Paymentinfo
{
    /**
     * @param Context $context
     * @param Session $customerSession *Proxy
     * @param PageFactory $resultPageFactory
     * @param Validator $formKeyValidator
     * @param Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param Address $addressHelper
     * @param PaymentFactory $paymentFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     * @param Data $paymentHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        Validator $formKeyValidator,
        Registry $registry,
        CardFactory $cardFactory,
        CardRepositoryInterface $cardRepository,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        Address $addressHelper,
        protected readonly PaymentFactory $paymentFactory,
        protected readonly \Magento\Checkout\Model\Session $checkoutSession,
        protected readonly Data $paymentHelper
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $resultPageFactory,
            $formKeyValidator,
            $registry,
            $cardFactory,
            $cardRepository,
            $helper,
            $addressHelper
        );
    }

    /**
     * Save action
     *
     * @return Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id     = $this->getRequest()->getParam('id');
        $method = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true) {
            /**
             * Convert inputs into an address and payment object for storage.
             */
            try {
                /**
                 * Load the card and verify we are actually the cardholder before doing anything.
                 */
                /** @var Card $card */
                if (!empty($id)) {
                    $card = $this->cardRepository->getByHash($id);
                } else {
                    $card = $this->cardFactory->create();
                    $card->setMethod($card->getMethod() ?: $method);
                }

                $card     = $card->getTypeInstance();
                $customer = $this->helper->getCurrentCustomer();

                if ($card && (empty($id) || ($card->getHash() == $id && $card->hasOwner($customer->getId())))) {
                    /**
                     * Process address data
                     */
                    $newAddrId = (int)$this->getRequest()->getParam('billing_address_id');

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
                    $cardData            = $this->getRequest()->getParam('payment');
                    $cardData['method']  = $method;
                    $cardData['card_id'] = $card->getId() > 0 ? $card->getHash() : '';

                    if (isset($cardData['cc_number'])) {
                        $cardData['cc_last4'] = substr((string)$cardData['cc_number'], -4);
                        $cardData['cc_bin']   = substr((string)$cardData['cc_number'], 0, 6);
                    }

                    /** @var Payment $newPayment */
                    $newPayment = $this->paymentFactory->create();
                    $newPayment->setQuote($this->checkoutSession->getQuote());
                    $newPayment->getQuote()->getBillingAddress()->setCountryId($newAddr->getCountryId());
                    $newPayment->setData('tokenbase_source', 'paymentinfo');
                    $newPayment->importData($cardData);

                    $paymentMethod = $this->paymentHelper->getMethodInstance($card->getMethod());
                    $paymentMethod->setInfoInstance($newPayment);
                    $paymentMethod->validate();

                    /**
                     * Save payment data
                     */
                    $card->setMethod($method);
                    $card->setActive(1);
                    $card->setCustomer($customer);
                    $card->setAddress($newAddr);
                    $card->importPaymentInfo($newPayment);
                    $card->setLastUse(time()); // Ignore 'save new card' checkbox at checkout, updated on first order.

                    $card = $this->cardRepository->save($card);

                    $this->session->unsData('tokenbase_form_data');

                    $this->messageManager->addSuccessMessage(__('Payment data saved successfully.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Invalid Request.'));
                }
            } catch (Throwable $e) {
                $this->session->setData('tokenbase_form_data', $this->getRequest()->getParams());

                $this->helper->log($method, (string)$e);
                $this->messageManager->addErrorMessage(__($e->getMessage()));

                $this->recordSessionFailure($e);
            }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid Request.'));
        }

        $resultRedirect->setPath('*/*', ['method' => $method, '_secure' => true]);

        return $resultRedirect;
    }

    /**
     * Record each save failure on their session. If they fail too many times in a given period, block access. This is
     * to help prevent credit card validation abuse, trying to store CCs until one works.
     *
     * @param \Exception $e
     * @return void
     */
    protected function recordSessionFailure(Exception $e)
    {
        $failures = $this->session->getData('tokenbase_failures');
        if (is_array($failures) === false) {
            $failures = [];
        }

        $failures[ time() ] = $e->getMessage();

        $this->session->setData('tokenbase_failures', $failures);
    }
}
