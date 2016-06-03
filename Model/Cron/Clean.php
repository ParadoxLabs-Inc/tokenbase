<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Cron;

/**
 * Perform scheduled maintenance actions
 *
 * TODO: Support multi-website configuration
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
     * Constructor, yeah!
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->cardCollectionFactory = $cardCollectionFactory;
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

        /** @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards */
        $cards = $this->cardCollectionFactory->create();
        $cards->addFieldToFilter('active', '0')
              ->addFieldToFilter('updated_at', [ 'lt' => date('c', strtotime('-120 days')), 'date' => true ])
              ->addFieldToFilter(
                  [
                      'last_use',
                      'last_use',
                  ],
                  [
                      ['lt' => date('c', strtotime('-120 days')), 'date' => true],
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
              ->addFieldToFilter('updated_at', ['lt' => date('c', strtotime('-7 days')), 'date' => true]);

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
    protected function deleteCards(\ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection $cards)
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
                $card->delete();

                $affectedCount++;
            } catch (\Exception $e) {
                $this->helper->log($cardMethod, sprintf('Error deleting card: %s', (string)$e));
            }
        }

        return $affectedCount;
    }
}
