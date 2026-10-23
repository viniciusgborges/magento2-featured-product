<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\ViewModel;

use Vbdev\FeaturedProduct\Model\FeaturedProductResolver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class FeaturedProduct implements ArgumentInterface
{
    private ?Product $product = null;
    private bool $isResolved = false;

    /**
     * @param FeaturedProductResolver $featuredProductResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly FeaturedProductResolver $featuredProductResolver,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        if (!$this->isResolved) {
            $this->isResolved = true;
            try {
                $storeId = (int)$this->storeManager->getStore()->getId();
                $this->product = $this->featuredProductResolver->execute($storeId);
            } catch (NoSuchEntityException) {
                $this->product = null;
            }
        }

        return $this->product;
    }
}
