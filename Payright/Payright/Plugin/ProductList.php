<?php
namespace Payright\Payright\Plugin;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;
use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

ini_set("display_errors", "0");
class ProductList
{
    protected $product;
    protected $payrightConfig;
    protected $payrightMain;
    protected $payrightHelper;
    protected $registry;
    protected $_catalogSession;
    protected $session;
    protected $_client;
    protected $jsonHelper;
    protected $_canCallTransactionOverview = false;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
    Product $product,
    PayrightConfig $payrightConfig,
    Helper $payrightHelper,
    \Magento\Catalog\Model\Session $catalogSession,
    \Magento\Framework\Session\SessionManagerInterface $session,
    Registry $registry,
    \Magento\Framework\Json\Helper\Data $jsonHelper,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->registry = $registry;
        $this->_catalogSession = $catalogSession;
        
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->session = $session;
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundGetProductDetailsHtml(
        \Magento\Catalog\Block\Product\ListProduct $subject,
        \Closure $proceed,
        
        \Magento\Catalog\Model\Product $product
    ) {
        $this->product = $product;
        

        $productId = $product->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $productType = $product->getTypeID();

        if (($productType != 'bundle' && $productType != 'grouped') && ($this->getConfigValue('payrightshowpricecategory') == "1")) {
            echo $this->getPrice($product->getFinalPrice());
        }
    }

    public function getPrice($finalPrice)
    {
        $this->session->start();
        $result = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

        if ($result != 'exceed_amount' && $result != 'APIError') {
            $resultString = "<div class='installments' style='padding: 10px;
         margin-bottom: 10px;'>or <strong>".$result['noofrepayments']."</strong>". " Fortnightly ". "payments of $" . "<strong>" . $result['LoanAmountPerPayment']."</strong> with <span class='payright-logo'></span> 

         <img id='prlogo' >";

            return $resultString;
        } else {
            if (($this->scopeConfig->getValue('payment/mypayright/sandbox')) == 1) {
                $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There is some problem with API!!</div>";
                return $resultString;
            }
        }
    }
   
       
    

    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue('payment/mypayright/'.$field);
    }
}
