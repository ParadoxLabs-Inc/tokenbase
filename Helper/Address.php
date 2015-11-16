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

/**
 * Exposing some helpful methods for processing address submission. Yeah!
 */
class Address extends \Magento\Customer\Controller\Address\FormPost
{
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
        $addressForm     = $this->_formFactory->create('customer_address', 'customer_address_edit', $origAddressData);

        $request         = $addressForm->prepareRequest($addressData);
        $addressData     = $addressForm->extractData($request);

        if ($validate === true) {
            $addressErrors = $addressForm->validateData($addressData);

            if ($addressErrors !== true) {
                throw new \Exception(implode(' ', $addressErrors));
            }
        }
        
        $attributeValues = $addressForm->compactData($addressData);

        $this->updateRegionData($attributeValues);

        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($origAddressData, $attributeValues),
            '\Magento\Customer\Api\Data\AddressInterface'
        );

        if (!isset($addressData['customer_id'])) {
            $addressDataObject->setCustomerId($this->_getSession()->getCustomerId());
        }

        return $addressDataObject;
    }

    /**
     * Expose address repository
     *
     * @return \Magento\Customer\Api\AddressRepositoryInterface
     */
    public function repository()
    {
        return $this->_addressRepository;
    }
}
