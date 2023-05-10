<?php
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
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Controller;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;

/**
 * Paymentinfo abstract controller
 */
abstract class Paymentinfo extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Address
     */
    protected $addressHelper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Session $customerSession *Proxy
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param CardRepositoryInterface $cardRepository
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->cardFactory = $cardFactory;
        $this->cardRepository = $cardRepository;
        $this->addressHelper = $addressHelper;
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct(
            $context
        );
    }

    /**
     * Check whether input form key is valid
     *
     * @return bool
     */
    protected function formKeyIsValid()
    {
        if ($this->formKeyValidator->validate($this->getRequest())) {
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
            $this->registry->register('tokenbase_method', $method, true);

            return true;
        }

        return false;
    }
}
