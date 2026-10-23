<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'vbdev_featured_product/general/enabled';
    private const XML_PATH_SKU = 'vbdev_featured_product/general/sku';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getSku(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_SKU, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
