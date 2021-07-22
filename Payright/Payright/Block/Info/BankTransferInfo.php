<?php

namespace Payright\Payright\Block\Info;

/**
 * Class BankTransferInfo
 *
 * @package Payright\Payright\Block\Info
 */
class BankTransferInfo extends \Magento\Sales\Block\Adminhtml\Order\View {
    protected $_bankTransfer;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \Magento\OfflinePayments\Model\Banktransfer $bankTransfer,
        array $data = []
    ) {
        $this->_reorderHelper = $reorderHelper;
        $this->_coreRegistry = $registry;
        $this->_salesConfig = $salesConfig;
        $this->_bankTransfer = $bankTransfer;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

}