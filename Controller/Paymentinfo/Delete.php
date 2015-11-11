<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Controller\Paymentinfo;

/**
 * Delete the given card, if valid
 */
class Delete extends \ParadoxLabs\TokenBase\Controller\Paymentinfo
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id     = $this->getRequest()->getParam('id');
        $method = $this->getRequest()->getParam('method');

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                /**
                 * Load the card and verify we are actually the cardholder before doing anything.
                 */

                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                $card = $this->cardFactory->create();
                $card->loadByHash($id);
                $card = $card->getTypeInstance();

                if ($card && $card->getHash() == $id && $card->hasOwner($this->helper->getCurrentCustomer()->getId())) {
                    $card->queueDeletion()
                         ->save();

                    $this->messageManager->addSuccess(__('Payment record deleted.'));
                } else {
                    $this->messageManager->addError(__('Invalid Request.'));
                }
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);

                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('Invalid Request.'));
        }

        $resultRedirect->setPath('*/*', ['method' => $method, '_secure' => true]);
        return $resultRedirect;
    }
}
