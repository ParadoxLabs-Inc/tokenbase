<?php declare(strict_types=1);
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

namespace ParadoxLabs\TokenBase\Block\Adminhtml\Config;

use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\View\Helper\Js;

class FilterComment extends Fieldset
{
    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param FilterProvider $filterProvider
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        protected readonly FilterProvider $filterProvider,
        array $data = [],
        $secureRenderer = null
    ) {
        // Magento 2.3 compatibility: There is no SecureHtmlRenderer class or argument to pass there.
        $args = func_get_args();
        unset($args[3]);

        parent::__construct(...array_filter($args));
    }

    /**
     * Return header comment part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        if (empty($element->getComment())) {
            return parent::_getHeaderCommentHtml($element);
        }

        $comment = $this->filterProvider->getBlockFilter()->filter((string)$element->getComment());

        return '<div>' . $comment . '</div>';
    }
}
