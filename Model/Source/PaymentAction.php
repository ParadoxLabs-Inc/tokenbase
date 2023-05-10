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

namespace ParadoxLabs\TokenBase\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Payment method settings: Payment actions
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        // Note: Not using AbstractMethod constants because that class is deprecated...

        return [
            [
                'value' => 'order',
                'label' => __('Save info (do not authorize)'),
            ],
            [
                'value' => 'authorize',
                'label' => __('Authorize'),
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture'),
            ],
        ];
    }
}
