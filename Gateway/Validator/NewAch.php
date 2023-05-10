<?php
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
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Gateway\Validator;

/**
 * Ach Class
 */
class NewAch extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var array
     */
    protected $achFields = [
        'echeck_account_name',
        'echeck_bank_name',
        'echeck_routing_no',
        'echeck_account_no',
        'echeck_account_type',
    ];

    /**
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $fails = [];

        /** @var \Magento\Payment\Model\Info $payment */
        $payment = $validationSubject['payment'];

        /**
         * If no tokenbase ID, we must have a new card. Make sure all the details look valid.
         */
        if ($payment->hasData('tokenbase_id') === false) {
            // Fields all present?
            foreach ($this->achFields as $field) {
                $value = trim((string)$payment->getData($field));

                if (empty($value)) {
                    $fails[] = __('Please complete all required fields.');
                    return $this->createResult(false, $fails);
                }
            }

            // Field lengths?
            if (strlen((string)$payment->getData('echeck_account_name')) > 22) {
                $fails[] = __('Please limit your account name to 22 characters.');
            } elseif (strlen((string)$payment->getData('echeck_routing_no')) != 9) {
                $fails[] = __('Your routing number must be 9 digits long. Please recheck the value you entered.');
            } elseif (strlen((string)$payment->getData('echeck_account_no')) < 5
                || strlen((string)$payment->getData('echeck_account_no')) > 17) {
                $fails[] = __(
                    'Your account number must be between 5 and 17 digits. Please recheck the value you entered.'
                );
            }

            // Data types?
            if (!is_numeric($payment->getData('echeck_routing_no'))) {
                $fails[] = __('Your routing number must be 9 digits long. Please recheck the value you entered.');
            } elseif (!is_numeric($payment->getData('echeck_account_no'))) {
                $fails[] = __(
                    'Your account number must be between 5 and 17 digits. Please recheck the value you entered.'
                );
            }
        }

        return $this->createResult(empty($fails), $fails);
    }
}
