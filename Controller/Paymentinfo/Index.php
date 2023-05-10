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

namespace ParadoxLabs\TokenBase\Controller\Paymentinfo;

/**
 * Index: Show cards and form for the default or chosen payment method.
 */
class Index extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
{
    /**
     * Payment data index page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /**
         * Check for active method, or pick one if none given.
         */
        if ($this->methodIsValid() !== true) {
            $methods = $this->helper->getActiveMethods();

            if (!empty($methods)) {
                sort($methods);

                $this->registry->register('tokenbase_method', $methods[0]);
            } else {
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                $this->messageManager->addErrorMessage(__('No payment methods are currently available.'));

                $resultRedirect->setPath('*/account');
                return $resultRedirect;
            }
        }

        /**
         * Check for card input and validate if present.
         */
        $id    = $this->getRequest()->getParam('id');

        if (empty($id) || $this->formKeyIsValid() !== true) {
            $id = null;

            if ($this->session->hasData('tokenbase_form_data')) {
                $data = $this->session->getData('tokenbase_form_data');

                if (isset($data['id']) && !empty($data['id'])) {
                    $id = $data['id'];
                }
            }
        }

        if (!empty($id)) {
            /** @var \ParadoxLabs\TokenBase\Model\Card $card */
            $card = $this->cardRepository->getByHash($id);
            $card = $card->getTypeInstance();

            if ($card && $card->getHash() == $id && $card->hasOwner($this->helper->getCurrentCustomer()->getId())) {
                $this->registry->register('active_card', $card, true);
            }
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('customer_paymentinfo_index_' . $this->registry->registry('tokenbase_method'));
        $resultPage->getConfig()->getTitle()->set(__('My Payment Options'));

        return $resultPage;
    }
}
