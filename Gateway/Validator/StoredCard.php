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

namespace ParadoxLabs\TokenBase\Gateway\Validator;

/**
 * StoredCard Validator
 */
class StoredCard extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard
     */
    private $ccValidator;

    /**
     * @var \Magento\Payment\Gateway\ConfigInterface
     */
    private $config;

    /**
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard $ccValidator
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard $ccValidator,
        \Magento\Payment\Gateway\ConfigInterface $config
    ) {
        parent::__construct($resultFactory);

        $this->ccValidator = $ccValidator;
        $this->config = $config;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $fails   = [];

        /** @var \Magento\Payment\Model\Info $payment */
        $payment = $validationSubject['payment'];

        /**
         * If we have a tokenbase ID, we're using a stored card.
         */
        $tokenbaseId = $payment->getData('tokenbase_id');
        if (!empty($tokenbaseId)) {
            /**
             * If Require CCV is enabled, enforce it.
             */
            if ($this->config->getValue('require_ccv') == 1) {
                $ccvLength = null;
                $ccvLabel  = 'CVV';

                $ccType = $payment->getData('cc_type');
                if (!empty($ccType)) {
                    $typeInfo = $this->ccValidator->getCcTypes()->getType($ccType);
                    if ($typeInfo !== false) {
                        $ccvLength = $typeInfo['code']['size'];
                        $ccvLabel  = $typeInfo['code']['name'];
                    }
                }

                if (!is_numeric($payment->getData('cc_cid'))
                    || ($ccvLength !== null && strlen($payment->getData('cc_cid')) != $ccvLength)
                    || strlen($payment->getData('cc_cid')) < 3) {
                    $isValid = false;
                    $fails[] = __('Please enter your credit card %1.', $ccvLabel);
                }
            }

            /**
             * This might be a card edit. Validate this too, as much as we can.
             */
            if ($payment->getData('cc_number') != '' && substr($payment->getData('cc_number'), 0, 4) != 'XXXX') {
                if (strlen($payment->getData('cc_number')) < 13
                    || !is_numeric($payment->getData('cc_number'))
                    || $this->ccValidator->isCcNumberMod10Valid($payment->getData('cc_number')) === false) {
                    $isValid = false;
                    $fails[] = __('Invalid credit card number.');
                }
            }

            $year  = $payment->getData('cc_exp_year');
            $month = $payment->getData('cc_exp_month');

            if ($this->ccValidator->isDateExpired($year, $month) === true) {
                $isValid = false;
                $fails[] = __('Invalid credit card expiration date.');
            }
        }

        return $this->createResult($isValid, $fails);
    }
}
