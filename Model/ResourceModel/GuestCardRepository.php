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

namespace ParadoxLabs\TokenBase\Model\ResourceModel;

/**
 * GuestCardRepository Class
 */
class GuestCardRepository implements \ParadoxLabs\TokenBase\Api\GuestCardRepositoryInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * GuestCardRepository constructor.
     *
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
    ) {
        $this->cardRepository = $cardRepository;
    }

    /**
     * Save card with extended objects.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveExtended(
        \ParadoxLabs\TokenBase\Api\Data\CardInterface $card,
        \Magento\Customer\Api\Data\AddressInterface $address,
        \ParadoxLabs\TokenBase\Api\Data\CardAdditionalInterface $additional
    ) {
        // Validate original record so it can't be overwritten maliciously
        if ($card->getHash()) {
            $originalCard = $this->getByHash($card->getHash());
            $this->validateGuestCard($originalCard);
        } elseif ($card->getId()) {
            $originalCard = $this->cardRepository->getById($card->getId());
            $this->validateGuestCard($originalCard);
        }

        $this->validateGuestCard($card);

        // Force guest card to inactive. Will be usable, but not visible, and automatically pruned.
        $card->setActive(0);

        return $this->cardRepository->saveExtended($card, $address, $additional);
    }

    /**
     * Retrieve card. Will accept hash only.
     *
     * @param string $cardHash
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getByHash($cardHash)
    {
        $card = $this->cardRepository->getByHash($cardHash);

        $this->validateGuestCard($card);

        return $card;
    }

    /**
     * Do not allow guests to fetch or modify cards belonging to non-guests.
     *
     * @param \ParadoxLabs\TokenBase\Api\Data\CardInterface $card
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validateGuestCard(\ParadoxLabs\TokenBase\Api\Data\CardInterface $card)
    {
        if ((int)$card->getCustomerId() > 0) {
            throw new \Magento\Framework\Exception\InputException(__('You do not have permission for this action.'));
        }
    }
}
