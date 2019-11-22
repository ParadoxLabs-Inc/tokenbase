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

namespace ParadoxLabs\TokenBase\Plugin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class ConvertGuestToCustomer
 */
class ConvertGuestToCustomer
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
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Plugin constructor.
     *
     * @param \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Model\ResourceModel\Card\CollectionFactory $cardCollectionFactory,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->cardCollectionFactory = $cardCollectionFactory;
        $this->cardRepository = $cardRepository;
        $this->scopeConfig = $scopeConfig;
        $this->_customerSession = $customerSession;
    }

    /**
     * Associate customer cards after post-checkout register.
     * Subsequently, for any registration done within the time span.
     *
     * @param \Magento\Customer\Controller\Account\CreatePost $subject
     * @param $result
     * @return void
     */
    public function afterExecute(\Magento\Customer\Controller\Account\CreatePost $subject, $result)
    {
        if ($this->_customerSession->getCustomerData()) {
            /**
             * Look for a guest card used by this email within the last day, and blindly attach it if we get a match.
             * This isn't flawless, but loading the order to get any tokenbase_id would be much slower.
             */
            $cardCollection = $this->cardCollectionFactory->create();
            $cardCollection->addFieldToFilter('customer_id', '0');
            $cardCollection->addFieldToFilter('customer_email', $this->_customerSession->getCustomerData()->getEmail());
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
                    $card->setCustomerId($this->_customerSession->getId());

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
                    } catch (LocalizedException $e) {
                        // No-op: gracefully skip a card save if it fails.
                    }
                }
            }
        }

        return $result;
    }
}
