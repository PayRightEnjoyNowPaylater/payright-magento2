<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */

namespace Payright\Payright\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 *
 * @package PayRight\PayRight\Model
 */
class ConfigProvider implements ConfigProviderInterface {
    /**
     * @var Config\Payright
     */
    protected $payrightConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param Config\Payright $config
     */
    public function __construct(\Payright\Payright\Model\Config\Payright $config) {
        $this->payrightConfig = $config;
    }
}