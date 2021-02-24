<?php

namespace Payright\Payright\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface {
    /**
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payrightplanid',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Payright Plan Id'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payrightplanname',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Payright Plan Name'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payrightcheckoutid',
            [
                'type' => 'text',
                'nullable' => true,
                'comment' => 'Payright Checkout Id'
            ]
        );

        $setup->endSetup();
    }
}