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

namespace ParadoxLabs\TokenBase\Gateway\Command;

/**
 * Capture Class
 */
class CaptureCommand implements \Magento\Payment\Gateway\CommandInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\MethodInterface
     */
    protected $method;

    /**
     * @param \ParadoxLabs\TokenBase\Api\MethodInterface $method
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\MethodInterface $method
    ) {
        $this->method = $method;
    }

    /**
     * Run a capture transaction on the given subject.
     *
     * @param array $commandSubject
     * @return null|\Magento\Payment\Gateway\Command\ResultInterface
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        /** @var double $amount */
        $amount = $commandSubject['amount'];

        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDataObject */
        $paymentDataObject = $commandSubject['payment'];

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $this->method->setInfoInstance($payment);
        $this->method->setStore($paymentDataObject->getOrder()->getStoreId());
        $this->method->capture($payment, $amount);

        return null;
    }
}
