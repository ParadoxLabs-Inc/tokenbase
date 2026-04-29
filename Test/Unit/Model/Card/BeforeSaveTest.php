<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\Card;

use ArrayIterator;
use DateTime;
use Magento\Framework\Event\ManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Model\Card\Context as CardContext;
use ParadoxLabs\TokenBase\Model\Method\Factory as MethodFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card as CardResource;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for Card::beforeSave() and related methods
 */
class BeforeSaveTest extends TestCase
{
    private Card $card;
    private ModelContext|MockObject $context;
    private Registry|MockObject $registry;
    private ExtensionAttributesFactory|MockObject $extensionFactory;
    private AttributeValueFactory|MockObject $customAttributeFactory;
    private CardContext|MockObject $cardContext;
    private Data|MockObject $helper;
    private MethodFactory|MockObject $methodFactory;
    private OrderCollectionFactory|MockObject $orderCollectionFactory;
    private TimezoneInterface|MockObject $dateProcessor;
    private CardResource|MockObject $resource;
    private CollectionFactory|MockObject $cardCollectionFactory;
    private RemoteAddress|MockObject $remoteAddress;

    protected function setUp(): void
    {
        // Initialize ObjectManager
        $this->resource = $this->createMock(CardResource::class);
        $this->resource->method('getIdFieldName')->willReturn('id');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')
            ->willReturnCallback(function ($className) {
                if ($className === CardResource::class) {
                    return $this->resource;
                }

                return $this->createMock($className);
            });
        ObjectManager::setInstance($objectManager);

        $this->context = $this->createMock(ModelContext::class);
        $this->registry = $this->createMock(Registry::class);
        $this->extensionFactory = $this->createMock(ExtensionAttributesFactory::class);
        $this->customAttributeFactory = $this->createMock(AttributeValueFactory::class);
        $this->cardContext = $this->createMock(CardContext::class);
        $this->helper = $this->createMock(Data::class);
        $this->methodFactory = $this->createMock(MethodFactory::class);
        $this->orderCollectionFactory = $this->createMock(OrderCollectionFactory::class);
        $this->dateProcessor = $this->createMock(TimezoneInterface::class);
        $this->cardCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);

        $customerFactory = $this->createMock(CustomerInterfaceFactory::class);
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);

        $this->cardContext->method('getHelper')->willReturn($this->helper);
        $this->cardContext->method('getMethodFactory')->willReturn($this->methodFactory);
        $this->cardContext->method('getOrderCollectionFactory')->willReturn($this->orderCollectionFactory);
        $this->cardContext->method('getDateProcessor')->willReturn($this->dateProcessor);
        $this->cardContext->method('getCustomerFactory')->willReturn($customerFactory);
        $this->cardContext->method('getCustomerRepository')->willReturn($customerRepository);
        $this->cardContext->method('getCardCollectionFactory')->willReturn($this->cardCollectionFactory);
        $this->cardContext->method('getRemoteAddress')->willReturn($this->remoteAddress);

        $customer = $this->createMock(CustomerInterface::class);
        $customerFactory->method('create')->willReturn($customer);

        $eventManager = $this->createMock(ManagerInterface::class);
        $this->context->method('getEventDispatcher')->willReturn($eventManager);

        // Set up default date mock
        $dateMock = $this->createMock(DateTime::class);
        $dateMock->method('format')->willReturn('2025-01-14 12:00:00');
        $this->dateProcessor->method('date')->willReturn($dateMock);

        $this->card = new Card(
            $this->context,
            $this->registry,
            $this->extensionFactory,
            $this->customAttributeFactory,
            $this->cardContext,
            $this->resource,
        );
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(ObjectManager::class);
        $property = $reflection->getProperty('_instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testBeforeSaveCleansProtectedAdditionalData(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setAdditional([
            'cc_type' => 'VI',
            'cc_last4' => '1111',
            'cc_cid' => '123',
            'cc_number' => '4111111111111111',
            'token' => 'secret_token',
        ]);

        // Set up empty collection
        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->card->beforeSave();

        $additional = $this->card->getAdditional();
        $this->assertSame('VI', $additional['cc_type']);
        $this->assertSame('1111', $additional['cc_last4']);
        $this->assertArrayNotHasKey('cc_cid', $additional);
        $this->assertArrayNotHasKey('cc_number', $additional);
        $this->assertArrayNotHasKey('token', $additional);
    }

    public function testBeforeSaveGeneratesHashIfEmpty(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setData('customer_email', 'test@example.com');

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->card->beforeSave();

        $hash = $this->card->getData('hash');
        $this->assertNotEmpty($hash);
        $this->assertSame(40, strlen((string) $hash));
    }

    public function testBeforeSaveRecordsIpOnFrontend(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->helper->method('getIsFrontend')->willReturn(true);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('192.168.1.100');

        $this->card->beforeSave();

        $this->assertSame('192.168.1.100', $this->card->getData('customer_ip'));
    }

    public function testBeforeSaveDoesNotRecordIpOnBackend(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->helper->method('getIsFrontend')->willReturn(false);

        $this->card->beforeSave();

        $this->assertNull($this->card->getData('customer_ip'));
    }

    public function testBeforeSaveSetsCreatedAtForNewObject(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->isObjectNew(true);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->card->beforeSave();

        $this->assertSame('2025-01-14 12:00:00', $this->card->getData('created_at'));
    }

    public function testBeforeSaveSetsUpdatedAt(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->card->beforeSave();

        $this->assertSame('2025-01-14 12:00:00', $this->card->getData('updated_at'));
    }

    public function testBeforeSaveDetectsDuplicateWhenPaymentIdChanges(): void
    {
        // Existing card with new payment_id
        $this->card->setData('id', 1);
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setData('payment_id', 'new_payment_id');
        $this->card->setOrigData('payment_id', 'old_payment_id');

        // Create a duplicate card mock
        $duplicateCard = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $duplicateCard->method('getId')->willReturn(null);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getFirstItem')->willReturn($duplicateCard);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $this->card->beforeSave();

        // No merge should happen since duplicate has no ID
        $this->assertSame(1, $this->card->getId());
    }

    public function testBeforeSaveMergesOntoExistingDuplicate(): void
    {
        // New card (no ID yet)
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setData('payment_id', 'duplicate_payment_id');
        $this->card->setOrigData('payment_id', null);

        // Create a duplicate card mock that exists
        $duplicateCard = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $duplicateCard->method('getId')->willReturn(999);

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(1);
        $collection->method('getFirstItem')->willReturn($duplicateCard);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        // Expect log call for merge
        $this->helper->expects($this->once())
            ->method('log')
            ->with(
                'testmethod',
                $this->callback(function ($phrase) {
                    return stripos((string)$phrase, 'Merging duplicate') !== false;
                }),
            );

        $this->card->beforeSave();

        // Card should have been merged to duplicate's ID
        $this->assertSame(999, $this->card->getId());
    }

    public function testBeforeSaveNoDuplicateCheckWhenPaymentIdUnchanged(): void
    {
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setData('payment_id', 'same_id');
        $this->card->setOrigData('payment_id', 'same_id');

        // Collection should NOT be created since payment_id hasn't changed
        $this->cardCollectionFactory->expects($this->never())
            ->method('create');

        $this->card->beforeSave();
    }

    public function testBeforeSaveRemovesMultipleDuplicates(): void
    {
        $this->card->setData('id', 1);
        $this->card->setData('method', 'testmethod');
        $this->card->setData('customer_id', 123);
        $this->card->setData('payment_id', 'new_payment_id');
        $this->card->setOrigData('payment_id', 'old_payment_id');

        // Create duplicate cards
        $dupe1 = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getProfileId'])
            ->getMock();
        $dupe1->method('getId')->willReturn(2);
        $dupe1->method('getProfileId')->willReturn('profile2');

        $dupe2 = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getProfileId'])
            ->getMock();
        $dupe2->method('getId')->willReturn(3);
        $dupe2->method('getProfileId')->willReturn('profile3');

        $collection = $this->createMock(Collection::class);
        $collection->method('getSize')->willReturn(3);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new ArrayIterator([$this->card, $dupe1, $dupe2]));
        $collection->method('getFirstItem')->willReturn($this->createMock(Card::class));
        $this->cardCollectionFactory->method('create')->willReturn($collection);

        // Set up connection mock for update queries
        $connection = $this->createMock(AdapterInterface::class);
        $this->resource->method('getConnection')->willReturn($connection);
        $this->resource->method('getTable')->willReturnArgument(0);

        // Expect deletes for duplicates (not current card)
        $deletedCards = [];
        $this->resource->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function ($card) use (&$deletedCards) {
                $deletedCards[] = $card->getId();
            });

        $this->card->beforeSave();

        // Verify the correct cards were deleted
        $this->assertContains(2, $deletedCards);
        $this->assertContains(3, $deletedCards);
        $this->assertNotContains(1, $deletedCards);
    }

    public function testCleanAdditionalDataPreservesNonProtectedKeys(): void
    {
        $this->card->setAdditional([
            'cc_type' => 'MC',
            'cc_last4' => '5100',
            'cc_exp_year' => '2030',
            'cc_exp_month' => '12',
            'some_custom_key' => 'custom_value',
            'cc_cid' => '456',
        ]);

        // Access protected method via reflection
        $reflection = new ReflectionClass($this->card);
        $method = $reflection->getMethod('cleanAdditionalData');
        $method->setAccessible(true);
        $method->invoke($this->card);

        $additional = $this->card->getAdditional();
        $this->assertSame('MC', $additional['cc_type']);
        $this->assertSame('5100', $additional['cc_last4']);
        $this->assertSame('2030', $additional['cc_exp_year']);
        $this->assertSame('12', $additional['cc_exp_month']);
        $this->assertSame('custom_value', $additional['some_custom_key']);
        $this->assertArrayNotHasKey('cc_cid', $additional);
    }
}
