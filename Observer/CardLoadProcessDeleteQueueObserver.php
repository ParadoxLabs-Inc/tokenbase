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
 * CardLoad Observer
 */
class CardLoadProcessDeleteQueueObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->registry = $registry;
        $this->cardRepository = $cardRepository;
    }

    /**
     * Check for any cards queued for deletion before we load the card list.
     * This will happen if there is a failure during order submit. We can't
     * actually save it there, so we register and do it here instead. Magic.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \ParadoxLabs\TokenBase\Model\Card $card */
            $card = $this->registry->registry('queue_card_deletion');

            if ($card && $card->getId() > 0) {
                $card->setData('no_sync', true);

                if ($card->getActive() == 1) {
                    $card->queueDeletion();
                    $this->cardRepository->save($card);
                } else {
                    $this->cardRepository->delete($card);
                }
            }
        } catch (\Exception $e) {
            // No-op -- never throw an exception in this context.
        }
    }
}
