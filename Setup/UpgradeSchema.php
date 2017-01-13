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

namespace ParadoxLabs\TokenBase\Setup;

/**
 * DB upgrade script for TokenBase
 */
class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * DB upgrade code
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /**
         * Normally upgrade files go by version incrementals: Apply changes since (version).
         * I'm going to try a slightly different approach: Get the schema where it's supposed to be.
         */

        /**
         * paradoxlabs_stored_card.hash
         */
        $hashExists = $setup->getConnection()->tableColumnExists($setup->getTable('paradoxlabs_stored_card'), 'hash');
        if ($hashExists !== true) {
            /**
             * Add hash column
             */
            $setup->getConnection()->addColumn(
                $setup->getTable('paradoxlabs_stored_card'),
                'hash',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'    => 40,
                    'comment'   => 'Unique Hash',
                ]
            );

            /**
             * Add index
             */
            $setup->getConnection()->addIndex(
                $setup->getTable('paradoxlabs_stored_card'),
                $setup->getIdxName(
                    $setup->getTable('paradoxlabs_stored_card'),
                    ['hash'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['hash'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

            /**
             * Generate hashes for any existing cards
             */
            $concat = new \Zend_Db_Expr(
                'SHA1(CONCAT("tokenbase", customer_id, customer_email, method, profile_id, payment_id))'
            );

            $setup->getConnection()->update(
                $setup->getTable('paradoxlabs_stored_card'),
                [
                    'hash' => $concat,
                ],
                'hash IS NULL'
            );
        }

        /**
         * paradoxlabs_stored_card.method length 12 => 32
         */
        $tableDdl = $setup->getConnection()->describeTable($setup->getTable('paradoxlabs_stored_card'));

        if (isset($tableDdl['method']) && $tableDdl['method']['LENGTH'] < 32) {
            $tableDdl['method']['LENGTH'] = 32;

            $setup->getConnection()->modifyColumnByDdl(
                $setup->getTable('paradoxlabs_stored_card'),
                'method',
                $tableDdl['method']
            );
        }

        /**
         * paradoxlabs_stored_card.profile_id int => varchar(32)
         */
        if (isset($tableDdl['profile_id']) && $tableDdl['profile_id']['DATA_TYPE'] !== 'varchar') {
            $tableDdl['profile_id']['COLUMN_NAME'] = 'profile_id';
            $tableDdl['profile_id']['DATA_TYPE'] = 'varchar';
            $tableDdl['profile_id']['LENGTH'] = 32;

            $setup->getConnection()->modifyColumnByDdl(
                $setup->getTable('paradoxlabs_stored_card'),
                'profile_id',
                $tableDdl['profile_id']
            );
        }

        /**
         * paradoxlabs_stored_card.payment_id int => varchar(32)
         */
        if (isset($tableDdl['payment_id']) && $tableDdl['payment_id']['DATA_TYPE'] !== 'varchar') {
            $tableDdl['profile_id']['COLUMN_NAME'] = 'payment_id';
            $tableDdl['payment_id']['DATA_TYPE'] = 'varchar';
            $tableDdl['payment_id']['LENGTH'] = 32;

            $setup->getConnection()->modifyColumnByDdl(
                $setup->getTable('paradoxlabs_stored_card'),
                'payment_id',
                $tableDdl['payment_id']
            );
        }

        $setup->endSetup();
    }
}
