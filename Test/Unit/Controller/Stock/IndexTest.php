<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Test\Unit\Controller\Stock;

use Vbdev\FeaturedProduct\Controller\Stock\Index;
use Vbdev\FeaturedProduct\Model\FeaturedProductResolver;
use Vbdev\FeaturedProduct\Model\Stock\SalableQtyProvider;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private const SKU = '24-MB01';
    private const STORE_ID = 1;
    private const WEBSITE_ID = 1;
    private const CACHE_CONTROL = 'public, max-age=5, s-maxage=5';

    private Json&MockObject $json;
    private FeaturedProductResolver&MockObject $resolver;
    private SalableQtyProvider&MockObject $salableQtyProvider;
    private Index $controller;

    protected function setUp(): void
    {
        $this->json = $this->createMock(Json::class);
        $jsonFactory = $this->createMock(JsonFactory::class);
        $jsonFactory->method('create')->willReturn($this->json);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $store->method('getWebsiteId')->willReturn(self::WEBSITE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->resolver = $this->createMock(FeaturedProductResolver::class);
        $this->salableQtyProvider = $this->createMock(SalableQtyProvider::class);
        $this->controller = new Index($jsonFactory, $storeManager, $this->resolver, $this->salableQtyProvider);
    }

    public function testReturnsStock(): void
    {
        $stock = ['qty' => 37.0, 'is_salable' => true, 'is_manage_stock' => true];
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn(self::SKU);
        $this->resolver->method('execute')->with(self::STORE_ID)->willReturn($product);
        $this->salableQtyProvider->method('execute')->with(self::SKU, self::WEBSITE_ID)->willReturn($stock);

        $this->json->expects($this->once())->method('setData')->with($stock)->willReturnSelf();
        $this->json->expects($this->never())->method('setHttpResponseCode');
        $this->json->expects($this->once())->method('setHeader')
            ->with('Cache-Control', self::CACHE_CONTROL, true)->willReturnSelf();

        $this->assertSame($this->json, $this->controller->execute());
    }

    public function testReturnsNotFoundWhenProductIsUnavailable(): void
    {
        $this->resolver->method('execute')->willThrowException(new NoSuchEntityException());

        $this->json->expects($this->once())->method('setHttpResponseCode')->with(404)->willReturnSelf();
        $this->json->expects($this->once())->method('setData')->with([])->willReturnSelf();
        $this->json->expects($this->once())->method('setHeader')
            ->with('Cache-Control', self::CACHE_CONTROL, true)->willReturnSelf();

        $this->assertSame($this->json, $this->controller->execute());
    }
}
