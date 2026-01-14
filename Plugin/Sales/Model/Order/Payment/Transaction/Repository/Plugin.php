<?php declare(strict_types=1);
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
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\Payment\Transaction\Repository;

use Closure;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;

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
        Repository $subject,
        Closure $proceed,
        SearchCriteria $searchCriteria
    ) {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection $collection */
        $collection = $proceed($searchCriteria);

        $sort = $collection->getSelect()->getPart(Select::ORDER);

        if (empty($sort)) {
            // Add missing sort order(s)
            $sortOrders = $searchCriteria->getSortOrders();
            if ($sortOrders) {
                foreach ($sortOrders as $sortOrder) {
                    $collection->addOrder(
                        $sortOrder->getField(),
                        ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                    );
                }
            }
        }

        return $collection;
    }
}
