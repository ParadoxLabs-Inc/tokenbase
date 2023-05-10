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

namespace ParadoxLabs\TokenBase\Model\Logger;

use Magento\Framework\Filesystem\DriverInterface;

/**
 * Custom payment gateway logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/tokenbase.log';

    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;

    /**
     * @param DriverInterface $filesystem
     * @param \Monolog\Formatter\LineFormatter $lineFormatter
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        \Monolog\Formatter\LineFormatter $lineFormatter,
        $filePath = null
    ) {
        parent::__construct($filesystem, $filePath);

        $this->setBubble(false);
        $this->setFormatter($lineFormatter);
    }
}
