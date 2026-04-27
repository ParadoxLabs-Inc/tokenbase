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

namespace ParadoxLabs\TokenBase\Controller;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\CardFactory;

/**
 * Paymentinfo abstract controller
 */
abstract class Paymentinfo extends AbstractAccount
{
    /**
     * @param Context $context
     * @param Session $session *Proxy
     * @param PageFactory $resultPageFactory
     * @param Validator $formKeyValidator
     * @param Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param CardRepositoryInterface $cardRepository
     * @param Data $helper
     * @param Address $addressHelper
     */
    public function __construct(
        Context $context,
        protected readonly Session $session,
        protected readonly PageFactory $resultPageFactory,
        protected readonly Validator $formKeyValidator,
        protected readonly Registry $registry,
        protected readonly CardFactory $cardFactory,
        protected readonly CardRepositoryInterface $cardRepository,
        protected readonly Data $helper,
        protected readonly Address $addressHelper
    ) {
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
