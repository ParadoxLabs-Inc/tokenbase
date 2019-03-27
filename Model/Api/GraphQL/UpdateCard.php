<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Api\GraphQL;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * UpdateCard Class
 *
 * NB: NOT implementing ResolverInterface because it's not enforced (as of 2.3.1), and it breaks 2.1/2.2 compat.
 */
class UpdateCard
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface
     */
    protected $customerCardRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface
     */
    protected $guestCardRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Api\GraphQL
     */
    protected $graphQL;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory
     */
    protected $cardFactory;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Quote\Api\Data\CartInterfaceFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * Card constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository
     * @param \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface $guestCardRepository
     * @param \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory
     * @param \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CustomerCardRepositoryInterface $customerCardRepository,
        \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface $guestCardRepository,
        \ParadoxLabs\TokenBase\Model\Api\GraphQL $graphQL,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory $cardFactory,
        \Magento\Quote\Model\Quote\PaymentFactory $paymentFactory,
        \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory,
        \Magento\Payment\Helper\Data $paymentHelper
    ) {
        $this->customerCardRepository = $customerCardRepository;
        $this->guestCardRepository = $guestCardRepository;
        $this->graphQL = $graphQL;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerRepository = $customerRepository;
        $this->cardFactory = $cardFactory;
        $this->paymentFactory = $paymentFactory;
        $this->quoteFactory = $quoteFactory;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return mixed|\Magento\Framework\GraphQl\Query\Resolver\Value
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $this->graphQL->authenticate($context);

        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $cardData = $args['input'];

        /**
         * Get card
         */
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        $card = $this->getCard($context, $cardData);

        /**
         * Set/update card data
         */
        $this->updateCardData($context, $card, $cardData);
        $this->updatePaymentInfo($cardData, $card);

        /** @var \Magento\Customer\Model\Data\Address $address */
        $address    = $this->updateAddressData($card, $cardData);
        $additional = $card->getAdditionalObject();

        /**
         * Save changes
         */
        if ($context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $this->customerCardRepository->saveExtended($context->getUserId(), $card, $address, $additional);
        } else {
            $this->guestCardRepository->saveExtended($card, $address, $additional);
        }

        /**
         * Output
         */
        return $this->graphQL->convertCardForOutput($card);
    }

    /**
     * Load or create card model
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param array $cardData
     * @return \ParadoxLabs\TokenBase\Model\Card
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCard(\Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context, $cardData)
    {
        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        if (isset($cardData['hash'])) {
            if ($context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
                $card = $this->customerCardRepository->getByHash($context->getUserId(), $cardData['hash']);
            } else {
                $card = $this->guestCardRepository->getByHash($cardData['hash']);
            }
        } else {
            $card = $this->cardFactory->create();
            $card->setMethod($cardData['method']);
        }

        $card = $card->getTypeInstance();

        return $card;
    }

    /**
     * Process card data (including customer assignment)
     *
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param array $cardData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCardData(
        \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context,
        \ParadoxLabs\TokenBase\Model\Card $card,
        $cardData
    ) {
        // Associate customer
        $customerId = $context->getUserId();
        if ($customerId > 0 && $context->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $customer = $this->customerRepository->getById($customerId);
            $card->setCustomer($customer);
        } else {
            $card->setCustomerId(0);
        }

        // Ignore complex values. Assume all others are okay because GraphQL schema will reject any not allowed.
        $settableValues = array_diff_key(
            $cardData,
            [
                'address' => 1,
                'additional' => 1,
            ]
        );

        $this->dataObjectHelper->populateWithArray(
            $card,
            $settableValues,
            \ParadoxLabs\TokenBase\Api\Data\CardInterface::class
        );
    }

    /**
     * Process address data
     *
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param array $cardData
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function updateAddressData(\ParadoxLabs\TokenBase\Model\Card $card, $cardData)
    {
        $address = $card->getAddressObject();
        if (isset($cardData['address'])) {
            $this->dataObjectHelper->populateWithArray(
                $address,
                $cardData['address'],
                \Magento\Customer\Api\Data\AddressInterface::class
            );

            if (isset($cardData['address']['region']['region_id'])) {
                $address->setRegionId($cardData['address']['region']['region_id']);
            }
        }

        return $address;
    }

    /**
     * Process payment data
     *
     * @param array $cardData
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updatePaymentInfo($cardData, \ParadoxLabs\TokenBase\Model\Card $card)
    {
        if (!isset($cardData['additional']) || empty($cardData['additional'])) {
            return;
        }

        $paymentData            = $cardData['additional'];
        $paymentData['method']  = $card->getMethod();
        $paymentData['card_id'] = $card->getId() > 0 ? $card->getHash() : '';

        if (isset($paymentData['cc_number'])) {
            $paymentData['cc_last4'] = substr($paymentData['cc_number'], -4);
            $paymentData['cc_bin']   = substr($paymentData['cc_number'], 0, 6);
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $this->paymentFactory->create();
        $payment->setQuote($quote);
        $payment->getQuote()->getBillingAddress()->setCountryId($card->getAddress('country_id'));
        $payment->importData($paymentData);

        $paymentMethod = $this->paymentHelper->getMethodInstance($card->getMethod());
        $paymentMethod->setInfoInstance($payment);
        $paymentMethod->validate();

        $card->importPaymentInfo($payment);
    }
}
