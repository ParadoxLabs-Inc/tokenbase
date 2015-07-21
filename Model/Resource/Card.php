<?php
/**
 * Card resource model
 *
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author        Ryan Hoerr <magento@paradoxlabs.com>
 * @license        http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Resource;

class Card extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('paradoxlabs_tokenbase_card', 'id');
    }

    /**
     * Load card by hash
     * TODO: implement loadByHash 
     * 
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @param string|int $hash
     * @return $this
     */
    public function loadByHash(
        \ParadoxLabs\TokenBase\Model\Card $card,
        $hash
    ) {
//        $adapter = $this->_getReadAdapter();
//        $select  = $adapter->select()
//            ->from( $this->getMainTable(), array( $this->getIdFieldName() ) )
//            ->where('hash = :hash');
//
//        $cardId  = $adapter->fetchOne( $select, array( 'hash' => $hash ) );
//
//        if( $cardId ) {
//            $this->load( $card, $cardId );
//        }
//        else {
//            $card->setData( array() );
//        }

        return $this;
    }
}