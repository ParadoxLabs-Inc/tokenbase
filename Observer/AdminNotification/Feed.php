<?php
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
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

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        // If frequency is 0 (or AdminNotification is disabled), default to checking daily.
        return parent::getFrequency() ?? 86400;
    }
}
