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

use ParadoxLabs\TokenBase\Model\Card;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use Throwable;

/**
 * CardLoad Observer
 */
class CardLoadProcessDeleteQueueObserver implements ObserverInterface
{
    /**
     * @param Registry $registry
     * @param CardRepositoryInterface $cardRepository
     */
    public function __construct(
        protected readonly Registry $registry,
        protected readonly CardRepositoryInterface $cardRepository
    ) {
    }

    /**
     * Check for any cards queued for deletion before we load the card list.
     * This will happen if there is a failure during order submit. We can't
     * actually save it there, so we register and do it here instead. Magic.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Card $card */
            $card = $this->registry->registry('queue_card_deletion');

            if ($card && $card->getId() > 0) {
                $card->setData('no_sync', true);

                if ($card->getActive() == 1) {
                    $card->queueDeletion();
                    $this->cardRepository->save($card);
                } else {
                    $this->cardRepository->delete($card);
                }
            }
        } catch (Throwable) {
            // No-op -- never throw an exception in this context.
        }
    }
}
