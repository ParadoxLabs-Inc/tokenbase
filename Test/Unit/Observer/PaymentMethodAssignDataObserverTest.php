<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Observer\PaymentMethodAssignDataObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the payment assignData observer's 'save this card' handling
 */
class PaymentMethodAssignDataObserverTest extends TestCase
{
    private PaymentMethodAssignDataObserver $observer;
    private Data|MockObject $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);

        $this->observer = new PaymentMethodAssignDataObserver(
            $this->helper,
            $this->createMock(CardRepositoryInterface::class)
        );
    }

    /**
     * With save forced by config, a submitted save=0 must not deactivate the card.
     *
     * @return void
     */
    public function testForcedSaveOverridesSubmittedZero(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * With save optional, the customer's choice must be respected.
     *
     * @return void
     */
    public function testOptionalSaveRespectsSubmittedZero(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '1'));

        $this->assertSame(0, $payment->getAdditionalInformation('save'));
    }

    /**
     * A submitted save=1 is stored as-is either way.
     *
     * @return void
     */
    public function testSubmittedSaveIsKept(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 1], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * Guests cannot vault, so forced save must not apply to them.
     *
     * @return void
     */
    public function testForcedSaveDoesNotApplyToGuests(): void
    {
        $payment = $this->createQuotePayment(null);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(0, $payment->getAdditionalInformation('save'));
    }

    /**
     * Forced save also applies when assigning to an order payment.
     *
     * @return void
     */
    public function testForcedSaveAppliesToOrderPayment(): void
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerId'])
            ->getMock();
        $order->method('getCustomerId')->willReturn(5);

        $payment = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrder', 'getExtensionAttributes'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getExtensionAttributes')->willReturn(null);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * Build a quote payment owned by the given customer (null for a guest).
     *
     * @param int|null $customerId
     * @return QuotePayment|MockObject
     */
    private function createQuotePayment(?int $customerId)
    {
        // Left unstubbed: getCustomerId() is a DataObject magic method, which PHPUnit cannot stub.
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $quote->setData('customer_id', $customerId);

        $payment = $this->getMockBuilder(QuotePayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote', 'getExtensionAttributes'])
            ->getMock();
        $payment->method('getQuote')->willReturn($quote);
        $payment->method('getExtensionAttributes')->willReturn(null);

        return $payment;
    }

    /**
     * Build the assignData event with the given submitted data and allow_unsaved config.
     *
     * @param object $payment
     * @param array $data
     * @param string $allowUnsaved
     * @return Observer
     */
    private function createObserver($payment, array $data, string $allowUnsaved): Observer
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getConfigData')
            ->willReturnCallback(
                static function ($field) use ($allowUnsaved) {
                    return $field === 'allow_unsaved' ? $allowUnsaved : null;
                }
            );

        return new Observer(
            [
                'method' => $method,
                'payment_model' => $payment,
                'data' => new DataObject($data),
            ]
        );
    }
}
