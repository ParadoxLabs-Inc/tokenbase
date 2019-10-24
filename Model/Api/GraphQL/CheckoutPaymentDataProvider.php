<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\Api\GraphQL;

/**
 * CheckoutPaymentDataProvider Class
 *
 * NB: NOT implementing class because it's not enforced (as of 2.3.3), and it breaks 2.1/2.2 compat.
 */
class CheckoutPaymentDataProvider /*implements
 \Magento\QuoteGraphQl\Model\Cart\Payment\AdditionalDataProviderInterface*/
{
    /**
     * Return additional data
     *
     * @param array $data
     * @return array
     */
    public function getData(array $data)
    {
        // Well this is easy.
        return $data['tokenbase_data'];
    }
}
