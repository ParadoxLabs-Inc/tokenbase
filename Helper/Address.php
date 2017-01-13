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

use Magento\Customer\Api\Data\RegionInterface;
use Magento\Directory\Model\ResourceModel\Region;

/**
 * Exposing some helpful methods for processing address submission. Yeah!
 */
class Address
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
     * Address constructor.
     *
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param Region $regionResource
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\ResourceModel\Region $regionResource,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->formFactory = $formFactory;
        $this->addressDataFactory = $addressDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->regionDataFactory = $regionDataFactory;
        $this->regionFactory = $regionFactory;
        $this->addressRepository = $addressRepository;
        $this->regionResource = $regionResource;
    }

    /**
     * Extract address from input data (request)
     *
     * @param array $addressData
     * @param array $origAddressData
     * @param bool $validate
     * @return \Magento\Customer\Api\Data\AddressInterface
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
}
