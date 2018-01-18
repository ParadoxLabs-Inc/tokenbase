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
 * DB setup script for TokenBase
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * DB setup code
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $this->createStoredCardTable($setup);
        $this->addPaymentTokenbaseIdColumns($setup);

        $setup->endSetup();
    }

    /**
     * Create table 'paradoxlabs_stored_card'
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     */
    protected function createStoredCardTable(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('paradoxlabs_stored_card')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Card ID'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            [],
            'Customer ID'
        )->addColumn(
            'customer_email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Customer Email'
        )->addColumn(
            'customer_ip',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Customer IP'
        )->addColumn(
            'profile_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Profile ID'
        )->addColumn(
            'payment_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Payment ID'
        )->addColumn(
            'method',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            [],
            'Payment Method Code'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['nullable' => false, 'default' => '1'],
            'Is Card Active'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Card Creation Time'
        )->addColumn(
            'updated_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Card Updated Time'
        )->addColumn(
            'last_use',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Time Last Used'
        )->addColumn(
            'expires',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Time card expires'
        )->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Card Address'
        )->addColumn(
            'additional',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            [],
            'Additional Info'
        )->addColumn(
            'hash',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            [],
            'Unique Hash'
        )->addIndex(
            $setup->getIdxName(
                $setup->getTable('paradoxlabs_stored_card'),
                ['hash'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['hash'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        )->addIndex(
            $setup->getIdxName(
                $setup->getTable('paradoxlabs_stored_card'),
                ['customer_id']
            ),
            ['customer_id']
        )->setComment(
            'Stored Cards for ParadoxLabs payment methods'
        );

        $setup->getConnection()->createTable($table);
    }

    /**
     * Add payment card ID columns
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     */
    protected function addPaymentTokenbaseIdColumns(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $quoteDb = $setup->getConnection('checkout');
        $quoteDb->addColumn(
            $setup->getTable('quote_payment'),
            'tokenbase_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'comment' => 'ParadoxLabs_TokenBase Card ID',
            ]
        );

        $salesDb = $setup->getConnection('sales');
        $salesDb->addColumn(
            $setup->getTable('sales_order_payment'),
            'tokenbase_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'comment' => 'ParadoxLabs_TokenBase Card ID',
            ]
        );
    }
}
