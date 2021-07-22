<?php

namespace Payright\Payright\Block;

use Magento\Framework\View\Element\Template;

/**
 * Class Head
 *
 * @package Payright\Payright\Block
 */
class Head extends Template {
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    /**
     * Header constructor.
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->assetRepository = $context->getAssetRepository();
    }

    /**
     * Get custom CSS.
     *
     * @return string
     */
    public function getCustomCSS() {
        $asset_repository = $this->assetRepository;
        $asset = $asset_repository->createAsset('Payright_Payright::css/payright.css');

        return $asset->getUrl();
    }
}