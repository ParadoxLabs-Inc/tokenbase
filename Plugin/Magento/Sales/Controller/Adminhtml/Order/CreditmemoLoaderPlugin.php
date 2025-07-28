<?php declare(strict_types=1);
/**
 * Copyright © 2025-present ParadoxLabs, Inc.
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
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Plugin\Magento\Sales\Controller\Adminhtml\Order;

use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;

/**
 * Interceptor for @see \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader
 */
class CreditmemoLoaderPlugin
{
    protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper;
    protected \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory;
    protected \Magento\Framework\Message\ManagerInterface $messageManager;

    /**
     * CreditmemoLoaderPlugin constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \ParadoxLabs\TokenBase\Helper\Data $tokenbaseHelper,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->tokenbaseHelper = $tokenbaseHelper;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Intercepted method load.
     *
     * @param CreditmemoLoader $subject
     * @return array
     * @see \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader::load
     */
    public function beforeLoad(CreditmemoLoader $subject): array
    {
        if ($this->isEligibleForProcessing($subject)) {
            $this->assignInvoiceToCreditMemo($subject);
        }

        return [];
    }

    /**
     * Check whether we should process the credit memo being loaded
     *
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $subject
     * @return bool
     */
    protected function isEligibleForProcessing(CreditmemoLoader $subject): bool
    {
        if (empty($subject->getOrderId())
            || !empty($subject->getCreditmemoId())
            || !empty($subject->getInvoiceId())) {
            return false;
        }

        $order = $this->getOrder((int)$subject->getOrderId());
        $paymentMethodCode = $order->getPayment()->getMethod();
        if (in_array($paymentMethodCode, $this->tokenbaseHelper->getActiveMethods(), true) === false) {
            return false;
        }

        return true;
    }

    /**
     * Assign an invoice to the credit memo if there's exactly one eligible; notify the admin of status
     *
     * @param \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader $subject
     * @return void
     */
    protected function assignInvoiceToCreditMemo(CreditmemoLoader $subject): void
    {
        $order = $this->getOrder((int)$subject->getOrderId());

        /**
         * If we have an order ID but no credit memo ID or invoice ID, try to load a single invoice for the order.
         *
         * Filter out any invoices that are already refunded in full.
         */
        $invoiceCollection = $this->invoiceCollectionFactory->create();
        $invoiceCollection->addFieldToFilter('order_id', $order->getId());
        $invoiceCollection->addFieldToFilter(
            'main_table.base_total_refunded',
            [
                ['null' => true],
                ['lt' => new \Zend_Db_Expr('`main_table`.`base_grand_total`')],
            ]
        );

        if ($invoiceCollection->getSize() === 1) {
            /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
            $invoice = $invoiceCollection->getFirstItem();
            $subject->setInvoiceId($invoice->getId());

            $this->messageManager->addNoticeMessage(
                __(
                    'Creating credit memo for invoice #%1 on order #%2',
                    $invoice->getIncrementId(),
                    $order->getIncrementId()
                )
            );
        } elseif ($invoiceCollection->getSize() > 1) {
            $this->messageManager->addWarningMessage(
                __(
                    'Order #%1 has multiple invoices. To do an online refund, please select a specific invoice.',
                    $order->getIncrementId()
                )
            );
        }
    }

    /**
     * Get an order by ID
     *
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrder(int $orderId): \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->orderRepository->get($orderId);
    }
}
