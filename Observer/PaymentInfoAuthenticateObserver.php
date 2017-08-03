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
 * PaymentInfoAuthenticateObserver Class
 */
class PaymentInfoAuthenticateObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    protected $customerSession;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * PaymentInfoAuthenticateObserver constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Try to stop CC validation abuse by requiring a valid order before giving access to the 'My Payment Data' section.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $action */
        $action = $observer->getEvent()->getData('controller_action');
        if ($action instanceof \Magento\Framework\App\Action\Action) {
            $preventAccess = false;

            if ($this->customerHasOrdered() === false) {
                $preventAccess = true;

                $this->messageManager->addErrorMessage(
                    __('%1 will be available after you\'ve placed an order.', __('My Payment Data'))
                );
            } elseif ($this->customerHasTooManyFailures() === true) {
                $preventAccess = true;

                $this->messageManager->addErrorMessage(
                    __('%1 is currently unavailable. Please try again later.', __('My Payment Data'))
                );
            }

            if ($preventAccess === true) {
                // No orders: prevent access
                $actionFlag = $action->getActionFlag();
                $actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);

                /** @var \Magento\Framework\App\Response\Http $response */
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
                        \Magento\Sales\Api\Data\OrderInterface::CUSTOMER_ID,
                        $this->customerSession->getCustomerId()
                    )
                    ->addFilter(
                        \Magento\Sales\Api\Data\OrderInterface::STATE,
                        [
                            \Magento\Sales\Model\Order::STATE_NEW,
                            \Magento\Sales\Model\Order::STATE_PROCESSING,
                            \Magento\Sales\Model\Order::STATE_COMPLETE,
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
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // Number of seconds to keep failures (default 86400, 1 day)
            $failureWindow = $this->scopeConfig->getValue(
                'checkout/tokenbase/failure_window',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
