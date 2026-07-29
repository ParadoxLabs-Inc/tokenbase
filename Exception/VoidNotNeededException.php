<?php declare(strict_types=1);
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
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown by a gateway when a void was not necessary: the authorization is already expired, reversed, or gone.
 *
 * Nothing was reversed at the processor, but nothing needed to be. AbstractMethod treats this as a successful
 * void and closes the transaction locally. Anything else thrown from a void is treated as a real failure.
 */
class VoidNotNeededException extends LocalizedException
{
}
