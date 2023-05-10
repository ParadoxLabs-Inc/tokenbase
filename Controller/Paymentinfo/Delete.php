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
 * Delete the given card, if valid
 */
class Delete extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id     = $this->getRequest()->getParam('id');
        $method = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                /**
                 * Load the card and verify we are actually the cardholder before doing anything.
                 */

                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                $card = $this->cardRepository->getByHash($id);
                $card = $card->getTypeInstance();

                if ($card && $card->getHash() == $id && $card->hasOwner($this->helper->getCurrentCustomer()->getId())) {
                    $card->queueDeletion();

                    $card = $this->cardRepository->save($card);

                    $this->messageManager->addSuccessMessage(__('Payment record deleted.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Invalid Request.'));
                }
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);

                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid Request.'));
        }

        $resultRedirect->setPath('*/*', ['method' => $method, '_secure' => true]);
        return $resultRedirect;
    }
}
