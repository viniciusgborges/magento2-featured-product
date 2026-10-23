<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Test\Unit\Model\Stock;

use Vbdev\FeaturedProduct\Model\Stock\SalableQtyProvider;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalableQtyProviderTest extends TestCase
{
    private const SKU = '24-MB01';
    private const WEBSITE_ID = 1;
    private const STOCK_ID = 2;

    private GetStockItemConfigurationInterface&MockObject $getStockItemConfiguration;
    private GetProductSalableQtyInterface&MockObject $getProductSalableQty;
    private SalableQtyProvider $provider;

    protected function setUp(): void
    {
        $stock = $this->createMock(StockInterface::class);
        $stock->method('getStockId')->willReturn(self::STOCK_ID);

        $stockByWebsiteIdResolver = $this->createMock(StockByWebsiteIdResolverInterface::class);
        $stockByWebsiteIdResolver->method('execute')->with(self::WEBSITE_ID)->willReturn($stock);

        $this->getStockItemConfiguration = $this->createMock(GetStockItemConfigurationInterface::class);
        $this->getProductSalableQty = $this->createMock(GetProductSalableQtyInterface::class);

        $this->provider = new SalableQtyProvider(
            $stockByWebsiteIdResolver,
            $this->getStockItemConfiguration,
            $this->getProductSalableQty
        );
    }

    public function testUnmanagedStockIsSalableWithoutQty(): void
    {
        $this->mockStockItemConfiguration(isManageStock: false);
        $this->getProductSalableQty->expects($this->never())->method('execute');

        $this->assertSame(
            ['qty' => null, 'is_salable' => true, 'is_manage_stock' => false],
            $this->provider->execute(self::SKU, self::WEBSITE_ID)
        );
    }

    public function testSkuWithoutSourceIsNotSalable(): void
    {
        $this->getStockItemConfiguration->method('execute')
            ->willThrowException(new SkuIsNotAssignedToStockException(__('Not assigned.')));
        $this->getProductSalableQty->expects($this->never())->method('execute');

        $this->assertSame(
            ['qty' => 0.0, 'is_salable' => false, 'is_manage_stock' => true],
            $this->provider->execute(self::SKU, self::WEBSITE_ID)
        );
    }

    public function testIntegerQtyIsShownAsIs(): void
    {
        $this->mockStockItemConfiguration(isManageStock: true);
        $this->getProductSalableQty->method('execute')->with(self::SKU, self::STOCK_ID)->willReturn(37.0);

        $this->assertSame(
            ['qty' => 37.0, 'is_salable' => true, 'is_manage_stock' => true],
            $this->provider->execute(self::SKU, self::WEBSITE_ID)
        );
    }

    public function testNegativeQtyBecomesZero(): void
    {
        $this->mockStockItemConfiguration(isManageStock: true);
        $this->getProductSalableQty->method('execute')->with(self::SKU, self::STOCK_ID)->willReturn(-3.0);

        $this->assertSame(
            ['qty' => 0.0, 'is_salable' => false, 'is_manage_stock' => true],
            $this->provider->execute(self::SKU, self::WEBSITE_ID)
        );
    }

    private function mockStockItemConfiguration(bool $isManageStock): void
    {
        $configuration = $this->createMock(StockItemConfigurationInterface::class);
        $configuration->method('isManageStock')->willReturn($isManageStock);

        $this->getStockItemConfiguration->method('execute')
            ->with(self::SKU, self::STOCK_ID)
            ->willReturn($configuration);
    }
}
