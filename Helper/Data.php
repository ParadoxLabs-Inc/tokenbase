<?php
/**
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

namespace ParadoxLabs\TokenBase\Helper;

use Magento\Framework\App\Helper\Context;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Recursively cleanup array from objects
     * 
     * @param $array
     * @return void
     */
    public function cleanupArray(&$array)
    {
        if( !$array ) {
            return;
        }

        foreach( $array as $key => $value ) {
            if( is_object( $value ) ) {
                unset( $array[ $key ] );
            }
            elseif( is_array( $value ) ) {
                $this->cleanupArray( $array[ $key ] );
            }
        }
    }

    /**
     * Write a message to the logs, nice and abstractly.
     * 
     * @param string $code
     * @param mixed $message
     * @return $this
     */
    public function log( $code, $message )
    {
        if( is_object( $message ) ) {
            if( $message instanceof \Magento\Framework\Object ) {
                $message = $message->getData();
                
                $this->cleanupArray( $message );
            }
            else {
                $message = (array)$message;
            }
        }
        
        if( is_array( $message ) ) {
            $message = print_r( $message, 1 );
        }
        
        // TODO: Custom logger to write to {$code}.log
        $this->_logger->info( $message );

        return $this;
    }
}