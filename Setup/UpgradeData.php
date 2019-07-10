<?php
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

namespace ParadoxLabs\TokenBase\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * UpgradeData Class
 */
class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    private $unserialize;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     */
    public function __construct(
        \Magento\Framework\Unserialize\Unserialize $unserialize
    ) {
        $this->unserialize = $unserialize;
    }

    /**
     * Data upgrade
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '4.0.0', '<')) {
            $this->convertSerializedColsToJson($setup, $context);
        }
    }

    /**
     * Convert serialized data to JSON. Magento did this across the core in 2.2.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return $this
     */
    public function convertSerializedColsToJson(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /**
         * Fix paradoxlabs_stored_card.address and paradoxlabs_stored_card.additional
         */
        $select = $setup->getConnection()->select()
            ->from($setup->getTable('paradoxlabs_stored_card'), 'id')
            ->columns(['address', 'additional'])
            ->where('additional LIKE ?', 'a:%')
            ->orWhere('address LIKE ?', 'a:%');

        $items = $setup->getConnection()->fetchAll($select);
        foreach ($items as $item) {
            // For each result, convert the two columns and update the row.
            $setup->getConnection()->update(
                $setup->getTable('paradoxlabs_stored_card'),
                [
                    'additional' => $this->jsonify($item['additional']),
                    'address'    => $this->jsonify($item['address']),
                ],
                ['id = ?' => $item['id']]
            );
        }

        return $this;
    }

    /**
     * Convert serialized string to JSON.
     *
     * @param string $string
     * @return string
     */
    private function jsonify($string)
    {
        // We're using serialize for array storage--all valid data will start with 'a:'.
        // If our string starts with 'a', attempt to decode, otherwise pass through as-is.
        if ($string[0] === 'a') {
            if (strpos($string, 'O:27:"Mage_Customer_Model_Address"') !== false) {
                // Sometimes addresses from M1 were legitimately stored with customer object. Codepath unclear.
                // Can't skip them entirely, can't easily clean, but try to look for it specifically.
                $array = [];
            } else {
                $array = $this->unserialize->unserialize($string);
            }

            return json_encode($array);
        }

        return $string;
    }
}
