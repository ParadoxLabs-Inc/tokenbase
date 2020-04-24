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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * FixAdminEmailAlreadyExistsObserver Class
 */
class FixAdminEmailAlreadyExistsObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * FixAdminEmailAlreadyExistsObserver constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Prevent "Email already exists" error after hitting a payment error when placing an order for a new customer.
     * In this situation, Magento erroneously rolls back the quote changes but not the newly registered customer.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\AdminOrder\Create $orderCreateModel */
        $orderCreateModel = $observer->getData('order_create_model');
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getData('request_model');
        /** @var \Magento\Backend\Model\Session\Quote $session */
        $session = $observer->getData('session');

        $params = $request->getParams();

        // If the request/session does not have a customer ID, but does have an email...
        if (empty($session->getCustomerId())
            && !empty($params['order']['account']['email'])) {
            try {
                $websiteId = null;
                if (!empty($session->getStoreId())) {
                    $store = $this->storeRepository->getById($session->getStoreId());
                    $websiteId = $store->getWebsiteId();
                }

                // Check if a customer with that email exists
                $customer = $this->customerRepository->get($params['order']['account']['email'], $websiteId);

                // And if so, assign it to the quote.
                $session->setCustomerId((int)$customer->getId());
                $orderCreateModel->getQuote()->setCustomer($customer);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                // Ignore nonexistent emails, let Magento process that normally.
            }
        }
    }
}
