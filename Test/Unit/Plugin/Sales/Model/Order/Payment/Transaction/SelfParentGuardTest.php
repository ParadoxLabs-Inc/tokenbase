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

namespace ParadoxLabs\TokenBase\Test\Unit\Plugin\Sales\Model\Order\Payment\Transaction;

use Magento\Sales\Model\Order\Payment\Transaction;
use ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\Payment\Transaction\SelfParentGuard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Magento 2.4.8 self-parenting transaction guard.
 */
class SelfParentGuardTest extends TestCase
{
    private SelfParentGuard $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new SelfParentGuard();
    }

    /**
     * The case the guard exists for: 2.4.8 set parent_id to the transaction's own id, so the parent lookup
     * hands back the transaction itself and close() would recurse on it forever.
     *
     * @return void
     */
    public function testSelfReferencingParentIsReportedAsNoParent(): void
    {
        $subject = $this->transaction(42);

        $this->assertFalse(
            $this->plugin->afterGetParentTransaction($subject, $this->transaction(42)),
            'A transaction that is its own parent must be reported as having no parent.'
        );
    }

    /**
     * Ids arrive from the database as strings; the comparison must not care.
     *
     * @return void
     */
    public function testSelfReferenceIsDetectedAcrossIdTypes(): void
    {
        $subject = $this->transaction('42');

        $this->assertFalse(
            $this->plugin->afterGetParentTransaction($subject, $this->transaction(42)),
            'A string id and an integer id of the same transaction are the same transaction.'
        );
    }

    /**
     * A genuine parent — every release other than 2.4.8, and 2.4.8 itself for anything but the order's
     * oldest transaction — must pass through untouched.
     *
     * @return void
     */
    public function testGenuineParentIsPassedThrough(): void
    {
        $subject = $this->transaction(42);
        $parent = $this->transaction(41);

        $this->assertSame(
            $parent,
            $this->plugin->afterGetParentTransaction($subject, $parent),
            'A parent that is a different transaction must be returned as-is.'
        );
    }

    /**
     * Core reports "no parent" as false, not null. That has to survive the plugin unchanged.
     *
     * @return void
     */
    public function testNoParentIsPassedThrough(): void
    {
        $this->assertFalse(
            $this->plugin->afterGetParentTransaction($this->transaction(42), false),
            'A false result must stay false.'
        );
    }

    /**
     * An unsaved parent has no id, which must not be read as matching an unsaved subject.
     *
     * @return void
     */
    public function testUnsavedParentIsPassedThrough(): void
    {
        $subject = $this->transaction(null);
        $parent = $this->transaction(null);

        $this->assertSame(
            $parent,
            $this->plugin->afterGetParentTransaction($subject, $parent),
            'Two transactions without ids must not be treated as the same transaction.'
        );
    }

    /**
     * @param int|string|null $id
     * @return Transaction|MockObject
     */
    private function transaction($id)
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getId')
            ->willReturn($id);

        return $transaction;
    }
}
