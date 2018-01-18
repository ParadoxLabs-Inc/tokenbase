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

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\CustomerManagement;

/**
 * Plugin Class
 */
class Plugin
{
    /**
     * @var \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory
     */
    protected $cardCollectionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Plugin constructor.
     *
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardRepository = $cardRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Associate customer cards after post-checkout register.
     *
     * @param \Magento\Sales\Api\OrderCustomerManagementInterface $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function afterCreate(
        \Magento\Sales\Api\OrderCustomerManagementInterface $subject,
        \Magento\Customer\Api\Data\CustomerInterface $customer
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
                'gt' => date('c', strtotime('-1 day')),
                'date' => true,
            ]
        );
        $cardCollection->setOrder('id', 'desc');
        $cardCollection->setPageSize(1);

        if ($cardCollection->getSize() > 0) {
            /** @var \ParadoxLabs\TokenBase\Api\Data\CardInterface $card */
            foreach ($cardCollection as $card) {
                $card->setCustomerId($customer->getId());

                // Activate the card by default if config is opt-out.
                $activate = (int)$this->scopeConfig->getValue(
                    'payment/' . $card->getMethod() . '/savecard_opt_out',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
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
