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

        if (empty($params['order']['account']['email'])
            || $params['order']['account']['email'] === $orderCreateModel->getQuote()->getCustomerEmail()) {
            return;
        }

        // If the request/session does not have a customer ID, but does have an email...
        try {
            $websiteId = null;
            if (!empty($session->getStoreId())) {
                $store = $this->storeRepository->getById($session->getStoreId());
                $websiteId = $store->getWebsiteId();
            }

            // Check if a customer with that email exists
            $customer = $this->customerRepository->get($params['order']['account']['email'], $websiteId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            // Ignore nonexistent emails, let Magento process that normally.
            $this->resetCustomer($session, $orderCreateModel);
            return;
        }

        if (empty($session->getCustomerId()) || $session->getCustomerId() != $customer->getId()) {
            // If the session has no customer and the email is registered, assign that customer.
            // If it does have a customer ID, make sure it matches the email, which may have been changed.
            $this->assignCustomer($session, $orderCreateModel, $customer);
        }
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote $session
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
     * @return void
     */
    protected function resetCustomer(
        \Magento\Backend\Model\Session\Quote $session,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
    ) {
        $session->setCustomerId(null);

        $quote = $orderCreateModel->getQuote();
        $quote->setCustomerId(null);
        $quote->getBillingAddress()->setCustomerId(null);
        $quote->getBillingAddress()->setCustomerAddressId(null);
        $quote->getShippingAddress()->setCustomerId(null);
        $quote->getShippingAddress()->setCustomerAddressId(null);
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote $session
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    protected function assignCustomer(
        \Magento\Backend\Model\Session\Quote $session,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ) {
        $session->setCustomerId((int)$customer->getId());
        $orderCreateModel->getQuote()->setCustomer($customer);
    }
}
