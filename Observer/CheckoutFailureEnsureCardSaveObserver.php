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

namespace ParadoxLabs\TokenBase\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Card;
use Throwable;

class CheckoutFailureEnsureCardSaveObserver implements ObserverInterface
{
    /**
     * @param Data $helper
     * @param Registry $registry
     * @param CardRepositoryInterface $cardRepository
     */
    public function __construct(
        protected readonly Data $helper,
        protected readonly Registry $registry,
        protected readonly CardRepositoryInterface $cardRepository
    ) {
    }

    /**
     * If we're doing a partial refund, don't mark it as fully refunded
     * unless the full amount is done.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $card = $this->registry->registry('tokenbase_ensure_checkout_card_save');

            if ($card instanceof Card && $card->getId() > 0) {
                $card->setData('no_sync', true);

                $card = $this->cardRepository->save($card);
            }
        } catch (Throwable $e) {
            // Log and ignore any errors; we don't want to throw them in this context.
            $this->helper->log(
                isset($card) && $card instanceof Card ? $card->getMethod() : 'tokenbase',
                'Checkout post-failure card save failed: ' . $e->getMessage()
            );
        }
    }
}
