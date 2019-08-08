<?php
namespace Payright\Payright\Plugin;

use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

ini_set("display_errors", "1");
class Bestsellerdproducts
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
    PayrightConfig $payrightConfig,
    Helper $payrightHelper,
     \Magento\Catalog\Model\Session $catalogSession,
    \Magento\Framework\Session\SessionManagerInterface $session,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    public function aftergetProductPriceHtml(\Magento\CatalogWidget\Block\Product\ProductsList $productlist, $result, \Magento\Catalog\Model\Product $product)
    {
        $productId = $product->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $productType = $product->getTypeID();
        $finalPrice = $product->getFinalPrice();


        if ($productType != 'bundle' && $productType != 'grouped') {
            $this->session->start();
            $resultNew = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

            if ($resultNew != 'exceed_amount' && $resultNew != 'APIError' && ($this->getConfigValue('bestsellerinstalmemts') == "1")) {
                $resultString = "<div class='installments' style='padding: 2px;
         margin-bottom: 10px;'>or <strong>".$resultNew['noofrepayments']."</strong>". " Fortnightly ". "payments of $" . "<strong>" . $resultNew['LoanAmountPerPayment']."</strong> with <span class='payright-logo'><img ></span></div>";
                return $result.$resultString;
            } else {
                if (($this->scopeConfig->getValue('payment/mypayright/sandbox')) == 1) {
                    $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There is some problem with API!!</div>";
                    return $result.$resultString;
                } else {
                    return $result;
                }
            }
        } else {
            return $result;
        }
    }
    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue('payment/mypayright/'.$field);
    }
}
