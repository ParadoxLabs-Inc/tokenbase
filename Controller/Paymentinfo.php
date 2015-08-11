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

namespace ParadoxLabs\TokenBase\Controller;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Paymentinfo abstract controller
 */
class Paymentinfo extends \Magento\Customer\Controller\Account
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \ParadoxLabs\TokenBase\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Address
     */
    protected $addressHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Registry $registry
     * @param \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Helper\Address $addressHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Registry $registry,
        \ParadoxLabs\TokenBase\Model\CardFactory $cardFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Helper\Address $addressHelper
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->cardFactory = $cardFactory;
        $this->addressHelper = $addressHelper;

        parent::__construct($context, $customerSession, $resultPageFactory);
    }

    /**
     * Check whether input form key is valid
     *
     * @return bool
     */
    protected function formKeyIsValid()
    {
        if ($this->formKeyValidator->validate($this->getRequest())) {
            return true;
        }

        return false;
    }

    /**
     * Check whether input method is valid, and register if so.
     *
     * @return bool
     */
    protected function methodIsValid()
    {
        $method = $this->getRequest()->getParam('method');

        if (in_array($method, $this->helper->getActiveMethods()) !== false) {
            $this->registry->register('tokenbase_method', $method, true);

            return true;
        }

        return false;
    }
}
