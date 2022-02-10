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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * PaymentMethodAssignDataObserver Class
 */
class PaymentMethodAssignDataObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->helper = $helper;
        $this->cardRepository = $cardRepository;
    }

    /**
     * Assign data to the payment instance for our methods.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Payment\Model\MethodInterface $method */
        $method = $observer->getData('method');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getData('payment_model');

        // Magento 2.0 compatibility
        if ($payment === null) {
            $payment = $method->getInfoInstance();
        }

        /** @var \Magento\Framework\DataObject $data */
        $data = $observer->getData('data');

        /**
         * Merge together data from additional_data array
         */
        if ($data->hasData('additional_data')) {
            foreach ($data->getData('additional_data') as $key => $value) {
                if ($data->getData($key) == false) {
                    $data->setData($key, $value);
                }
            }
        }

        $this->helper->log($payment->getMethod(), sprintf('assignData(%s)', $data->getData('card_id')));

        $this->assignStandardData($payment, $data, $method);

        $this->assignTokenbaseData($payment, $data, $method);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \Magento\Framework\DataObject $data
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return void
     */
    protected function assignStandardData(
        \Magento\Payment\Model\InfoInterface $payment,
        \Magento\Framework\DataObject $data,
        \Magento\Payment\Model\MethodInterface $method
    ) {
        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $ccNumber = preg_replace('/[^X\d]/', '', (string)$data->getData('cc_number'));

        $payment->setData('cc_type', $data->getData('cc_type'));
        $payment->setData('cc_owner', $data->getData('cc_owner'));
        $payment->setData('cc_last_4', substr($ccNumber, -4));
        $payment->setData('cc_number', $ccNumber);
        $payment->setData('cc_cid', preg_replace('/[^\d]/', '', (string)$data->getData('cc_cid')));
        $payment->setData('cc_exp_month', $data->getData('cc_exp_month'));
        $payment->setData('cc_exp_year', $data->getData('cc_exp_year'));
        $payment->setData('cc_ss_issue', $data->getData('cc_ss_issue'));
        $payment->setData('cc_ss_start_month', $data->getData('cc_ss_start_month'));
        $payment->setData('cc_ss_start_year', $data->getData('cc_ss_start_year'));

        if ($method->getConfigData('can_store_bin') == 1) {
            $payment->setAdditionalInformation('cc_bin', substr($ccNumber, 0, 6));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \Magento\Framework\DataObject $data
     * @param \Magento\Payment\Model\MethodInterface $method
     * @return void
     */
    protected function assignTokenbaseData(
        \Magento\Payment\Model\InfoInterface $payment,
        \Magento\Framework\DataObject $data,
        \Magento\Payment\Model\MethodInterface $method
    ) {
        /** @var \Magento\Sales\Model\Order\Payment $payment */

        if ($data->hasData('card_id') && $data->getData('card_id') != '') {
            /**
             * Load and validate the chosen card.
             *
             * If we are in checkout, force load by hash rather than numeric ID. Bit harder to guess.
             */
            if ($this->helper->getIsFrontend() || !is_numeric($data->getData('card_id'))) {
                $this->loadAndSetCard($payment, $data->getData('card_id'), true);
            } else {
                $this->loadAndSetCard($payment, $data->getData('card_id'));
            }

            /**
             * Overwrite data if necessary
             */
            if ($data->hasData('cc_type') && $data->getData('cc_type') != '') {
                $payment->setData('cc_type', $data->getData('cc_type'));
            }

            if ($data->hasData('cc_last4') && $data->getData('cc_last4') != '') {
                $payment->setData('cc_last_4', $data->getData('cc_last4'));
            }

            if (!empty($data->getData('cc_bin')) && $method->getConfigData('can_store_bin') == 1) {
                $payment->setAdditionalInformation('cc_last_4', $data->getData('cc_bin'));
            }

            if ($data->getData('cc_exp_year') != '' && $data->getData('cc_exp_month') != '') {
                $payment->setData('cc_exp_year', $data->getData('cc_exp_year'));
                $payment->setData('cc_exp_month', $data->getData('cc_exp_month'));
            }
        } elseif ($payment->hasData('tokenbase_card') === false
            || $payment->getData('tokenbase_card')->getId() !== $payment->getData('tokenbase_id')) {
            $payment->setData('tokenbase_id', null);
        }

        if ($data->hasData('save')) {
            $payment->setAdditionalInformation('save', (int)$data->getData('save'));
        }
    }

    /**
     * Load the given card by ID, authenticate, and store with the object.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param int|string $cardId
     * @param bool $byHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function loadAndSetCard(
        \Magento\Payment\Model\InfoInterface $payment,
        $cardId,
        $byHash = false
    ) {
        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $this->helper->log(
            $payment->getMethod(),
            sprintf('observer::loadAndSetCard(%s, %s)', $cardId, var_export($byHash, true))
        );

        try {
            $card = $this->cardRepository->getById($cardId);

            if ($card && $card->getId() > 0 && ($byHash === false || $card->getHash() == $cardId)) {
                $this->setCardOnPayment($payment, $card);

                return $card;
            }
        } catch (\Magento\Framework\Exception\StateException $e) {
            $this->helper->log($payment->getMethod(), $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            // Any error is inability to load card -- handle same as auth failure.
        }

        /**
         * This error will be thrown if the card does not exist OR if we don't have permission to use it.
         */
        $this->helper->log(
            $payment->getMethod(),
            sprintf('Unable to load payment data. Please check the form and try again.')
        );

        throw new \Magento\Framework\Exception\LocalizedException(
            __('Unable to load payment data. Please check the form and try again.')
        );
    }

    /**
     * Set the current payment card
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return $this
     */
    protected function setCardOnPayment(
        \Magento\Payment\Model\InfoInterface $payment,
        \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
    ) {
        /** @var \Magento\Sales\Model\Order\Payment $payment */

        $this->helper->log(
            $payment->getMethod(),
            sprintf('observer::setCard(%s)', $card->getId())
        );

        $payment->setData('tokenbase_id', $card->getId())
                ->setData('cc_type', $card->getType())
                ->setData('cc_last_4', $card->getAdditional('cc_last4'))
                ->setData('cc_exp_month', $card->getAdditional('cc_exp_month'))
                ->setData('cc_exp_year', $card->getAdditional('cc_exp_year'))
                ->setData('tokenbase_card', $card);

        if (!empty($card->getAdditional('cc_bin'))) {
            $payment->setAdditionalInformation('cc_bin', $card->getAdditional('cc_bin'));
        }

        return $this;
    }
}
