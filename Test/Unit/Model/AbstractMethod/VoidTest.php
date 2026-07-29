<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\AbstractMethod;

use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Exception\VoidNotNeededException;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\AbstractMethod;
use ParadoxLabs\TokenBase\Model\Gateway\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AbstractMethod void/cancel error handling
 */
class VoidTest extends TestCase
{
    private AbstractMethod|MockObject $method;
    private AbstractGateway|MockObject $gateway;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(AbstractGateway::class);

        $helper = $this->createMock(Data::class);
        $helper->method('getCurrentStoreId')->willReturn(1);

        $this->method = $this->getMockBuilder(AbstractMethod::class)
            ->setConstructorArgs([
                $this->createMock(Repository::class),
                $helper,
                $this->gateway,
                $this->createMock(CardInterfaceFactory::class),
                $this->createMock(CardRepositoryInterface::class),
                $this->createMock(Address::class),
                $this->createMock(ConfigInterface::class),
                $this->createMock(Registry::class),
                'testmethod',
            ])
            ->onlyMethods(['gateway', 'getCard', 'loadOrCreateCard', 'log'])
            ->getMock();

        $this->method->method('gateway')->willReturn($this->gateway);
        $this->method->method('getCard')->willReturn(null);
    }

    /**
     * A successful void closes the transaction, as before.
     *
     * @return void
     */
    public function testSuccessfulVoidClosesTransaction(): void
    {
        $payment = $this->createPaymentMock();

        $response = $this->createMock(Response::class);
        $response->method('getData')->willReturn(['response_code' => 1]);
        $this->gateway->method('void')->willReturn($response);

        $this->method->void($payment);

        $this->assertTrue($payment->getData('should_close_parent_transaction'));
        $this->assertTrue($payment->getData('is_transaction_closed'));
    }

    /**
     * A gateway telling us the void was unnecessary is still treated as a void.
     *
     * @return void
     */
    public function testExpectedFailureIsTreatedAsVoided(): void
    {
        $payment = $this->createPaymentMock();

        $this->gateway->method('void')
            ->willThrowException(new VoidNotNeededException(__('Authorization already expired.')));

        $this->method->void($payment);

        $this->assertTrue($payment->getData('should_close_parent_transaction'));
        $this->assertTrue($payment->getData('is_transaction_closed'));
    }

    /**
     * An unclassified gateway failure must be reported, not swallowed as success.
     *
     * @return void
     */
    public function testUnexpectedFailureThrowsAndDoesNotCloseTransaction(): void
    {
        $payment = $this->createPaymentMock();

        $this->gateway->method('void')
            ->willThrowException(new CommandException(__('Transaction failed. Already settled.')));

        try {
            $this->method->void($payment);
            $this->fail('Expected PaymentException was not thrown.');
        } catch (PaymentException $exception) {
            $this->assertSame(
                'Unable to void payment: Transaction failed. Already settled.'
                . ' The authorization may still be open at the payment gateway.',
                $exception->getMessage()
            );
        }

        $this->assertFalse($payment->getData('should_close_parent_transaction'));
        $this->assertFalse($payment->getData('is_transaction_closed'));
    }

    /**
     * Order cancellation must not be blocked by a failed void, but must report it on the order.
     *
     * @return void
     */
    public function testCancelReportsFailureWithoutThrowing(): void
    {
        $payment = $this->createPaymentMock();
        $payment->setMessage('Canceled order online');

        $this->gateway->method('void')
            ->willThrowException(new CommandException(__('Transaction failed. Already settled.')));

        $this->method->cancel($payment);

        $this->assertFalse($payment->getData('should_close_parent_transaction'));
        $this->assertFalse($payment->getData('is_transaction_closed'));
        $this->assertSame(
            'Canceled order online Unable to void payment: Transaction failed. Already settled.'
            . ' The authorization may still be open at the payment gateway.',
            (string)$payment->getMessage()
        );
    }

    /**
     * Gateways can classify their own benign failures via isExpectedVoidFailure().
     *
     * @return void
     */
    public function testGatewayClassifierCanMarkFailureExpected(): void
    {
        $helper = $this->createMock(Data::class);
        $helper->method('getCurrentStoreId')->willReturn(1);

        $method = $this->getMockBuilder(AbstractMethod::class)
            ->setConstructorArgs([
                $this->createMock(Repository::class),
                $helper,
                $this->gateway,
                $this->createMock(CardInterfaceFactory::class),
                $this->createMock(CardRepositoryInterface::class),
                $this->createMock(Address::class),
                $this->createMock(ConfigInterface::class),
                $this->createMock(Registry::class),
                'testmethod',
            ])
            ->onlyMethods(['gateway', 'getCard', 'loadOrCreateCard', 'log', 'isExpectedVoidFailure'])
            ->getMock();

        $method->method('gateway')->willReturn($this->gateway);
        $method->method('getCard')->willReturn(null);
        $method->method('isExpectedVoidFailure')->willReturn(true);

        $this->gateway->method('void')
            ->willThrowException(new CommandException(__('Transaction not found.')));

        $payment = $this->createPaymentMock();

        $method->void($payment);

        $this->assertTrue($payment->getData('should_close_parent_transaction'));
        $this->assertTrue($payment->getData('is_transaction_closed'));
    }

    /**
     * Build an order payment with a usable auth transaction ID.
     *
     * Left unstubbed so the DataObject magic setters (setShouldCloseParentTransaction, setMessage, ...)
     * behave normally; PHPUnit cannot stub undeclared methods.
     *
     * @return OrderPayment|MockObject
     */
    private function createPaymentMock()
    {
        $payment = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $payment->setData('parent_transaction_id', 'txn123');

        return $payment;
    }
}
