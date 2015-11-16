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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * Custom data field conversion -- quote to order, etc, etc.
 */
abstract class ConvertAbstract
{
    /**
     * @var string[]
     */
    protected $fields = [
        'tokenbase_id',
        'echeck_account_name',
        'echeck_bank_name',
        'echec_account_type',
        'echeck_routing_number',
        'echeck_routing_no',
        'echeck_account_no',
    ];

    /**
     * Copy payment data fields from A to B
     *
     * @param \Magento\Framework\DataObject $from
     * @param \Magento\Framework\DataObject $to
     * @return void
     */
    protected function copyData(\Magento\Framework\DataObject $from, \Magento\Framework\DataObject $to)
    {
        // Do the magic. Yeah, this is it.
        foreach ($this->fields as $field) {
            $to->setData($field, $from->getData($field));
        }
    }
}
