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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * Custom data field conversion -- quote to order, etc, etc.
 */
abstract class ConvertAbstract
{
    /**
     * @var string[]
     */
    protected $fields = [
        'tokenbase_id',
        'echeck_account_name',
        'echeck_bank_name',
        'echeck_account_type',
        'echeck_routing_number',
        'echeck_routing_no',
        'echeck_account_no',
        'echeck_type',
    ];

    /**
     * Copy payment data fields from A to B
     *
     * @param \Magento\Framework\DataObject $from
     * @param \Magento\Framework\DataObject $to
     * @return void
     */
    protected function copyData(\Magento\Framework\DataObject $from, \Magento\Framework\DataObject $to)
    {
        // Do the magic. Yeah, this is it.
        foreach ($this->fields as $field) {
            $to->setData($field, $from->getData($field));
        }
    }
}
