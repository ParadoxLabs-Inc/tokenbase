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

namespace ParadoxLabs\TokenBase\Controller\Adminhtml\Index;

/**
 * TokenbaseCardsDelete Class
 */
class PaymentinfoDelete extends Paymentinfo
{
    /**
     * @var bool
     */
    protected $skipCardLoad = true;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * View customer's stored cards list (active view)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $id     = $this->getRequest()->getParam('card_id');
        $method = $this->getRequest()->getParam('method');

        $response = [
            'success' => false,
            'message' => '',
        ];

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                $card = $this->cardRepository->getById($id);
                $card = $card->getTypeInstance();

                /**
                 * If we have a valid card, mark it for deletion. That's it.
                 */
                if ($card && $card->getHash() == $id) {
                    $card->queueDeletion();
                    $card = $this->cardRepository->save($card);

                    $response['success'] = true;
                    $response['message'] = __('Payment record deleted.');
                } else {
                    $response['message'] = __('Unable to load card for deletion.');
                }
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);

                $response['message'] = __($e->getMessage());
            }
        } else {
            $response['message'] = __('Invalid Request.');
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
