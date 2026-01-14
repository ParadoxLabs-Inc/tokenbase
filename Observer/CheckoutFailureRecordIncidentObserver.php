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

namespace ParadoxLabs\TokenBase\Observer;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use ParadoxLabs\TokenBase\Helper\Data;

class CheckoutFailureRecordIncidentObserver implements ObserverInterface
{
    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     */
    public function __construct(
        protected Data $helper,
        protected Session $customerSession
    ) {
    }

    /**
     * On checkoutfailure, record the error if it's a TokenBase method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        /** @var \Exception $exception */
        $exception = $observer->getData('exception');

        // Note: We skip AuthorizationException errors to not count attempts we block ourselves.
        // @see \ParadoxLabs\TokenBase\Observer\CheckoutCheckFailuresObserver
        if ($quote instanceof Quote
            && $exception instanceof Exception
            && ($exception instanceof AuthorizationException) === false
            && $quote->getPayment() instanceof Payment
            && in_array($quote->getPayment()->getMethod(), $this->helper->getAllMethods(), true)) {
            $this->recordSessionFailure($exception);
        }
    }

    /**
     * Record each save failure on their session. If they fail too many times in a given period, block access. This is
     * to help prevent credit card validation abuse, trying to store CCs until one works.
     *
     * @param \Exception $e
     * @return void
     */
    protected function recordSessionFailure(Exception $e)
    {
        $failures = $this->customerSession->getData('tokenbase_failures');
        if (is_array($failures) === false) {
            $failures = [];
        }

        $failures[ time() ] = $e->getMessage();

        $this->customerSession->setData('tokenbase_failures', $failures);
    }
}
