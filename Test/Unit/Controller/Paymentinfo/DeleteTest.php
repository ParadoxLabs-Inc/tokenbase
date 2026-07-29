<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Controller\Paymentinfo;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Controller\Paymentinfo\Delete;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Model\CardFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the customer card delete controller
 */
class DeleteTest extends TestCase
{
    private Delete $controller;
    private Request|MockObject $request;
    private MessageManagerInterface|MockObject $messageManager;
    private CardRepositoryInterface|MockObject $cardRepository;
    private Data|MockObject $helper;
    private Validator|MockObject $formKeyValidator;
    private Json|MockObject $jsonResult;
    private Redirect|MockObject $redirectResult;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->messageManager = $this->createMock(MessageManagerInterface::class);
        $this->cardRepository = $this->createMock(CardRepositoryInterface::class);
        $this->helper = $this->createMock(Data::class);
        $this->formKeyValidator = $this->createMock(Validator::class);
        $this->jsonResult = $this->createMock(Json::class);
        $this->redirectResult = $this->createMock(Redirect::class);

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturn($this->jsonResult);

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirectResult);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn(123);
        $this->helper->method('getCurrentCustomer')->willReturn($customer);
        $this->helper->method('getActiveMethods')->willReturn(['test_method']);

        $this->formKeyValidator->method('validate')->willReturn(true);

        $this->controller = new Delete(
            $context,
            $this->createMock(Session::class),
            $this->createMock(PageFactory::class),
            $this->formKeyValidator,
            $this->createMock(Registry::class),
            $this->createMock(CardFactory::class),
            $this->cardRepository,
            $this->helper,
            $this->createMock(Address::class)
        );
    }

    /**
     * Deletion failures must report the reason on the AJAX response, not via the message queue.
     *
     * @return void
     */
    public function testAjaxFailureReturnsReason(): void
    {
        $this->mockRequest(true);
        $this->mockCard();

        $this->cardRepository->method('save')
            ->willThrowException(new StateException(__('Unable to remove Visa: in use by subscription 1.')));

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'success' => false,
                    'message' => 'Unable to remove Visa: in use by subscription 1.',
                ]
            )
            ->willReturnSelf();

        $this->assertSame($this->jsonResult, $this->controller->execute());
    }

    /**
     * Successful AJAX deletion should not carry a message.
     *
     * @return void
     */
    public function testAjaxSuccess(): void
    {
        $this->mockRequest(true);
        $this->mockCard();

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->assertSame($this->jsonResult, $this->controller->execute());
    }

    /**
     * Invalid input on AJAX must report the reason rather than an empty failure.
     *
     * @return void
     */
    public function testAjaxInvalidRequestReturnsReason(): void
    {
        $this->mockRequest(true, null);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'success' => false,
                    'message' => 'Invalid Request.',
                ]
            )
            ->willReturnSelf();

        $this->assertSame($this->jsonResult, $this->controller->execute());
    }

    /**
     * Non-AJAX deletion failures keep the message-queue plus redirect behavior.
     *
     * @return void
     */
    public function testNonAjaxFailureQueuesMessageAndRedirects(): void
    {
        $this->mockRequest(false);
        $this->mockCard();

        $this->cardRepository->method('save')
            ->willThrowException(new StateException(__('Unable to remove Visa: in use by subscription 1.')));

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with('Unable to remove Visa: in use by subscription 1.');

        $this->assertSame($this->redirectResult, $this->controller->execute());
    }

    /**
     * Set up the request params for the controller.
     *
     * @param bool $isAjax
     * @param string|null $cardHash
     * @return void
     */
    private function mockRequest(bool $isAjax, ?string $cardHash = 'cardhash'): void
    {
        $this->request->method('isAjax')->willReturn($isAjax);
        $this->request->method('getParam')
            ->willReturnCallback(
                static function ($key) use ($cardHash) {
                    return match ($key) {
                        'id' => $cardHash,
                        'method' => 'test_method',
                        default => null,
                    };
                }
            );
    }

    /**
     * Set up a card owned by the current customer.
     *
     * @return void
     */
    private function mockCard(): void
    {
        $card = $this->createMock(Card::class);
        $card->method('getTypeInstance')->willReturnSelf();
        $card->method('getHash')->willReturn('cardhash');
        $card->method('hasOwner')->willReturn(true);

        $this->cardRepository->method('getByHash')->willReturn($card);
    }
}
