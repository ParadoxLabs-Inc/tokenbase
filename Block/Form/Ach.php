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

namespace ParadoxLabs\TokenBase\Block\Form;

/**
 * ACH input form on checkout for TokenBase methods.
 */
class Ach extends Cc
{
    /**
     * @var string
     */
    protected $_template = 'ParadoxLabs_TokenBase::form/ach.phtml';
}
