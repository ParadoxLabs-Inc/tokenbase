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

namespace ParadoxLabs\TokenBase\Controller\Adminhtml\Index;

/**
 * TokenbaseCardsDelete Class
 */
class PaymentinfoDelete extends Paymentinfo
{
    /**
     * @var bool
     */
    protected $skipCardLoad = true;

    /**
     * @var \ParadoxLabs\TokenBase\Api\CardRepositoryInterface
     */
    protected $cardRepository;

    /**
     * View customer's stored cards list (active view)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $id     = $this->getRequest()->getParam('card_id');
        $method = $this->getRequest()->getParam('method');

        $response = [
            'success' => false,
            'message' => '',
        ];

        if ($this->formKeyIsValid() === true && $this->methodIsValid() === true && !empty($id)) {
            try {
                /** @var \ParadoxLabs\TokenBase\Model\Card $card */
                $card = $this->cardRepository->getById($id);
                $card = $card->getTypeInstance();

                /**
                 * If we have a valid card, mark it for deletion. That's it.
                 */
                if ($card && $card->getHash() == $id) {
                    $card->queueDeletion();
                    $card = $this->cardRepository->save($card);

                    $response['success'] = true;
                    $response['message'] = __('Payment record deleted.');
                } else {
                    $response['message'] = __('Unable to load card for deletion.');
                }
            } catch (\Exception $e) {
                $this->helper->log($method, (string)$e);

                $response['message'] = __($e->getMessage());
            }
        } else {
            $response['message'] = __('Invalid Request.');
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
