<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Cron;

/**
 * Perform scheduled maintenance actions
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
     * @var \ParadoxLabs\TokenBase\Model\Resource\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * Constructor, yeah!
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Model\Resource\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Model\Resource\Card\CollectionFactory $cardCollectionFactory,
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
        // TODO: trim cards missing payment_id after delay

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

        /** @var \ParadoxLabs\TokenBase\Model\Resource\Card\Collection $cards */
        $cards = $this->cardCollectionFactory->create();
        $cards->addFieldToFilter('active', '0')
              ->addFieldToFilter('updated_at', array( 'lt' => date('c', strtotime('-120 days')), 'date' => true ))
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

                /**
                 * Suspend any profiles using the card.
                 */
//                $cardId			= $card->getId();
//                $cardPaymentId	= $card->getPaymentId();
//                $profiles	= Mage::getModel('sales/recurring_profile')->getCollection()
//                                   ->addFieldToFilter( 'method_code', $cardMethod )
//                                   ->addFieldToFilter( 'additional_info',
// array( 'like' => '%"' . $cardPaymentId . '"%' ) )
//                                   ->addFieldToFilter( 'state', array( 'in' => array( 'active', 'pending' ) ) );
//
//                $count = 0;
//                if( count( $profiles ) > 0 ) {
//                    foreach( $profiles as $profile ) {
//                        $profile	= Mage::getModel('sales/recurring_profile')
//->loadByInternalReferenceId( $profile->getInternalReferenceId() );
//                        $adtl		= $profile->getAdditionalInfo();
//
//                        if( $adtl['payment_id'] == $cardPaymentId ) {
//                            $profile->setState( Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED )
//                                    ->save();
//
//                            $count++;
//                        }
//                    }
//                }
//
//                if( $count > 0 ) {
//                    $this->helper->log(
//                        $cardMethod,
//                        sprintf(
//                            "Deleted card %s; automatically suspended %s recurring profiles.",
//                            $cardId,
//                            $cardMethod,
//                            $count
//                        )
//                    );
//                }
            } catch (\Exception $e) {
                $this->helper->log($cardMethod, sprintf('Error deleting card: %s', (string)$e));
            }
        }

        if ($affectedCount > 0) {
            $this->helper->log('tokenbase', sprintf('Deleted %s queued cards.', $affectedCount));
        }
    }
}
