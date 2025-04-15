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

namespace ParadoxLabs\TokenBase\Plugin\QuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Resolver\Context;
use Magento\Framework\GraphQl\Query\Resolver\Value;

/**
 * SelectedPaymentMethod Class
 */
class SelectedPaymentMethod
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
     * SelectedPaymentMethod constructor.
     *
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
     * Set cache validity to the cacheableQuery after resolving any resolver in a query
     *
     * @param ResolverInterface $subject
     * @param mixed|Value $resolvedValue
     * @param Field $field
     * @param Context $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        ResolverInterface $subject,
        $resolvedValue,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (empty($resolvedValue) || $value['model'] instanceof \Magento\Quote\Model\Quote === false) {
            return $resolvedValue;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $value['model'];
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();

        if (in_array($payment->getMethod(), $this->helper->getAllMethods(), true)) {
            $resolvedValue['tokenbase_data'] = $payment->getData() + $payment->getAdditionalInformation();
            $resolvedValue['tokenbase_data']['cc_last4'] = $payment->getData('cc_last_4');
            $resolvedValue['tokenbase_save'] = (bool)$payment->getAdditionalInformation('save');

            if (!empty($payment->getData('tokenbase_id'))) {
                try {
                    $card = $this->cardRepository->getById($payment->getData('tokenbase_id'));
                    $resolvedValue['tokenbase_card_id'] = $card->getHash();
                } catch (\Exception $exception) {
                    // No-op
                }
            }
        }

        return $resolvedValue;
    }
}
