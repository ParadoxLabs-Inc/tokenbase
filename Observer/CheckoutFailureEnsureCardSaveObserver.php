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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * CheckoutFailureEnsureCardSave Observer
 */
class CheckoutFailureEnsureCardSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->cardRepository = $cardRepository;
    }

    /**
     * If we're doing a partial refund, don't mark it as fully refunded
     * unless the full amount is done.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $card = $this->registry->registry('tokenbase_ensure_checkout_card_save');

            if ($card instanceof \ParadoxLabs\TokenBase\Model\Card && $card->getId() > 0) {
                $card->setData('no_sync', true);

                $card = $this->cardRepository->save($card);
            }
        } catch (\Exception $e) {
            // Log and ignore any errors; we don't want to throw them in this context.
            $this->helper->log(
                isset($card) && $card instanceof \ParadoxLabs\TokenBase\Model\Card ? $card->getMethod() : 'tokenbase',
                'Checkout post-failure card save failed: ' . $e->getMessage()
            );
        }
    }
}
