<?php declare(strict_types=1);
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Helper;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Customer\Model\Address\Config;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region;
use Magento\Directory\Model\ResourceModel\Region as RegionResource;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Throwable;

/**
 * Exposing some helpful methods for processing address submission. Yeah!
 */
class Address extends AbstractHelper
{
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
        Context $context,
        protected FormFactory $formFactory,
        protected AddressInterfaceFactory $addressDataFactory,
        protected DataObjectHelper $dataObjectHelper,
        protected RegionInterfaceFactory $regionDataFactory,
        protected RegionFactory $regionFactory,
        protected RegionResource $regionResource,
        protected AddressRepositoryInterface $addressRepository,
        protected ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        protected Mapper $addressMapper,
        protected Config $addressConfig
    ) {
        parent::__construct($context);
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
        $addressForm = $this->formFactory->create('customer_address', 'customer_address_edit', $origAddressData);

        if (isset($addressData['street']) && is_string($addressData['street'])) {
            $addressData['street'] = explode("\n", str_replace("\r", '', $addressData['street']));
        }

        $request     = $addressForm->prepareRequest($addressData);
        $addressData = $addressForm->extractData($request);

        if ($validate === true) {
            $addressErrors = $addressForm->validateData($addressData);

            if ($addressErrors !== true) {
                throw new LocalizedException(__(implode(' ', $addressErrors)));
            }
        }

        $attributeValues = $addressForm->compactData($addressData);
        $attributeValues = $this->processRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($origAddressData, $attributeValues),
            AddressInterface::class
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
        if (!empty($addressArray['region']) && strlen((string)$addressArray['region']) == 2) {
            $addressArray['region_code'] = $addressArray['region'];
        }

        if (!empty($addressArray['region_id'])) {
            /** @var \Magento\Directory\Model\Region $newRegion */
            $newRegion = $this->regionFactory->create();
            $this->regionResource->load($newRegion, $addressArray['region_id']);

            $addressArray['region_code'] = $newRegion->getCode();
            $addressArray['region']      = $newRegion->getDefaultName();
        } elseif (!empty($addressArray['region_code'])) {
            /** @var \Magento\Directory\Model\Region $newRegion */
            $newRegion = $this->regionFactory->create();
            $this->regionResource->loadByCode($newRegion, $addressArray['region_code'], $addressArray['country_id']);

            $addressArray['region_id'] = $newRegion->getId();
            $addressArray['region']    = $newRegion->getDefaultName();
        }

        $regionData = [
            RegionInterface::REGION_ID => !empty($addressArray['region_id']) ? $addressArray['region_id'] : null,
            RegionInterface::REGION => !empty($addressArray['region']) ? $addressArray['region'] : null,
            RegionInterface::REGION_CODE => !empty($addressArray['region_code']) ? $addressArray['region_code'] : null,
        ];

        $region = $this->regionDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            RegionInterface::class
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
        try {
            /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
            $renderer    = $this->addressConfig->getFormatByCode($format)->getRenderer();
            $addressData = $this->addressMapper->toFlatArray($address);

            return $renderer->renderArray($addressData);
        } catch (Throwable) {
            return '';
        }
    }
}
