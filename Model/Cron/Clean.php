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

namespace ParadoxLabs\TokenBase\Model\Cron;

/**
 * Perform scheduled maintenance actions
 *
 * TODO: Support multi-website configuration--determine and set store on method in card->getMethodInstance() if new
 */
class Clean
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * Constructor, yeah!
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardRepository = $cardRepository;
    }

    /**
     * @return void
     */
    public function cleanData()
    {
        $cleanOldCards = $this->scopeConfig->getValue(
            'checkout/tokenbase/clean_old_cards',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($cleanOldCards != 1) {
            return;
        }

        /**
         * Prune inactive cards older than 120 days (beyond auth and refund periods)
         */
        $cutoff = $this->scopeConfig->getValue(
            'checkout/tokenbase/clean_old_cards_after',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: '180 days';

        /** @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards */
        $cards = $this->cardCollectionFactory->create();
        $cards->addFieldToFilter('active', '0')
              ->addFieldToFilter('updated_at', [ 'lt' => date('c', (int)strtotime('-' . $cutoff)), 'date' => true ])
              ->addFieldToFilter(
                  [
                      'last_use',
                      'last_use',
                  ],
                  [
                      ['lt' => date('c', (int)strtotime('-' . $cutoff)), 'date' => true],
                      ['null' => true],
                  ]
              );

        $affectedCount    = 0;
        $affectedCount   += $this->deleteCards($cards);

        unset($cards);

        /**
         * Prune any cards missing tokens after 7 days (invalid/unusable)
         */

        /** @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards */
        $cards = $this->cardCollectionFactory->create();
        $cards->addFieldToFilter('profile_id', ['null' => true])
              ->addFieldToFilter('payment_id', ['null' => true])
              ->addFieldToFilter('updated_at', ['lt' => date('c', (int)strtotime('-7 days')), 'date' => true]);

        $affectedCount   += $this->deleteCards($cards);

        if ($affectedCount > 0) {
            $this->helper->log('tokenbase', sprintf('Deleted %s queued cards.', $affectedCount));
        }
    }

    /**
     * Permanently delete cards from the given collection, return the number affected.
     *
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards
     * @return int
     */
    public function deleteCards(\ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards)
    {
        $affectedCount = 0;

        /** @var \ParadoxLabs\TokenBase\Model\Card $card */
        foreach ($cards as $card) {
            $card       = $card->getTypeInstance();
            $cardMethod = $card->getMethod();

            try {
                /**
                 * Delete the card.
                 */
                $card->queueDeletion();
                $this->cardRepository->delete($card);

                $affectedCount++;
            } catch (\Exception $e) {
                $this->helper->log($cardMethod, sprintf('Error deleting card: %s', (string)$e));
            }
        }

        return $affectedCount;
    }
}
