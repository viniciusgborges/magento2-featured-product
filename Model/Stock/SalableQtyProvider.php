<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Model\Stock;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

class SalableQtyProvider
{
    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetProductSalableQtyInterface $getProductSalableQty
     */
    public function __construct(
        private readonly StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        private readonly GetStockItemConfigurationInterface $getStockItemConfiguration,
        private readonly GetProductSalableQtyInterface $getProductSalableQty
    ) {
    }

    /**
     * @param string $sku
     * @param int $websiteId
     * @return array{qty: float|null, is_salable: bool, is_manage_stock: bool}
     * @throws LocalizedException
     */
    public function execute(string $sku, int $websiteId): array
    {
        $stockId = (int)$this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        try {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        } catch (SkuIsNotAssignedToStockException) {
            return ['qty' => 0.0, 'is_salable' => false, 'is_manage_stock' => true];
        }

        if (!$stockItemConfiguration->isManageStock()) {
            return ['qty' => null, 'is_salable' => true, 'is_manage_stock' => false];
        }

        $qty = max(0.0, $this->getProductSalableQty->execute($sku, $stockId));

        return ['qty' => $qty, 'is_salable' => $qty > 0, 'is_manage_stock' => true];
    }
}
