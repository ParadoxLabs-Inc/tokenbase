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

namespace ParadoxLabs\TokenBase\Gateway\Command;

/**
 * Cancel Class
 */
class CancelCommand implements \Magento\Payment\Gateway\CommandInterface
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
     * Run a cancel transaction on the given subject.
     *
     * @param array $commandSubject
     * @return null|\Magento\Payment\Gateway\Command\ResultInterface
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDataObject */
        $paymentDataObject = $commandSubject['payment'];

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $this->method->setInfoInstance($payment);
        $this->method->setStore($paymentDataObject->getOrder()->getStoreId());
        $this->method->cancel($payment);

        return null;
    }
}
