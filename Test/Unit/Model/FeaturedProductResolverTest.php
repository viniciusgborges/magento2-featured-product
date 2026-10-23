<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Test\Unit\Model;

use Vbdev\FeaturedProduct\Model\Config;
use Vbdev\FeaturedProduct\Model\FeaturedProductResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeaturedProductResolverTest extends TestCase
{
    private const SKU = '24-MB01';
    private const STORE_ID = 1;
    private const WEBSITE_ID = 1;
    private const OTHER_WEBSITE_ID = 99;

    private Config&MockObject $config;
    private ProductRepositoryInterface&MockObject $productRepository;
    private FeaturedProductResolver $resolver;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(self::WEBSITE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->with(self::STORE_ID)->willReturn($store);

        $this->resolver = new FeaturedProductResolver($this->config, $this->productRepository, $storeManager);
    }

    public function testReturnsDisplayableProduct(): void
    {
        $this->mockConfig(isEnabled: true, sku: self::SKU);
        $product = $this->mockProduct(isDisabled: false, isVisible: true, websiteId: self::WEBSITE_ID);
        $this->productRepository->method('get')->with(self::SKU, false, self::STORE_ID)->willReturn($product);

        $this->assertSame($product, $this->resolver->execute(self::STORE_ID));
    }

    public function testThrowsWhenModuleIsDisabled(): void
    {
        $this->mockConfig(isEnabled: false, sku: self::SKU);
        $this->productRepository->expects($this->never())->method('get');

        $this->expectException(NoSuchEntityException::class);
        $this->resolver->execute(self::STORE_ID);
    }

    public function testThrowsWhenSkuIsEmpty(): void
    {
        $this->mockConfig(isEnabled: true, sku: '');
        $this->productRepository->expects($this->never())->method('get');

        $this->expectException(NoSuchEntityException::class);
        $this->resolver->execute(self::STORE_ID);
    }

    public function testThrowsWhenProductIsDisabled(): void
    {
        $this->mockConfig(isEnabled: true, sku: self::SKU);
        $this->productRepository->method('get')
            ->willReturn($this->mockProduct(isDisabled: true, isVisible: true, websiteId: self::WEBSITE_ID));

        $this->expectException(NoSuchEntityException::class);
        $this->resolver->execute(self::STORE_ID);
    }

    public function testThrowsWhenProductIsNotVisible(): void
    {
        $this->mockConfig(isEnabled: true, sku: self::SKU);
        $this->productRepository->method('get')
            ->willReturn($this->mockProduct(isDisabled: false, isVisible: false, websiteId: self::WEBSITE_ID));

        $this->expectException(NoSuchEntityException::class);
        $this->resolver->execute(self::STORE_ID);
    }

    public function testThrowsWhenProductIsFromAnotherWebsite(): void
    {
        $this->mockConfig(isEnabled: true, sku: self::SKU);
        $this->productRepository->method('get')
            ->willReturn($this->mockProduct(isDisabled: false, isVisible: true, websiteId: self::OTHER_WEBSITE_ID));

        $this->expectException(NoSuchEntityException::class);
        $this->resolver->execute(self::STORE_ID);
    }

    private function mockConfig(bool $isEnabled, string $sku): void
    {
        $this->config->method('isEnabled')->with(self::STORE_ID)->willReturn($isEnabled);
        $this->config->method('getSku')->with(self::STORE_ID)->willReturn($sku);
    }

    private function mockProduct(bool $isDisabled, bool $isVisible, int $websiteId): Product&MockObject
    {
        $product = $this->createMock(Product::class);
        $product->method('isDisabled')->willReturn($isDisabled);
        $product->method('isVisibleInSiteVisibility')->willReturn($isVisible);
        $product->method('getWebsiteIds')->willReturn([(string)$websiteId]);

        return $product;
    }
}
