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

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\Payment\Transaction\Repository;

/**
 * Plugin Class
 */
class Plugin
{
    /**
     * Here's the thing. As of version 2.0.7, Magento totally fails to sort this collection
     * the way it said it should. Just totally ignores the order. This fixes that.
     *
     * Around plugin because we need both the input (sort orders) and the result (collection).
     *
     * @param \Magento\Sales\Model\Order\Payment\Transaction\Repository $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection
     * @throws \Zend_Db_Select_Exception
     */
    public function aroundGetList(
        /** @noinspection PhpUnusedParameterInspection */
        \Magento\Sales\Model\Order\Payment\Transaction\Repository $subject,
        \Closure $proceed,
        \Magento\Framework\Api\SearchCriteria $searchCriteria
    ) {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection $collection */
        $collection = $proceed($searchCriteria);

        $sort = $collection->getSelect()->getPart(\Zend_Db_Select::ORDER);

        if (count($sort) == 0) {
            // Add missing sort order(s)
            $sortOrders = $searchCriteria->getSortOrders();
            if ($sortOrders) {
                foreach ($sortOrders as $sortOrder) {
                    $collection->addOrder(
                        $sortOrder->getField(),
                        ($sortOrder->getDirection() == \Magento\Framework\Api\SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                    );
                }
            }
        }

        return $collection;
    }
}
