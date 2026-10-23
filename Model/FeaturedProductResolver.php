<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class FeaturedProductResolver
{
    /**
     * @param Config $config
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param int $storeId
     * @return Product
     * @throws NoSuchEntityException
     */
    public function execute(int $storeId): Product
    {
        $sku = $this->config->isEnabled($storeId) ? $this->config->getSku($storeId) : '';
        if ($sku === '') {
            throw new NoSuchEntityException(__('No featured product is configured.'));
        }

        $product = $this->productRepository->get($sku, false, $storeId);
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();

        if (!$product instanceof Product || !$this->isDisplayable($product, $websiteId)) {
            throw new NoSuchEntityException(__('The featured product is not available in this store.'));
        }

        return $product;
    }

    /**
     * @param Product $product
     * @param int $websiteId
     * @return bool
     */
    private function isDisplayable(Product $product, int $websiteId): bool
    {
        return !$product->isDisabled()
            && $product->isVisibleInSiteVisibility()
            && in_array($websiteId, array_map('intval', $product->getWebsiteIds()), true);
    }
}
