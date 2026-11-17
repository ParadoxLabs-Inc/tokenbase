<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\Api;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\Data\CardSearchResultsInterface;
use ParadoxLabs\TokenBase\Model\Api\CustomerCardRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CustomerCardRepository
 */
class CustomerCardRepositoryTest extends TestCase
{
    private CustomerCardRepository $repository;
    private CardRepositoryInterface|MockObject $cardRepository;
    private FilterGroupBuilder|MockObject $filterGroupBuilder;
    private FilterBuilder|MockObject $filterBuilder;
    private ScopeConfigInterface|MockObject $scopeConfig;

    protected function setUp(): void
    {
        $this->setUpSubject();
    }

    private function setUpSubject(array $mocks = []): void
    {
        $this->cardRepository = $mocks['cardRepository'] ?? $this->createStub(CardRepositoryInterface::class);
        $this->filterGroupBuilder = $this->createStub(FilterGroupBuilder::class);
        $this->filterBuilder = $mocks['filterBuilder'] ?? $this->createStub(FilterBuilder::class);
        $this->scopeConfig = $mocks['scopeConfig'] ?? $this->createStub(ScopeConfigInterface::class);

        $this->repository = new CustomerCardRepository(
            $this->cardRepository,
            $this->filterGroupBuilder,
            $this->filterBuilder,
            $this->scopeConfig,
        );
    }

    private function enableApi(): void
    {
        $this->scopeConfig->method('getValue')
            ->with('checkout/tokenbase/enable_public_api', 'store')
            ->willReturn('1');
    }

    public function testGetByHashThrowsWhenApiDisabled(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->scopeConfig->method('getValue')
            ->with('checkout/tokenbase/enable_public_api', 'store')
            ->willReturn('0');

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('The public TokenBase API is not enabled.');

        $this->repository->getByHash(123, 'somehash');
    }

    public function testGetByHashReturnsCard(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('somehash')
            ->willReturn($card);

        $result = $this->repository->getByHash(123, 'somehash');

        $this->assertSame($card, $result);
    }

    public function testGetByHashThrowsForWrongCustomer(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getCustomerId')->willReturn(456);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('somehash')
            ->willReturn($card);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('You do not have permission for this action.');

        $this->repository->getByHash(123, 'somehash');
    }

    public function testGetByHashThrowsForInactiveCard(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(0);

        $this->cardRepository->method('getByHash')
            ->with('somehash')
            ->willReturn($card);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('You do not have permission for this action.');

        $this->repository->getByHash(123, 'somehash');
    }

    public function testDeleteByHashThrowsWhenApiDisabled(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->scopeConfig->method('getValue')
            ->with('checkout/tokenbase/enable_public_api', 'store')
            ->willReturn('0');

        $this->expectException(AuthorizationException::class);

        $this->repository->deleteByHash(123, 'somehash');
    }

    public function testDeleteByHashDeletesCard(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('somehash')
            ->willReturn($card);

        $this->cardRepository->expects($this->once())
            ->method('delete')
            ->with($card)
            ->willReturn(true);

        $result = $this->repository->deleteByHash(123, 'somehash');

        $this->assertTrue($result);
    }

    public function testGetListThrowsWhenApiDisabled(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->scopeConfig->method('getValue')
            ->with('checkout/tokenbase/enable_public_api', 'store')
            ->willReturn('0');

        $searchCriteria = $this->createStub(SearchCriteriaInterface::class);

        $this->expectException(AuthorizationException::class);

        $this->repository->getList(123, $searchCriteria);
    }

    public function testGetListAddsMandatoryFilters(): void
    {
        $this->setUpSubject([
            'filterBuilder' => $this->createMock(FilterBuilder::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $searchCriteria->method('getFilterGroups')->willReturn([]);

        $customerFilter = $this->createStub(Filter::class);
        $activeFilter = $this->createStub(Filter::class);

        $this->filterBuilder->expects($this->exactly(2))
            ->method('setField')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(2))
            ->method('setValue')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(2))
            ->method('setConditionType')
            ->with('eq')
            ->willReturnSelf();
        $this->filterBuilder->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($customerFilter, $activeFilter);

        $filterGroup = $this->createStub(FilterGroup::class);
        $this->filterGroupBuilder->method('setFilters')->willReturnSelf();
        $this->filterGroupBuilder->method('create')->willReturn($filterGroup);

        $searchCriteria->expects($this->once())
            ->method('setFilterGroups')
            ->with($this->callback(function ($groups) {
                return count($groups) === 2;
            }));

        $searchResults = $this->createStub(CardSearchResultsInterface::class);
        $this->cardRepository->method('getList')->willReturn($searchResults);

        $result = $this->repository->getList(123, $searchCriteria);

        $this->assertSame($searchResults, $result);
    }

    public function testSaveExtendedThrowsWhenApiDisabled(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->scopeConfig->method('getValue')
            ->with('checkout/tokenbase/enable_public_api', 'store')
            ->willReturn('0');

        $card = $this->createStub(CardInterface::class);
        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $this->expectException(AuthorizationException::class);

        $this->repository->saveExtended(123, $card, $address, $additional);
    }

    public function testSaveExtendedValidatesNewCard(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn(null);
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $savedCard = $this->createStub(CardInterface::class);
        $this->cardRepository->expects($this->once())
            ->method('saveExtended')
            ->with($card, $address, $additional)
            ->willReturn($savedCard);

        $result = $this->repository->saveExtended(123, $card, $address, $additional);

        $this->assertSame($savedCard, $result);
    }

    public function testSaveExtendedValidatesExistingCardByHash(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $originalCard = $this->createStub(CardInterface::class);
        $originalCard->method('getCustomerId')->willReturn(123);
        $originalCard->method('getActive')->willReturn(1);

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn('existinghash');
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('existinghash')
            ->willReturn($originalCard);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $savedCard = $this->createStub(CardInterface::class);
        $this->cardRepository->expects($this->once())
            ->method('saveExtended')
            ->willReturn($savedCard);

        $result = $this->repository->saveExtended(123, $card, $address, $additional);

        $this->assertSame($savedCard, $result);
    }

    public function testSaveExtendedValidatesExistingCardById(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $originalCard = $this->createStub(CardInterface::class);
        $originalCard->method('getCustomerId')->willReturn(123);
        $originalCard->method('getActive')->willReturn(1);

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn(null);
        $card->method('getId')->willReturn(456);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getById')
            ->with(456)
            ->willReturn($originalCard);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $savedCard = $this->createStub(CardInterface::class);
        $this->cardRepository->expects($this->once())
            ->method('saveExtended')
            ->willReturn($savedCard);

        $result = $this->repository->saveExtended(123, $card, $address, $additional);

        $this->assertSame($savedCard, $result);
    }

    public function testSaveExtendedThrowsForWrongCustomerCard(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn(null);
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(999);
        $card->method('getActive')->willReturn(1);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('You do not have permission for this action.');

        $this->repository->saveExtended(123, $card, $address, $additional);
    }

    public function testSaveExtendedThrowsWhenOriginalCardBelongsToOther(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $originalCard = $this->createStub(CardInterface::class);
        $originalCard->method('getCustomerId')->willReturn(999);
        $originalCard->method('getActive')->willReturn(1);

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn('existinghash');
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('existinghash')
            ->willReturn($originalCard);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('You do not have permission for this action.');

        $this->repository->saveExtended(123, $card, $address, $additional);
    }

    public function testSaveExtendedIgnoresNonExistentHashLookup(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn('nonexistenthash');
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(1);

        $this->cardRepository->method('getByHash')
            ->with('nonexistenthash')
            ->willThrowException(new NoSuchEntityException());

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $savedCard = $this->createStub(CardInterface::class);
        $this->cardRepository->expects($this->once())
            ->method('saveExtended')
            ->willReturn($savedCard);

        $result = $this->repository->saveExtended(123, $card, $address, $additional);

        $this->assertSame($savedCard, $result);
    }

    public function testSaveExtendedThrowsForInactiveCard(): void
    {
        $this->setUpSubject([
            'scopeConfig' => $this->createMock(ScopeConfigInterface::class),
        ]);

        $this->enableApi();

        $card = $this->createStub(CardInterface::class);
        $card->method('getHash')->willReturn(null);
        $card->method('getId')->willReturn(null);
        $card->method('getCustomerId')->willReturn(123);
        $card->method('getActive')->willReturn(0);

        $address = $this->createStub(AddressInterface::class);
        $additional = $this->createStub(CardAdditionalInterface::class);

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('You do not have permission for this action.');

        $this->repository->saveExtended(123, $card, $address, $additional);
    }
}
