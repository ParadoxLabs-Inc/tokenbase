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

namespace ParadoxLabs\TokenBase\Gateway\Validator\CreditCard;

/**
 * Credit card types
 *
 * Defines common credit card types and number pattern matching. Not guaranteed to be perfect.
 *
 * See equivalent frontend validator:
 * Magento_Payment::base/web/js/model/credit-card-validation/credit-card-number-validator/credit-card-type.js
 */
class Types
{
    private $types = [
        [
            'title'   => 'Visa',
            'type'    => 'VI',
            'pattern' => '^4\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [13, 16, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVV',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'MasterCard',
            'type'    => 'MC',
            'pattern' => '^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$',
            'gaps'    => [4, 8, 12],
            'lengths' => [16],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVC',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'American Express',
            'type'    => 'AE',
            'pattern' => '^3([47]\d*)?$',
            'gaps'    => [4, 10],
            'lengths' => [15],
            'luhn'    => true,
            'code'    => [
                'name' => 'CID',
                'size' => 4,
            ],
        ],
        [
            'title'   => 'Diners',
            'type'    => 'DN',
            'pattern' => '^(3(0[0-5]|095|6|[8-9]))\d*$',
            'gaps'    => [4, 10],
            'lengths' => [14, 16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVV',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'Discover',
            'type'    => 'DI',
            'pattern' => '^(6011(0|[2-4]|74|7[7-9]|8[6-9]|9)|6(4[4-9]|5))\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CID',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'JCB',
            'type'    => 'JCB',
            'pattern' => '^35(2[8-9]|[3-8])\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVV',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'UnionPay',
            'type'    => 'UN',
            'pattern' => '^(622(1(2[6-9]|[3-9])|[3-8]|9([0-1]|2[0-5]))|62[4-6]|628([2-8]))\d*?$',
            'gaps'    => [4, 8, 12],
            'lengths' => [16, 17, 18, 19],
            'luhn'    => false,
            'code'    => [
                'name' => 'CVN',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'Maestro International',
            'type'    => 'MI',
            'pattern' => '^(5(0|[6-9])|63|67(?!59|6770|6774))\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVC',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'Maestro Domestic',
            'type'    => 'MD',
            'pattern' => '^6759(?!24|38|40|6[3-9]|70|76)|676770|676774\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVC',
                'size' => 3,
            ],
        ],
        [
            'title'   => 'Other',
            'type'    => 'OT',
            'pattern' => '^\d*$',
            'gaps'    => [4, 8, 12],
            'lengths' => [12, 13, 14, 15, 16, 17, 18, 19],
            'luhn'    => true,
            'code'    => [
                'name' => 'CVV',
                'size' => 3,
            ],
        ],
    ];

    /**
     * Get CC types array
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Attempt to get the CC type for a given credit card number. Requires the full card number.
     *
     * @param string $ccNumber
     * @return array|false
     */
    public function getTypeForCard($ccNumber)
    {
        foreach ($this->types as $type) {
            if (preg_match('/' . $type['pattern'] . '/', (string)$ccNumber) === 1) {
                return $type;
            }
        }

        return false;
    }

    /**
     * Get type information for a given type code.
     *
     * @param string $code 2-char CC type
     * @return array|false
     */
    public function getType($code)
    {
        foreach ($this->types as $type) {
            if ($type['type'] == $code) {
                return $type;
            }
        }

        return false;
    }
}
