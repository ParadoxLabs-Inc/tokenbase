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

namespace ParadoxLabs\TokenBase\Observer\AdminNotification;

/**
 * Check for extension updates/notifications and add any to the system.
 */
class Feed extends \Magento\AdminNotification\Model\Feed
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Module\Dir
     */
    protected $moduleDir;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileHandler;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\Module\Dir $moduleDir
     * @param \Magento\Framework\Filesystem\Io\File $fileHandler
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\Module\Dir $moduleDir,
        \Magento\Framework\Filesystem\Io\File $fileHandler,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->moduleDir = $moduleDir;
        $this->fileHandler = $fileHandler;

        parent::__construct(
            $context,
            $registry,
            $backendConfig,
            $inboxFactory,
            $curlFactory,
            $deploymentConfig,
            $productMetadata,
            $urlBuilder,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        $methods        = $this->helper->getAllMethods();
        $methods[]      = 'tokenbase';

        $this->_feedUrl = 'https://store.paradoxlabs.com/updates.php?key=' . implode(',', $methods)
            . '&version=' . $this->getModuleVersion('ParadoxLabs_TokenBase');

        return $this->_feedUrl;
    }

    /**
     * Get version of the specified module from its composer.json.
     *
     * @param string $module
     * @return string
     */
    public function getModuleVersion($module)
    {
        $composerFile = $this->fileHandler->read(
            $this->moduleDir->getDir($module) . '/composer.json'
        );

        $composer = json_decode((string)$composerFile, true);

        return isset($composer['version']) ? $composer['version'] : '';
    }
}
