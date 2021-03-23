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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * ValidatePaymentObserver Class
 */
class ValidatePaymentObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ValidatorPoolInterface|null
     */
    protected $validatorPool;

    /**
     * ValidatePaymentObserver constructor.
     *
     * @param \Magento\Payment\Gateway\Validator\ValidatorPoolInterface|null $validatorPool
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ValidatorPoolInterface $validatorPool = null
    ) {
        $this->validatorPool = $validatorPool;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $order = $observer->getData('order');
        $quote = $observer->getData('quote');

        if ($order instanceof \Magento\Sales\Api\Data\OrderInterface) {
            $model = $order;
        } elseif ($quote instanceof \Magento\Quote\Api\Data\CartInterface) {
            $model = $quote;
        }

        if ($model->getPayment() instanceof \Magento\Payment\Model\InfoInterface === false) {
            return;
        }

        try {
            // Get payment method validation by method code (intended for tokenbase, but will work for any that use it).
            $validator = $this->validatorPool->get($model->getPayment()->getMethod());
            $result    = $validator->validate([
                'payment' => $model->getPayment(),
                'storeId' => $model->getStoreId(),
            ]);

            if (!$result->isValid()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __(implode("\n", $result->getFailsDescription()))
                );
            }
        } catch (\Magento\Framework\Exception\NotFoundException $exception) {
            // No validator pool for this payment method -- nothing to validate
            return;
        }
    }
}
