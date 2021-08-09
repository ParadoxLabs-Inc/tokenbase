<?php
/**
 * ParadoxLabs, Inc.
 * https://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author <support@paradoxlabs.com>
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * ConvertGuestToCustomerObserver Class
 */
class ConvertGuestToCustomerObserver implements \Magento\Framework\Event\ObserverInterface
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
     * ConvertGuestToCustomerObserver constructor.
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
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getData('customer_data_object');
        /** @var array $delegateData */
        $delegateData = $observer->getData('delegate_data');

        if (isset($delegateData['__sales_assign_order_id'])
            && $customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
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
                    'gt' => date('c', strtotime('-12 hours')),
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

                    try {
                        $this->cardRepository->save($card);
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        // No-op: gracefully skip a card save if it fails.
                    }
                }
            }
        }
    }
}
