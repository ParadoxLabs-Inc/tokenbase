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

namespace ParadoxLabs\TokenBase\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for card search results.
 *
 * @api
 */
interface CardSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get cards.
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface[]
     */
    public function getItems();

    /**
     * Set cards.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
