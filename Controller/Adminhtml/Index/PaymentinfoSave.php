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

namespace ParadoxLabs\TokenBase\Controller\Adminhtml\Index;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * TokenbaseCardsSave Class
 */
class PaymentinfoSave extends Paymentinfo
{
    /**
     * @var bool
     */
    protected $skipCardLoad = true;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory
     */
    protected $cardFactory;

    /**
     * PaymentinfoDelete constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $viewHelper
     * @param \Magento\Framework\Math\Random $random
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Mapper $addressMapper
     * @param AccountManagementInterface $customerAccountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $viewHelper,
        \Magento\Framework\Math\Random $random,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Mapper $addressMapper,
        AccountManagementInterface $customerAccountManagement,
        AddressRepositoryInterface $addressRepository,
        CustomerInterfaceFactory $customerDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->quoteFactory = $quoteFactory;
        $this->cardFactory = $cardFactory;

        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory,
            $cardRepository,
            $helper,
            $addressHelper
        );
    }

    /**
     * View customer's stored cards list (active view)
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id     = $this->getRequest()->getParam('card_id');
        $method = $this->getRequest()->getParam('method');

        $response = [
            'success' => false,
            'message' => '',
        ];

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true) {
            /**
             * Convert inputs into an address and payment object for storage.
             */
            try {
                $customer = $this->getCustomer();

                /**
                 * Load the card before doing anything.
                 */

                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                if (!empty($id)) {
                    $card = $this->cardRepository->getByHash($id);
                } else {
                    $card = $this->cardFactory->create();
                    $card->setMethod($card->getMethod() ?: $method);
                }

                $card       = $card->getTypeInstance();

                if ($card && (empty($id) || $card->getHash() == $id)) {
                    /**
                     * Process address data
                     */
                    $newAddrId    = (int)$this->getRequest()->getParam('billing_address_id');

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
                    $cardData['card_id']    = $card->getId() > 0 ? $card->getHash() : '';

                    if (isset($cardData['cc_number'])) {
                        $cardData['cc_last4'] = substr($cardData['cc_number'], -4);
                    }

                    /** @var \Magento\Quote\Model\Quote $quote */
                    $quote = $this->quoteFactory->create();
                    $quote->setCustomer($customer);

                    /** @var \Magento\Quote\Model\Quote\Payment $newPayment */
                    $newPayment = $this->paymentFactory->create();
                    $newPayment->setQuote($quote);
                    $newPayment->getQuote()->getBillingAddress()->setCountryId($newAddr->getCountryId());
                    $newPayment->importData($cardData);

                    /**
                     * Save payment data
                     */
                    $card->setMethod($method);
                    $card->setActive(1);
                    $card->setCustomer($customer);
                    $card->setAddress($newAddr);
                    $card->importPaymentInfo($newPayment);

                    $this->cardRepository->save($card);

                    $this->_session->setData('tokenbase_form_data', null);

                    $response['success'] = true;
                } else {
                    $response['message'] = __('Invalid card reference.');
                }
            } catch (\Exception $e) {
                $this->_session->setData('tokenbase_form_data', $this->getRequest()->getParams());

                $this->helper->log($method, (string)$e);
                $response['message'] = __($e->getMessage());
            }
        } else {
            $response['message'] = __('Invalid Request.');
        }

        if ($response['success'] === false) {
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($response);
        } else {
            // If successful, rebuild and output the entire tab.

            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('paymentinfo');
            return $resultForward;
        }
    }
}
