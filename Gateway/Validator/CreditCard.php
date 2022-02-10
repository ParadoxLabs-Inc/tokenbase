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
 * CreditCard Class
 */
class CreditCard extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \Magento\Payment\Gateway\ConfigInterface
     */
    protected $config;

    /**
     * @var \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types
     */
    protected $ccTypes;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateProcessor;

    /**
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \Magento\Payment\Gateway\ConfigInterface $config
     * @param \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types $ccTypes
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Magento\Payment\Gateway\ConfigInterface $config,
        \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types $ccTypes,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateProcessor
    ) {
        parent::__construct($resultFactory);

        $this->config = $config;
        $this->ccTypes = $ccTypes;
        $this->dateProcessor = $dateProcessor;
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

        $tokenbaseId = $payment->getData('tokenbase_id');
        if (empty($tokenbaseId)) {
            $availableTypes = explode(',', (string)$this->config->getValue('cctypes'));

            $ccNumber = preg_replace('/[\-\s]+/', '', (string)$payment->getData('cc_number'));
            $payment->setData('cc_number', $ccNumber);

            $typeInfo = $this->ccTypes->getTypeForCard($ccNumber);
            if ($typeInfo) {
                // Validate credit card number and type
                if (in_array($typeInfo['type'], $availableTypes) === false) {
                    // Is the type allowed?
                    $isValid = false;
                    $fails[] = __('This credit card type is not allowed for this payment method.');
                } elseif ($typeInfo['luhn'] === true && $this->isCcNumberMod10Valid($ccNumber) !== true) {
                    // Is the card number valid?
                    $isValid = false;
                    $fails[] = __('Invalid credit card number.');
                }/* elseif (in_array(strlen($ccNumber), $typeInfo['lengths']) === false) {
                    // Is the length valid?
                    $isValid = false;
                    $fails[] = __('Invalid credit card number.');
                }*/

                // Validate CVV
                if ($this->config->getValue('useccv') == 1) {
                    if (!is_numeric($payment->getData('cc_cid'))
                        || strlen((string)$payment->getData('cc_cid')) != $typeInfo['code']['size']) {
                        $isValid = false;
                        $fails[] = __('Please enter a valid %1.', $typeInfo['code']['name']);
                    }
                }

                // Validate expiration date.
                if ($this->isDateExpired($payment->getData('cc_exp_year'), $payment->getData('cc_exp_month'))) {
                    $isValid = false;
                    $fails[] = __('Invalid credit card expiration date.');
                }
            } else {
                // We failed to detect the CC type. Welp. This is bad!
                $isValid = false;
                $fails[] = __('This credit card type is not allowed for this payment method.');
            }
        }

        return $this->createResult($isValid, $fails);
    }

    /**
     * Determine whether the given year and month are expired (in the past).
     *
     * @param int $year
     * @param int $month
     * @return bool
     */
    public function isDateExpired($year, $month)
    {
        $date  = $this->dateProcessor->date();

        $year  = (int)$year;
        $month = (int)$month;

        /**
         * Reject if year is invalid, or month is invalid, or year is past, or year is equal but month is past.
         */
        if ($year <= 0
            || $month <= 0
            || $month > 12
            || (int)$date->format('Y') > $year
            || ((int)$date->format('Y') == $year
                && (int)$date->format('m') > $month)) {
            return true;
        }

        return false;
    }

    /**
     * Validate credit card number based on the Luhn (mod10) algorithm.
     *
     * Based on Rolands Kusins's implementation at http://www.phpclasses.org/browse/file/51066.html (GPL)
     *
     * @param   string $ccNumber
     * @return  bool
     */
    public function isCcNumberMod10Valid($ccNumber)
    {
        // Double every other number minus the last, and all them all together.
        // This sum must be a multiple of 10 to be a valid credit card number.
        $number   = preg_replace('/[^\d]/', '', (string)$ccNumber);
        $sumTable = [
            [0,1,2,3,4,5,6,7,8,9],
            [0,2,4,6,8,1,3,5,7,9]
        ];

        $length = strlen((string)$number);
        $sum    = 0;
        $flip   = 1;

        for ($i = $length - 2; $i >= 0; --$i) {
            $sum += $sumTable[$flip++ & 0x1][$number[$i]];
        }

        $sum += (int)substr((string)$number, -1);

        return $sum % 10 === 0;
    }

    /**
     * Accessor for CC Types object.
     *
     * @return \ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types
     */
    public function getCcTypes()
    {
        return $this->ccTypes;
    }
}
