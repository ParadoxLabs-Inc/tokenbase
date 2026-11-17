<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\ResourceModel;

use Exception;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Model\Quote\PaymentFactory;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface;
use ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterfaceFactory;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Model\CardFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card as CardResource;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\CardRepository;
use ParadoxLabs\TokenBase\Test\Unit\PartialStubTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CardRepository
 */
class CardRepositoryTest extends TestCase
{
    use PartialStubTrait;

    private CardRepository $repository;
    private CardResource|MockObject $resource;
    private CardFactory|MockObject $cardFactory;
    private CardInterfaceFactory|MockObject $dataCardFactory;
    private CollectionFactory|MockObject $cardCollectionFactory;
    private CardSearchResultsInterfaceFactory|MockObject $searchResultsFactory;
    private DataObjectHelper|MockObject $dataObjectHelper;
    private DataObjectProcessor|MockObject $dataObjectProcessor;
    private PaymentFactory|MockObject $paymentFactory;
    private CartInterfaceFactory|MockObject $quoteFactory;
    private PaymentHelper|MockObject $paymentHelper;

    protected function setUp(): void
    {
        $this->setUpSubject();
    }

    private function setUpSubject(array $mocks = []): void
    {
        $this->resource = $mocks['resource'] ?? $this->createStub(CardResource::class);
        $this->cardFactory = $this->createStub(CardFactory::class);
        $this->dataCardFactory = $this->createStub(CardInterfaceFactory::class);
        $this->cardCollectionFactory = $this->createStub(CollectionFactory::class);
        $this->searchResultsFactory = $this->createStub(CardSearchResultsInterfaceFactory::class);
        $this->dataObjectHelper = $this->createStub(DataObjectHelper::class);
        $this->dataObjectProcessor = $this->createStub(DataObjectProcessor::class);
        $this->paymentFactory = $this->createStub(PaymentFactory::class);
        $this->quoteFactory = $this->createStub(CartInterfaceFactory::class);
        $this->paymentHelper = $this->createStub(PaymentHelper::class);

        $this->repository = new CardRepository(
            $this->resource,
            $this->cardFactory,
            $this->dataCardFactory,
            $this->cardCollectionFactory,
            $this->searchResultsFactory,
            $this->dataObjectHelper,
            $this->dataObjectProcessor,
            $this->paymentFactory,
            $this->quoteFactory,
            $this->paymentHelper,
        );
    }

    public function testGetByIdLoadsNumericId(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(123);

        $this->cardFactory->method('create')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($card, 123);

        $result = $this->repository->getById(123);

        $this->assertSame($card, $result);
    }

    public function testGetByIdLoadsByHashForNonNumeric(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(123);

        $this->cardFactory->method('create')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($card, 'somehash', 'hash');

        $result = $this->repository->getById('somehash');

        $this->assertSame($card, $result);
    }

    public function testGetByIdThrowsForNotFound(): void
    {
        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(null);

        $this->cardFactory->method('create')->willReturn($card);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Card with id "999" does not exist.');

        $this->repository->getById(999);
    }

    public function testGetByHashLoadsCard(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(123);

        $this->cardFactory->method('create')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('load')
            ->with($card, 'testhash', 'hash');

        $result = $this->repository->getByHash('testhash');

        $this->assertSame($card, $result);
    }

    public function testGetByHashThrowsForNotFound(): void
    {
        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(null);

        $this->cardFactory->method('create')->willReturn($card);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Card with hash "invalidhash" does not exist.');

        $this->repository->getByHash('invalidhash');
    }

    public function testLoadCallsGetById(): void
    {
        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(123);

        $this->cardFactory->method('create')->willReturn($card);

        $result = $this->repository->load(123);

        $this->assertSame($card, $result);
    }

    public function testSaveSetsNullIdForZero(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setId', 'getTypeInstance'])
            ->getMock();

        $card->method('getId')->willReturn(0);
        $card->expects($this->once())->method('setId')->with(null);
        $card->method('getTypeInstance')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('save')
            ->with($card);

        $this->repository->save($card);
    }

    public function testSaveSetsNullIdForStringZero(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setId', 'getTypeInstance'])
            ->getMock();

        $card->method('getId')->willReturn('0');
        $card->expects($this->once())->method('setId')->with(null);
        $card->method('getTypeInstance')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('save')
            ->with($card);

        $this->repository->save($card);
    }

    public function testSaveSavesCardDirectlyWhenNotBaseCardClass(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        // When passed a mock (or subclass), the ::class check fails,
        // so the card is saved directly without calling getTypeInstance()
        $card = $this->createStub(Card::class);
        $card->method('getId')->willReturn(123);

        $this->resource->expects($this->once())
            ->method('save')
            ->with($card);

        $result = $this->repository->save($card);

        $this->assertSame($card, $result);
    }

    public function testDeleteQueuesDeletionForActiveCard(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->createMock(Card::class);
        $card->method('getActive')->willReturn(1);

        $card->expects($this->once())->method('queueDeletion');

        $this->resource->expects($this->once())
            ->method('save')
            ->with($card);

        $this->resource->expects($this->never())
            ->method('delete');

        $result = $this->repository->delete($card);

        $this->assertTrue($result);
    }

    public function testDeleteRemovesInactiveCard(): void
    {
        $this->setUpSubject([
            'resource' => $this->createMock(CardResource::class),
        ]);

        $card = $this->createPartialStub(Card::class, ['getActive', 'getTypeInstance']);

        $card->method('getActive')->willReturn(0);
        $card->method('getTypeInstance')->willReturn($card);

        $this->resource->expects($this->once())
            ->method('delete')
            ->with($card);

        $this->resource->expects($this->never())
            ->method('save');

        $result = $this->repository->delete($card);

        $this->assertTrue($result);
    }

    public function testDeleteThrowsCouldNotDeleteOnException(): void
    {
        $card = $this->createStub(Card::class);
        $card->method('getActive')->willReturn(0);

        $this->resource->method('delete')
            ->willThrowException(new Exception('Delete failed'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Delete failed');

        $this->repository->delete($card);
    }

    public function testDeleteByIdGetsAndDeletesCard(): void
    {
        $card = $this->createMock(Card::class);
        $card->method('getId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardFactory->method('create')->willReturn($card);

        $card->expects($this->once())->method('queueDeletion');

        $result = $this->repository->deleteById(123);

        $this->assertTrue($result);
    }

    public function testGetListReturnsSearchResults(): void
    {
        $searchCriteria = $this->createStub(SearchCriteriaInterface::class);
        $searchCriteria->method('getFilterGroups')->willReturn([]);
        $searchCriteria->method('getSortOrders')->willReturn(null);
        $searchCriteria->method('getCurrentPage')->willReturn(1);
        $searchCriteria->method('getPageSize')->willReturn(10);

        $collection = $this->createStub(Collection::class);
        $collection->method('getSize')->willReturn(0);
        $collection->method('getItems')->willReturn([]);

        $this->cardCollectionFactory->method('create')->willReturn($collection);

        $searchResults = $this->createMock(CardSearchResultsInterface::class);
        $this->searchResultsFactory->method('create')->willReturn($searchResults);

        $searchResults->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteria);
        $searchResults->expects($this->once())
            ->method('setTotalCount')
            ->with(0);
        $searchResults->expects($this->once())
            ->method('setItems')
            ->with([]);

        $result = $this->repository->getList($searchCriteria);

        $this->assertSame($searchResults, $result);
    }
}
