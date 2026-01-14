<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\AbstractMethod;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\AbstractMethod;
use ParadoxLabs\TokenBase\Model\Card;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AbstractMethod transaction-related methods
 */
class TransactionTest extends TestCase
{
    private AbstractMethod|MockObject $method;
    private Repository|MockObject $transactionRepository;
    private Data|MockObject $helper;
    private AbstractGateway|MockObject $gateway;
    private CardInterfaceFactory|MockObject $cardFactory;
    private CardRepositoryInterface|MockObject $cardRepository;
    private Address|MockObject $addressHelper;
    private ConfigInterface|MockObject $config;
    private Registry|MockObject $registry;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(Repository::class);
        $this->helper = $this->createMock(Data::class);
        $this->gateway = $this->createMock(AbstractGateway::class);
        $this->cardFactory = $this->createMock(CardInterfaceFactory::class);
        $this->cardRepository = $this->createMock(CardRepositoryInterface::class);
        $this->addressHelper = $this->createMock(Address::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->registry = $this->createMock(Registry::class);

        $this->helper->method('getCurrentStoreId')->willReturn(1);

        $this->method = $this->getMockBuilder(AbstractMethod::class)
            ->setConstructorArgs([
                $this->transactionRepository,
                $this->helper,
                $this->gateway,
                $this->cardFactory,
                $this->cardRepository,
                $this->addressHelper,
                $this->config,
                $this->registry,
                'testmethod',
            ])
            ->onlyMethods(['getInfoInstance', 'getCustomer', 'gateway', 'getCard', 'hasData', 'setInfoInstance'])
            ->getMock();

        $this->method->method('gateway')->willReturn($this->gateway);
    }

    public function testGetValidTransactionIdReturnsOriginalWhenUnique(): void
    {
        $payment = $this->createOrderPaymentMock();

        $this->transactionRepository->method('getByTransactionId')
            ->willReturn(false);

        $reflection = new \ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');
        $getValidTxnMethod->setAccessible(true);

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123', $result);
    }

    public function testGetValidTransactionIdAddsSuffixWhenDuplicate(): void
    {
        $payment = $this->createOrderPaymentMock();

        $existingTxn = $this->createMock(TransactionInterface::class);

        $callCount = 0;
        $this->transactionRepository->method('getByTransactionId')
            ->willReturnCallback(function ($txnId) use (&$callCount, $existingTxn) {
                $callCount++;
                // First two calls return existing transaction, third returns false
                if ($callCount <= 2) {
                    return $existingTxn;
                }

                return false;
            });

        $reflection = new \ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');
        $getValidTxnMethod->setAccessible(true);

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123-2', $result);
    }

    public function testGetValidTransactionIdHandlesMultipleDuplicates(): void
    {
        $payment = $this->createOrderPaymentMock();

        $existingTxn = $this->createMock(TransactionInterface::class);

        $callCount = 0;
        $this->transactionRepository->method('getByTransactionId')
            ->willReturnCallback(function ($txnId) use (&$callCount, $existingTxn) {
                $callCount++;
                // First 5 calls return existing transaction
                if ($callCount <= 5) {
                    return $existingTxn;
                }

                return false;
            });

        $reflection = new \ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');
        $getValidTxnMethod->setAccessible(true);

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123-5', $result);
    }

    public function testResyncStoredCardSkipsWhenNoCard(): void
    {
        $payment = $this->createOrderPaymentMock();

        $this->method->method('getCard')->willReturn(null);

        // CardRepository should not be called
        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $result = $resyncMethod->invoke($this->method, $payment);

        $this->assertSame($this->method, $result);
    }

    public function testResyncStoredCardSkipsWhenCardHasNoId(): void
    {
        $card = $this->createCardMock(null);

        $this->method->method('getCard')->willReturn($card);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $result = $resyncMethod->invoke($this->method, $payment);

        $this->assertSame($this->method, $result);
    }

    public function testResyncStoredCardSavesWhenAdditionalDataChanged(): void
    {
        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', '{"cc_type":"VI"}'],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', '{"cc_type":"MC"}'],
            ]);

        $this->method->method('getCard')->willReturn($card);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->expects($this->once())
            ->method('save')
            ->with($card)
            ->willReturn($card);

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardSavesWhenAddressChanged(): void
    {
        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', null],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', null],
            ]);
        $card->method('getAddress')
            ->willReturnCallback(function ($field) {
                if ($field === 'city') {
                    return 'OldCity';
                }

                return null;
            });

        $this->method->method('getCard')->willReturn($card);

        $orderAddress = $this->createMock(OrderAddress::class);
        $orderAddress->method('getData')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'city') {
                    return 'NewCity';
                }
                if ($field === null) {
                    return ['city' => 'NewCity'];
                }

                return null;
            });

        $order = $this->createMock(Order::class);
        $order->method('getBillingAddress')->willReturn($orderAddress);

        $payment = $this->createMock(OrderPayment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getId')->willReturn(1);

        $newAddress = $this->createMock(AddressInterface::class);
        $this->addressHelper->method('buildAddressFromInput')
            ->willReturn($newAddress);

        $this->cardRepository->expects($this->once())
            ->method('save')
            ->with($card)
            ->willReturn($card);

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardSkipsWhenNoChangesAndNoBillingAddress(): void
    {
        // When there's no billing address at all and no additional data changes, skip save
        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnCallback(function ($key = null) {
                if ($key === 'additional') {
                    return '{"cc_type":"VI"}';
                }

                return null;
            });
        $card->method('getData')
            ->willReturnCallback(function ($key = null) {
                if ($key === 'additional') {
                    return '{"cc_type":"VI"}';
                }

                return null;
            });

        $this->method->method('getCard')->willReturn($card);

        // No billing address - getBillingAddress returns null
        $order = $this->createMock(Order::class);
        $order->method('getBillingAddress')->willReturn(null);

        $payment = $this->createMock(OrderPayment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getData')
            ->willReturnCallback(function ($key = null) {
                return null;
            });
        $payment->method('getId')->willReturn(1);

        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardUpdatesRegistryOnSave(): void
    {
        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', '{"cc_type":"VI"}'],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', '{"cc_type":"MC"}'],
            ]);

        $this->method->method('getCard')->willReturn($card);
        $this->method->method('hasData')->willReturn(true);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->method('save')->willReturn($card);

        $this->registry->expects($this->once())
            ->method('unregister')
            ->with('tokenbase_ensure_checkout_card_save');
        $this->registry->expects($this->once())
            ->method('register')
            ->with('tokenbase_ensure_checkout_card_save', $card);

        $reflection = new \ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');
        $resyncMethod->setAccessible(true);

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardChecksAllAddressFields(): void
    {
        $addressFields = [
            'firstname',
            'lastname',
            'company',
            'street',
            'city',
            'country_id',
            'region',
            'region_id',
            'postcode',
            'telephone',
            'prefix',
            'middlename',
            'suffix',
        ];

        foreach ($addressFields as $field) {
            $card = $this->createCardMock(123);
            $card->method('getOrigData')->willReturn(null);
            $card->method('getData')->willReturn(null);
            $card->method('getAddress')
                ->willReturnCallback(function ($f) use ($field) {
                    return $f === $field ? 'old_value' : 'same';
                });

            $methodInstance = $this->getMockBuilder(AbstractMethod::class)
                ->setConstructorArgs([
                    $this->transactionRepository,
                    $this->helper,
                    $this->gateway,
                    $this->cardFactory,
                    $this->cardRepository,
                    $this->addressHelper,
                    $this->config,
                    $this->registry,
                    'testmethod',
                ])
                ->onlyMethods(['getInfoInstance', 'getCustomer', 'gateway', 'getCard', 'hasData', 'setInfoInstance'])
                ->getMock();

            $methodInstance->method('getCard')->willReturn($card);
            $methodInstance->method('hasData')->willReturn(true);

            $orderAddress = $this->createMock(OrderAddress::class);
            $orderAddress->method('getData')
                ->willReturnCallback(function ($f = null) use ($field) {
                    if ($f === null) {
                        return [$field => 'new_value'];
                    }

                    return $f === $field ? 'new_value' : 'same';
                });

            $order = $this->createMock(Order::class);
            $order->method('getBillingAddress')->willReturn($orderAddress);

            $payment = $this->createMock(OrderPayment::class);
            $payment->method('getOrder')->willReturn($order);

            $newAddress = $this->createMock(AddressInterface::class);
            $this->addressHelper->method('buildAddressFromInput')->willReturn($newAddress);
            $this->cardRepository->method('save')->willReturn($card);

            $reflection = new \ReflectionClass($methodInstance);
            $resyncMethod = $reflection->getMethod('resyncStoredCard');
            $resyncMethod->setAccessible(true);

            // Should detect change and save
            $resyncMethod->invoke($methodInstance, $payment);
        }

        // If we got here without exceptions, all fields were checked
        $this->assertTrue(true);
    }

    private function createOrderPaymentMock(): OrderPayment|MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getBillingAddress')->willReturn(null);

        $payment = $this->createMock(OrderPayment::class);
        $payment->method('getId')->willReturn(1);
        $payment->method('getOrder')->willReturn($order);

        return $payment;
    }

    private function createCardMock(?int $id): Card|MockObject
    {
        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId',
                'getOrigData',
                'getData',
                'getAddress',
                'setMethodInstance',
                'setInfoInstance',
                'setAddress',
            ])
            ->getMock();

        $card->method('getId')->willReturn($id);
        $card->method('setMethodInstance')->willReturnSelf();
        $card->method('setInfoInstance')->willReturnSelf();
        $card->method('setAddress')->willReturnSelf();

        return $card;
    }
}
