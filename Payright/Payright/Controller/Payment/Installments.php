<?php
/**
 * Copyright © 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */

namespace Payright\Payright\Controller\Payment;

// TODO Why are these here??
ini_set('memory_limit', '-1');
set_time_limit(0);
ini_set("display_errors", 1);

use Magento\Framework\View\Element\Template;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

class Installments extends \Magento\Framework\App\Action\Action {
    protected $_cart;
    protected $_checkoutSession;
    protected $session;
    protected $_jsonResultFactory;
    protected $_jsonHelper;
    protected $payrightConfig;
    protected $payrightHelper;

    /**
     * Installments constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param Cart $cart
     * @param CheckoutSession $checkoutSession
     * @param JsonResultFactory $jsonResultFactory
     * @param JsonHelper $jsonHelper
     * @param PayrightConfig $payrightConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param Helper $payrightHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Cart $cart,
        CheckoutSession $checkoutSession,
        JsonResultFactory $jsonResultFactory,
        JsonHelper $jsonHelper,
        PayrightConfig $payrightConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        Helper $payrightHelper
    ) {
        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->payrightHelper = $payrightHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     *
     *
     * @return mixed
     */
    public function execute() {

        $RepaymentData = $this->getInstallmentTextCart();

        $result = $this->_jsonResultFactory->create();

        return $result->setData($RepaymentData);
    }

    /**
     * Get transaction overview.
     *
     * @return string|void
     */
    public function getInstallmentTextCart() {
        $this->session->start();
        $saleAmount = $this->_cart->getQuote()->getGrandTotal();

        return $this->payrightHelper->calculateSingleProductInstallment($saleAmount);
    }
}
