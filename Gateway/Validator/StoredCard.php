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
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard $ccValidator
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard $ccValidator
    ) {
        parent::__construct($resultFactory);

        $this->ccValidator = $ccValidator;
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
