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

namespace ParadoxLabs\TokenBase\Observer;

use Magento\Framework\App\Response\Http;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Helper\Data;

class PaymentInfoAuthenticateObserver implements ObserverInterface
{
    /**
     * PaymentInfoAuthenticateObserver constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Session $customerSession *Proxy
     * @param Data $helper
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        protected readonly Session $customerSession,
        protected readonly Data $helper,
        protected readonly UrlInterface $urlBuilder,
        protected readonly ManagerInterface $messageManager,
        protected readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Try to stop CC validation abuse by requiring a valid order before giving access to the 'My Payment Data' section.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Action $action */
        $action = $observer->getEvent()->getData('controller_action');
        if ($action instanceof Action) {
            $preventAccess = false;

            if ($this->customerHasOrdered() === false) {
                $preventAccess = true;

                $this->messageManager->addErrorMessage(
                    (string)__('%1 will be available after you\'ve placed an order.', __('My Payment Options'))
                );
            } elseif ($this->customerHasTooManyFailures() === true) {
                $preventAccess = true;

                $this->messageManager->addErrorMessage(
                    (string)__('%1 is currently unavailable. Please try again later.', __('My Payment Options'))
                );
            }

            if ($preventAccess === true) {
                // No orders: prevent access
                $actionFlag = $action->getActionFlag();
                $actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

                /** @var Http $response */
                $response = $action->getResponse();
                $response->setRedirect(
                    $this->urlBuilder->getUrl('customer/account')
                );
            }
        }
    }

    /**
     * Determine whether the current logged-in customer has placed a valid order.
     *
     * @return bool
     */
    protected function customerHasOrdered()
    {
        // Allow this restriction to be turned off
        $active = $this->scopeConfig->getValue(
            'checkout/tokenbase/paymentinfo_require_order',
            ScopeInterface::SCOPE_STORE
        );
        if ($active != 1) {
            return true;
        }

        if ($this->helper->getIsFrontend()) {
            $orderCount = $this->customerSession->getData('customer_order_count');

            if (empty($orderCount)) {
                // Find an order belonging to this customer. Skip any that are canceled, refunded, held for review, etc.
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter(
                        OrderInterface::CUSTOMER_ID,
                        $this->customerSession->getCustomerId()
                    )
                    ->addFilter(
                        OrderInterface::STATE,
                        [
                            Order::STATE_NEW,
                            Order::STATE_PROCESSING,
                            Order::STATE_COMPLETE,
                        ],
                        'in'
                    )
                    ->setPageSize(1)
                    ->create();

                $orders = $this->orderRepository->getList($searchCriteria);

                $orderCount = $orders->getTotalCount();

                $this->customerSession->setData('customer_order_count', $orderCount);
            }

            if ($orderCount > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the customer has more than the allowed number of recent failures.
     *
     * @return bool
     */
    protected function customerHasTooManyFailures()
    {
        if ($this->helper->getIsFrontend()) {
            $failures = $this->customerSession->getData('tokenbase_failures');

            // Number of failures to block after (default 5)
            $failureLimit = $this->scopeConfig->getValue(
                'checkout/tokenbase/failure_limit',
                ScopeInterface::SCOPE_STORE
            );

            // Number of seconds to keep failures (default 86400, 1 day)
            $failureWindow = $this->scopeConfig->getValue(
                'checkout/tokenbase/failure_window',
                ScopeInterface::SCOPE_STORE
            );

            if (is_array($failures) && count($failures) >= $failureLimit) {
                $countInWindow = 0;
                foreach ($failures as $time => $message) {
                    if ($time > time() - $failureWindow) {
                        $countInWindow++;
                    }
                }

                if ($countInWindow >= $failureLimit) {
                    return true;
                }
            }
        }

        return false;
    }
}
