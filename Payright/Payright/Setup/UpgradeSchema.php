<?php

namespace Payright\Payright\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\SchemaSetupInterface;


class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @var SalesSetupFactory
    */
    protected $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
    */

    protected $quoteSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(SalesSetupFactory $salesSetupFactory, QuoteSetupFactory $quoteSetupFactory)
    {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
         /**
            * Prepare database for upgrade 
         */
            $setup->startSetup();
            
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_order'),
                'payrightplanid',
                [
                    'type' => 'text',
                    'nullable' => true  ,
                    'comment' => 'Bank',
                ]
            );

            $setup->endSetup();
    }
}