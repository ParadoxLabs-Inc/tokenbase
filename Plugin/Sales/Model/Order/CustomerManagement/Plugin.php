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

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\CustomerManagement;

use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory;

class Plugin
{
    /**
     * Plugin constructor.
     *
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param CardRepositoryInterface $cardRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected readonly CollectionFactory $cardCollectionFactory,
        protected readonly CardRepositoryInterface $cardRepository,
        protected readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Associate customer cards after post-checkout register.
     *
     * @param OrderCustomerManagementInterface $subject
     * @param CustomerInterface $customer
     * @return CustomerInterface
     */
    public function afterCreate(
        OrderCustomerManagementInterface $subject,
        CustomerInterface $customer
    ) {
        /**
         * Look for a guest card used by this email within the last day, and blindly attach it if we get a match.
         * This isn't flawless, but loading the order to get any tokenbase_id would be much slower.
         */
        $cardCollection = $this->cardCollectionFactory->create();
        $cardCollection->addFieldToFilter('customer_id', '0');
        $cardCollection->addFieldToFilter('customer_email', $customer->getEmail());
        $cardCollection->addFieldToFilter(
            'last_use',
            [
                'gt' => date('c', (int)strtotime('-1 day')),
                'date' => true,
            ]
        );
        $cardCollection->setOrder('id', 'desc');
        $cardCollection->setPageSize(1);

        if ($cardCollection->getSize() > 0) {
            /** @var CardInterface $card */
            foreach ($cardCollection as $card) {
                $card->setCustomerId($customer->getId());

                // Activate the card by default if config is opt-out.
                $activate = (int)$this->scopeConfig->getValue(
                    'payment/' . $card->getMethod() . '/savecard_opt_out',
                    ScopeInterface::SCOPE_STORE
                );
                if ($activate === 1) {
                    $card->setActive(1);
                }

                $this->cardRepository->save($card);
            }
        }

        return $customer;
    }
}
