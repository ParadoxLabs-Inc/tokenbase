<?php declare(strict_types=1);
/**
 * Paradox Labs, Inc.
 * https://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  https://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     https://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Plugin\Vault\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;

class PaymentTokenManagement
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * PaymentTokenManagement constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \ParadoxLabs\TokenBase\Api\CardRepositoryInterface $cardRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        ManagerInterface $eventManager,
        CardRepositoryInterface $cardRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        Data $helper
    ) {
        $this->eventManager           = $eventManager;
        $this->cardRepository         = $cardRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->sortOrderBuilder       = $sortOrderBuilder;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->helper                 = $helper;
    }

    /**
     * Lists payment tokens that match specified search criteria.
     *
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $subject
     * @param array $results
     * @param int $customerId Customer ID.
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[] Payment tokens search result interface.
     */
    public function afterGetListByCustomerId(
        PaymentTokenManagementInterface $subject,
        array $results,
        $customerId
    ): array {
        /**
         * Get any TokenBase cards for $customerId, and add to the Vault results
         */
        $cardCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId)
                                                    ->addFilter('method', $this->helper->getActiveMethods(), 'in')
                                                    ->create();

        $cards = $this->cardRepository->getList($cardCriteria);

        $results = array_merge($results, $cards->getItems());

        return $results;
    }

    /**
     * Searches for all visible, non-expired tokens
     *
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $subject
     * @param array $results
     * @param int $customerId Customer ID.
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[] Payment tokens search result interface.
     */
    public function afterGetVisibleAvailableTokens(
        PaymentTokenManagementInterface $subject,
        array $results,
        $customerId
    ): array {
        /**
         * Get any active TokenBase cards for $customerId, and add to the Vault results
         */
        $sortOrder = $this->sortOrderBuilder->setField('last_use')
                                            ->setDirection('desc')
                                            ->create();

        $cardCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId)
                                                    ->addFilter('method', $this->helper->getActiveMethods(), 'in')
                                                    ->addFilter('active', 1)
                                                    ->addFilter('payment_id', true, 'notnull')
                                                    ->addFilter('expires', date('Y-m-d 00:00:00'), 'gt')
                                                    ->addSortOrder($sortOrder)
                                                    ->create();

        $cards = $this->cardRepository->getList($cardCriteria);

        $results = array_merge($results, $cards->getItems());

        return $results;
    }

    /**
     * Get payment token by token Id.
     *
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $subject
     * @param PaymentTokenInterface|null $result
     * @param int $paymentId The payment token ID.
     * @return PaymentTokenInterface|null Payment token interface.
     */
    public function afterGetByPaymentId(
        PaymentTokenManagementInterface $subject,
        $result,
        $paymentId
    ) {
        if ($result === null && is_int($paymentId)) {
            /**
             * Get tokenbase ID by payment ID
             * Get card by tokenbase ID
             */
            $payment = $this->orderPaymentRepository->get($paymentId);
            if ($payment->getData('tokenbase_id') !== null) {
                /** @var \ParadoxLabs\TokenBase\Model\Card $result */
                $result = $this->cardRepository->getById($payment->getData('tokenbase_id'));
            }
        }

        return $result;
    }

    /**
     * Get payment token by gateway token.
     *
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $subject
     * @param PaymentTokenInterface|null $result
     * @param string $token The gateway token.
     * @param string $paymentMethodCode
     * @param int $customerId Customer ID.
     * @return PaymentTokenInterface|null Payment token interface.
     */
    public function afterGetByGatewayToken(
        PaymentTokenManagementInterface $subject,
        $result,
        $token,
        $paymentMethodCode,
        $customerId
    ) {
        if ($result === null) {
            /**
             * Get tokenbase card by $token/code/customerId if $result is null
             */
            $cardCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId)
                                                        ->addFilter('method', $paymentMethodCode)
                                                        ->addFilter('payment_id', $token)
                                                        ->setPageSize(1)
                                                        ->create();

            $cards = $this->cardRepository->getList($cardCriteria);

            if ($cards->getTotalCount() === 1) {
                /** @var \ParadoxLabs\TokenBase\Model\Card $result */
                $result = current($cards->getItems());
            }
        }

        return $result;
    }

    /**
     * Get payment token by public hash.
     *
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $subject
     * @param PaymentTokenInterface|null $result
     * @param string $hash Public hash.
     * @param int $customerId Customer ID.
     * @return PaymentTokenInterface|null Payment token interface.
     */
    public function afterGetByPublicHash(
        PaymentTokenManagementInterface $subject,
        $result,
        $hash,
        $customerId
    ) {
        if ($result === null) {
            try {
                $card = $this->cardRepository->getByHash($hash);
                if ($card->getId() && (int)$card->getCustomerId() === (int)$customerId) {
                    /** @var \ParadoxLabs\TokenBase\Model\Card $result */
                    $result = $card;
                }
            } catch (LocalizedException $e) {
                // NO-OP: If we can't find the card in TB, ignore and let Vault continue.
            }
        }

        return $result;
    }
}
