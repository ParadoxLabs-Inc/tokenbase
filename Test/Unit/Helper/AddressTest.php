<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Helper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\Form;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResource;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use ParadoxLabs\TokenBase\Helper\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Address helper
 */
class AddressTest extends TestCase
{
    private Address $helper;
    private Context|MockObject $context;
    private FormFactory|MockObject $formFactory;
    private AddressInterfaceFactory|MockObject $addressDataFactory;
    private DataObjectHelper|MockObject $dataObjectHelper;
    private RegionInterfaceFactory|MockObject $regionDataFactory;
    private RegionFactory|MockObject $regionFactory;
    private RegionResource|MockObject $regionResource;
    private AddressRepositoryInterface|MockObject $addressRepository;
    private ExtensibleDataObjectConverter|MockObject $extensibleDataObjectConverter;
    private Mapper|MockObject $addressMapper;
    private Config|MockObject $addressConfig;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->formFactory = $this->createMock(FormFactory::class);
        $this->addressDataFactory = $this->createMock(AddressInterfaceFactory::class);
        $this->dataObjectHelper = $this->createMock(DataObjectHelper::class);
        $this->regionDataFactory = $this->createMock(RegionInterfaceFactory::class);
        $this->regionFactory = $this->createMock(RegionFactory::class);
        $this->regionResource = $this->createMock(RegionResource::class);
        $this->addressRepository = $this->createMock(AddressRepositoryInterface::class);
        $this->extensibleDataObjectConverter = $this->createMock(ExtensibleDataObjectConverter::class);
        $this->addressMapper = $this->createMock(Mapper::class);
        $this->addressConfig = $this->createMock(Config::class);

        $this->helper = new Address(
            $this->context,
            $this->formFactory,
            $this->addressDataFactory,
            $this->dataObjectHelper,
            $this->regionDataFactory,
            $this->regionFactory,
            $this->regionResource,
            $this->addressRepository,
            $this->extensibleDataObjectConverter,
            $this->addressMapper,
            $this->addressConfig,
        );
    }

    public function testRepositoryReturnsAddressRepository(): void
    {
        $result = $this->helper->repository();

        $this->assertSame($this->addressRepository, $result);
    }

    public function testCompareAddressesReturnsTrueForIdentical(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'street' => '123 Main St',
            'city' => 'Anytown',
            'region' => 'CA',
            'postcode' => '12345',
            'country_id' => 'US',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturn($addressData);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertTrue($result);
    }

    public function testCompareAddressesReturnsFalseForDifferent(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData1 = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'street' => '123 Main St',
            'city' => 'Anytown',
        ];

        $addressData2 = [
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'street' => '456 Oak Ave',
            'city' => 'Other City',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturnOnConsecutiveCalls($addressData1, $addressData2);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertFalse($result);
    }

    public function testCompareAddressesIgnoresIdDifference(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData1 = [
            'id' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $addressData2 = [
            'id' => 2,
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturnOnConsecutiveCalls($addressData1, $addressData2);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertTrue($result);
    }

    public function testCompareAddressesIgnoresRegionIdDifference(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData1 = [
            'region_id' => 12,
            'firstname' => 'John',
        ];

        $addressData2 = [
            'region_id' => 15,
            'firstname' => 'John',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturnOnConsecutiveCalls($addressData1, $addressData2);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertTrue($result);
    }

    public function testCompareAddressesIgnoresDefaultShippingDifference(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData1 = [
            'default_shipping' => true,
            'firstname' => 'John',
        ];

        $addressData2 = [
            'default_shipping' => false,
            'firstname' => 'John',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturnOnConsecutiveCalls($addressData1, $addressData2);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertTrue($result);
    }

    public function testCompareAddressesIgnoresDefaultBillingDifference(): void
    {
        $address1 = $this->createMock(AddressInterface::class);
        $address2 = $this->createMock(AddressInterface::class);

        $addressData1 = [
            'default_billing' => true,
            'firstname' => 'John',
        ];

        $addressData2 = [
            'default_billing' => false,
            'firstname' => 'John',
        ];

        $this->extensibleDataObjectConverter->method('toFlatArray')
            ->willReturnOnConsecutiveCalls($addressData1, $addressData2);

        $result = $this->helper->compareAddresses($address1, $address2);

        $this->assertTrue($result);
    }

    public function testAddressToArrayConvertsAddressInterface(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $expectedArray = [
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $this->extensibleDataObjectConverter->expects($this->once())
            ->method('toFlatArray')
            ->with($address)
            ->willReturn($expectedArray);

        $result = $this->helper->addressToArray($address);

        $this->assertSame($expectedArray, $result);
    }

    public function testAddressToArrayConvertsAddressModelInterface(): void
    {
        $addressData = $this->createMock(AddressInterface::class);

        $addressModel = $this->getMockBuilder(AddressModelInterface::class)
            ->addMethods(['getDataModel'])
            ->getMockForAbstractClass();

        $addressModel->expects($this->once())
            ->method('getDataModel')
            ->willReturn($addressData);

        $expectedArray = ['firstname' => 'Jane'];

        $this->extensibleDataObjectConverter->expects($this->once())
            ->method('toFlatArray')
            ->with($addressData)
            ->willReturn($expectedArray);

        $result = $this->helper->addressToArray($addressModel);

        $this->assertSame($expectedArray, $result);
    }

    public function testProcessRegionDataWithRegionId(): void
    {
        $region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode', 'getDefaultName'])
            ->getMock();
        $region->method('getCode')->willReturn('CA');
        $region->method('getDefaultName')->willReturn('California');

        $this->regionFactory->method('create')->willReturn($region);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $addressArray = [
            'region_id' => 12,
            'country_id' => 'US',
        ];

        $result = $this->helper->processRegionData($addressArray);

        $this->assertSame('CA', $result['region_code']);
        $this->assertInstanceOf(RegionInterface::class, $result['region']);
    }

    public function testProcessRegionDataWithRegionCode(): void
    {
        $region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getDefaultName'])
            ->getMock();
        $region->method('getId')->willReturn(12);
        $region->method('getDefaultName')->willReturn('California');

        $this->regionFactory->method('create')->willReturn($region);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $addressArray = [
            'region_code' => 'CA',
            'country_id' => 'US',
        ];

        $result = $this->helper->processRegionData($addressArray);

        $this->assertSame(12, $result['region_id']);
        $this->assertInstanceOf(RegionInterface::class, $result['region']);
    }

    public function testProcessRegionDataWithTwoCharRegion(): void
    {
        $region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getDefaultName'])
            ->getMock();
        $region->method('getId')->willReturn(12);
        $region->method('getDefaultName')->willReturn('California');

        $this->regionFactory->method('create')->willReturn($region);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $addressArray = [
            'region' => 'CA',
            'country_id' => 'US',
        ];

        $result = $this->helper->processRegionData($addressArray);

        $this->assertSame('CA', $result['region_code']);
    }

    public function testProcessRegionDataWithoutRegionInfo(): void
    {
        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $addressArray = [
            'country_id' => 'US',
            'city' => 'Anytown',
        ];

        $result = $this->helper->processRegionData($addressArray);

        $this->assertInstanceOf(RegionInterface::class, $result['region']);
    }

    public function testBuildAddressFromInputConvertsStringStreet(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn([
            'street' => ['123 Main St', 'Apt 4'],
            'city' => 'Anytown',
        ]);
        $form->method('compactData')->willReturn([
            'street' => ['123 Main St', 'Apt 4'],
            'city' => 'Anytown',
        ]);

        $address = $this->createMock(AddressInterface::class);
        $this->addressDataFactory->method('create')->willReturn($address);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $inputData = [
            'street' => "123 Main St\nApt 4",
            'city' => 'Anytown',
        ];

        $result = $this->helper->buildAddressFromInput($inputData);

        $this->assertInstanceOf(AddressInterface::class, $result);
    }

    public function testBuildAddressFromInputWithArrayStreet(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn([
            'street' => ['123 Main St'],
        ]);
        $form->method('compactData')->willReturn([
            'street' => ['123 Main St'],
        ]);

        $address = $this->createMock(AddressInterface::class);
        $this->addressDataFactory->method('create')->willReturn($address);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $inputData = [
            'street' => ['123 Main St'],
        ];

        $result = $this->helper->buildAddressFromInput($inputData);

        $this->assertInstanceOf(AddressInterface::class, $result);
    }

    public function testBuildAddressFromInputWithValidation(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn(['city' => 'Test']);
        $form->method('validateData')->willReturn(true);
        $form->method('compactData')->willReturn(['city' => 'Test']);

        $address = $this->createMock(AddressInterface::class);
        $this->addressDataFactory->method('create')->willReturn($address);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $result = $this->helper->buildAddressFromInput(['city' => 'Test'], [], true);

        $this->assertInstanceOf(AddressInterface::class, $result);
    }

    public function testBuildAddressFromInputValidationFailure(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn([]);
        $form->method('validateData')->willReturn(['City is required', 'Street is required']);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('City is required Street is required');

        $this->helper->buildAddressFromInput([], [], true);
    }

    public function testBuildAddressFromInputMergesOriginalData(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn(['city' => 'NewCity']);
        $form->method('compactData')->willReturn(['city' => 'NewCity']);

        $address = $this->createMock(AddressInterface::class);
        $this->addressDataFactory->method('create')->willReturn($address);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        $origData = ['firstname' => 'John', 'lastname' => 'Doe'];
        $newData = ['city' => 'NewCity'];

        // populateWithArray is called twice: once for region, once for address
        // We verify that the address call happens with merged data
        $addressPopulateCalled = false;
        $this->dataObjectHelper->expects($this->exactly(2))
            ->method('populateWithArray')
            ->willReturnCallback(function ($object, $data, $interface) use ($address, &$addressPopulateCalled) {
                if ($object === $address) {
                    $addressPopulateCalled = true;
                    $this->assertArrayHasKey('firstname', $data);
                    $this->assertSame('John', $data['firstname']);
                }

                return $this->dataObjectHelper;
            });

        $this->helper->buildAddressFromInput($newData, $origData);
        $this->assertTrue($addressPopulateCalled, 'populateWithArray should have been called for address');
    }

    public function testBuildAddressFromInputHandlesNullOrigData(): void
    {
        $form = $this->createMock(Form::class);
        $this->formFactory->method('create')->willReturn($form);

        $request = $this->createMock(RequestInterface::class);
        $form->method('prepareRequest')->willReturn($request);
        $form->method('extractData')->willReturn([]);
        $form->method('compactData')->willReturn([]);

        $address = $this->createMock(AddressInterface::class);
        $this->addressDataFactory->method('create')->willReturn($address);

        $regionInterface = $this->createMock(RegionInterface::class);
        $this->regionDataFactory->method('create')->willReturn($regionInterface);

        // Pass null as origAddressData - should be converted to empty array
        $result = $this->helper->buildAddressFromInput([], null);

        $this->assertInstanceOf(AddressInterface::class, $result);
    }

    public function testGetFormattedAddressReturnsHtml(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $renderer = $this->createMock(\Magento\Customer\Block\Address\Renderer\RendererInterface::class);
        $renderer->method('renderArray')->willReturn('<p>123 Main St</p>');

        $format = $this->createMock(\Magento\Customer\Model\Address\Config\Reader::class);
        // Create a mock that has getRenderer method
        $formatMock = new class($renderer) {
            private $renderer;
            public function __construct($renderer)
            {
                $this->renderer = $renderer;
            }
            public function getRenderer()
            {
                return $this->renderer;
            }
        };

        $this->addressConfig->method('getFormatByCode')
            ->with('html')
            ->willReturn($formatMock);

        $this->addressMapper->method('toFlatArray')
            ->with($address)
            ->willReturn(['street' => '123 Main St']);

        $result = $this->helper->getFormattedAddress($address, 'html');

        $this->assertSame('<p>123 Main St</p>', $result);
    }

    public function testGetFormattedAddressReturnsEmptyOnException(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $this->addressConfig->method('getFormatByCode')
            ->willThrowException(new \Exception('Format not found'));

        $result = $this->helper->getFormattedAddress($address);

        $this->assertSame('', $result);
    }
}
