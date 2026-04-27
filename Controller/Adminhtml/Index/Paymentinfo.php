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

namespace ParadoxLabs\TokenBase\Controller\Adminhtml\Index;

use Magento\Framework\View\Result\Layout;
use ParadoxLabs\TokenBase\Model\Card;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Controller\Adminhtml\Index;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer\Mapper as CustomerMapper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory as ViewLayoutFactory;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;

/**
 * TokenbaseCards controller - manage cards on admin customer view
 */
class Paymentinfo extends Index
{
    protected bool $skipCardLoad = false;

    /**
     * Paymentinfo constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param View $viewHelper
     * @param Random $random
     * @param CustomerRepositoryInterface $customerRepository
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Mapper $addressMapper
     * @param AccountManagementInterface $customerAccountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param JsonFactory $resultJsonFactory
     * @param CardRepositoryInterface $cardRepository
     * @param Data $helper
     * @param Address $addressHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        View $viewHelper,
        Random $random,
        CustomerRepositoryInterface $customerRepository,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        Mapper $addressMapper,
        AccountManagementInterface $customerAccountManagement,
        AddressRepositoryInterface $addressRepository,
        CustomerInterfaceFactory $customerDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerMapper $customerMapper,
        DataObjectProcessor $dataObjectProcessor,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        ViewLayoutFactory $layoutFactory,
        LayoutFactory $resultLayoutFactory,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        JsonFactory $resultJsonFactory,
        protected readonly CardRepositoryInterface $cardRepository,
        protected readonly Data $helper,
        protected readonly Address $addressHelper
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory
        );
    }

    /**
     * View customer's stored cards list (active view)
     *
     * @return Layout
     */
    public function execute()
    {
        $this->getCustomer();

        /**
         * Check for active method, or pick one if none given.
         */
        if ($this->methodIsValid() !== true) {
            $methods = $this->helper->getActiveMethods();

            if (!empty($methods)) {
                sort($methods);

                $this->_coreRegistry->register('tokenbase_method', $methods[0]);
            }
        }

        /**
         * Check for card input and validate if present.
         */
        $id = $this->getRequest()->getParam('card_id');

        if (empty($id) || $this->formKeyIsValid() !== true) {
            $id = null;

            if ($this->_session->hasData('tokenbase_form_data')) {
                if ($this->getRequest()->getParam('cancel') == 1) {
                    $this->_session->setData('tokenbase_form_data', null);
                }

                $data = $this->_session->getData('tokenbase_form_data');

                if (isset($data['card_id'])
                    && !empty($data['card_id'])
                    && $data['method'] == $this->_coreRegistry->registry('tokenbase_method')) {
                    $id = $data['card_id'];
                }
            }
        }

        if (!empty($id) && $this->skipCardLoad !== true) {
            /** @var Card $card */
            $card = $this->cardRepository->getByHash($id);
            $card = $card->getTypeInstance();

            if ($card && $card->getHash() == $id) {
                $this->_coreRegistry->register('active_card', $card, true);
            }
        }

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addHandle('customer_index_paymentinfo_' . $this->_coreRegistry->registry('tokenbase_method'));

        return $resultLayout;
    }

    /**
     * Check whether input form key is valid
     *
     * @return bool
     */
    protected function formKeyIsValid()
    {
        if ($this->_formKeyValidator->validate($this->getRequest())) {
            return true;
        }

        return false;
    }

    /**
     * Check whether input method is valid, and register if so.
     *
     * @return bool
     */
    protected function methodIsValid()
    {
        $method = $this->getRequest()->getParam('method');

        if (in_array($method, $this->helper->getActiveMethods()) !== false) {
            $this->_coreRegistry->register('tokenbase_method', $method, true);

            return true;
        }

        return false;
    }

    /**
     * Get current customer model.
     *
     * @return CustomerInterface
     */
    protected function getCustomer()
    {
        if ($this->_coreRegistry->registry('current_customer')) {
            return $this->_coreRegistry->registry('current_customer');
        }

        $customerId = $this->initCurrentCustomer();
        $customer   = $this->_customerRepository->getById($customerId);

        $this->_coreRegistry->register('current_customer', $customer);

        return $customer;
    }
}
