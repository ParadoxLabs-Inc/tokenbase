<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Helper;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Directory\Model\ResourceModel\Region;

/**
 * Exposing some helpful methods for processing address submission. Yeah!
 */
class Address extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\Metadata\FormFactory
     */
    protected $formFactory;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Customer\Api\Data\RegionInterfaceFactory
     */
    protected $regionDataFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var Region
     */
    protected $regionResource;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * Address constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param Region $regionResource
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\ResourceModel\Region $regionResource,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig
    ) {
        parent::__construct($context);

        $this->formFactory = $formFactory;
        $this->addressDataFactory = $addressDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->regionDataFactory = $regionDataFactory;
        $this->regionFactory = $regionFactory;
        $this->addressRepository = $addressRepository;
        $this->regionResource = $regionResource;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
    }

    /**
     * Extract address from input data (request)
     *
     * @param array $addressData
     * @param array $origAddressData
     * @param bool $validate
     * @return AddressInterface
     * @throws \Exception
     */
    public function buildAddressFromInput($addressData, $origAddressData = [], $validate = false)
    {
        if (!is_array($origAddressData)) {
            $origAddressData = [];
        }
        
        /** @var \Magento\Customer\Model\Metadata\Form $addressForm */
        $addressForm     = $this->formFactory->create('customer_address', 'customer_address_edit', $origAddressData);

        if (is_string($addressData['street'])) {
            $addressData['street'] = explode("\n", str_replace("\r", '', $addressData['street']));
        }

        $request         = $addressForm->prepareRequest($addressData);
        $addressData     = $addressForm->extractData($request);

        if ($validate === true) {
            $addressErrors = $addressForm->validateData($addressData);

            if ($addressErrors !== true) {
                throw new \Magento\Framework\Exception\LocalizedException(__(implode(' ', $addressErrors)));
            }
        }
        
        $attributeValues = $addressForm->compactData($addressData);
        $attributeValues = $this->processRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($origAddressData, $attributeValues),
            '\Magento\Customer\Api\Data\AddressInterface'
        );

        return $addressDataObject;
    }

    /**
     * Process region data, ensure it's valid and consistent.
     *
     * @param array $addressArray
     * @return array
     */
    public function processRegionData($addressArray)
    {
        if (!empty($addressArray['region_id'])) {
            /** @var \Magento\Directory\Model\Region $newRegion */
            $newRegion = $this->regionFactory->create();
            $this->regionResource->load($newRegion, $addressArray['region_id']);

            $addressArray['region_code'] = $newRegion->getCode();
            $addressArray['region'] = $newRegion->getDefaultName();
        }

        $regionData = [
            RegionInterface::REGION_ID   => !empty($addressArray['region_id']) ? $addressArray['region_id'] : null,
            RegionInterface::REGION      => !empty($addressArray['region']) ? $addressArray['region'] : null,
            RegionInterface::REGION_CODE => !empty($addressArray['region_code']) ? $addressArray['region_code'] : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            '\Magento\Customer\Api\Data\RegionInterface'
        );

        $addressArray['region'] = $region;

        return $addressArray;
    }

    /**
     * Expose address repository
     *
     * @return \Magento\Customer\Api\AddressRepositoryInterface
     */
    public function repository()
    {
        return $this->addressRepository;
    }

    /**
     * Check whether the contents of the two addresses match.
     *
     * @param AddressModelInterface|AddressInterface $address1
     * @param AddressModelInterface|AddressInterface $address2
     * @return bool
     */
    public function compareAddresses($address1, $address2)
    {
        // Arrayify addresses
        $addr1Array = $this->addressToArray($address1);
        $addr2Array = $this->addressToArray($address2);

        // Compare, except for these keys.
        $excludeKeys = [
            'id' => null,
            'default_shipping' => null,
            'default_billing' => null,
            'region_id' => null,
        ];

        $diff = array_diff_assoc($addr1Array, $addr2Array);
        $diff = array_diff_key($diff, $excludeKeys);

        return empty($diff);
    }

    /**
     * Turn an arbitrary Address object into an array, for reasons.
     *
     * @param AddressModelInterface|AddressInterface $address
     * @return array
     */
    public function addressToArray($address)
    {
        if ($address instanceof AddressModelInterface) {
            $address = $address->getDataModel();
        }

        return $this->extensibleDataObjectConverter->toFlatArray($address);
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param string $format
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedAddress(\Magento\Customer\Api\Data\AddressInterface $address, $format = 'html')
    {
        /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
        $renderer    = $this->addressConfig->getFormatByCode($format)->getRenderer();
        $addressData = $this->addressMapper->toFlatArray($address);

        return $renderer->renderArray($addressData);
    }
}
