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
 * StoredAch Class
 */
class StoredAch extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
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
         * If there is an ID, this might be an edit. Validate as much as we can.
         */
        if ($payment->hasData('tokenbase_id') !== false) {
            if ($payment->getData('echeck_account_name') != ''
                && strlen((string)$payment->getData('echeck_account_name')) > 22) {
                $fails[] = __('Please limit your account name to 22 characters.');
            }

            if ($payment->getData('echeck_routing_no') != ''
                && substr((string)$payment->getData('echeck_routing_no'), 0, 4) != 'XXXX'
            ) {
                // If not masked and not 9 digits, or not numeric...
                if (strlen((string)$payment->getData('echeck_routing_no')) != 9
                    || !is_numeric($payment->getData('echeck_routing_no'))
                ) {
                    $fails[] = __('Your routing number must be 9 digits long. Please recheck the value you entered.');
                }
            }

            if ($payment->getData('echeck_account_no') != ''
                && substr((string)$payment->getData('echeck_account_no'), 0, 4) != 'XXXX'
            ) {
                // If not masked and not 5-17 digits, or not numeric...
                if (strlen((string)$payment->getData('echeck_account_no')) < 5
                    || strlen((string)$payment->getData('echeck_account_no')) > 17
                    || !is_numeric($payment->getData('echeck_account_no'))
                ) {
                    $fails[] = __(
                        'Your account number must be between 5 and 17 digits. Please recheck the value you entered.'
                    );
                }
            }
        }

        return $this->createResult(empty($fails), $fails);
    }
}
