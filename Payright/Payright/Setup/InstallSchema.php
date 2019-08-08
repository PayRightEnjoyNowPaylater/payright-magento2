<?php
namespace Payright\Payright\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payrightplanid',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'Bank'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payrightplanname',
            [
                'type' => 'text',
                'nullable' => true  ,
                'comment' => 'Bank'
            ]
        );

        $setup->endSetup();
    }
}