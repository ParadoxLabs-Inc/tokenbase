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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Customer;

use \Magento\Backend\Block\Template;

/**
 * Cards Class
 */
class Cards extends Template
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;

        $this->method = $this->helper->getMethodInstance($this->registry->registry('tokenbase_method'));

        parent::__construct($context, $data);
    }

    /**
     * Get stored cards for the currently-active method.
     *
     * @return array|\ParadoxLabs\TokenBase\Model\ResourceModel\Card\Collection
     */
    public function getCards()
    {
        return $this->helper->getActiveCustomerCardsByMethod($this->method->getCode());
    }

    /**
     * Get currently-active card (if any)
     *
     * @return \ParadoxLabs\TokenBase\Api\Data\CardInterface|null
     */
    public function getCurrentCard()
    {
        return $this->registry->registry('active_card');
    }

    /**
     * Get the active payment method title.
     *
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        return $this->method->getConfigData('title');
    }

    /**
     * Get HTML-formatted card address. This is silly, but it's how the core says to do it.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     * @see \Magento\Customer\Model\Address\AbstractAddress::format()
     */
    public function getFormattedCardAddress(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        try {
            /** @var \Magento\Customer\Block\Address\Renderer\RendererInterface $renderer */
            $renderer    = $this->addressConfig->getFormatByCode('html')->getRenderer();
            $addressData = $this->addressMapper->toFlatArray($address);

            return $renderer->renderArray($addressData);
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Get CC type label (if applicable).
     *
     * @param \ParadoxLabs\TokenBase\Model\Card $card
     * @return \Magento\Framework\Phrase|null
     */
    public function getCcTypeLabel(\ParadoxLabs\TokenBase\Model\Card $card)
    {
        return $card->getType() ? $this->helper->translateCardType($card->getType()) : null;
    }
}
