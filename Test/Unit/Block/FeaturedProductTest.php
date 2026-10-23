<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Test\Unit\Block;

use Vbdev\FeaturedProduct\Block\FeaturedProduct;
use Vbdev\FeaturedProduct\ViewModel\FeaturedProduct as FeaturedProductViewModel;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeaturedProductTest extends TestCase
{
    private FeaturedProductViewModel&MockObject $viewModel;
    private FeaturedProduct $block;

    protected function setUp(): void
    {
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->with('vbdev_featured/stock')
            ->willReturn('http://localhost/vbdev_featured/stock/');
        $context = $this->createMock(Context::class);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        $this->viewModel = $this->createMock(FeaturedProductViewModel::class);
        $this->block = new FeaturedProduct(
            $context,
            $this->createMock(ImageFactory::class),
            [
                'view_model' => $this->viewModel,
                'jsLayout' => [
                    'components' => [
                        FeaturedProduct::STOCK_COMPONENT => [
                            'component' => 'Vbdev_FeaturedProduct/js/view/featured-stock',
                            'config' => ['template' => 'Vendor_Theme/featured-stock'],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testJsLayoutAddsEndpointUrl(): void
    {
        $component = json_decode($this->block->getJsLayout(), true)['components'][FeaturedProduct::STOCK_COMPONENT];

        $this->assertSame(
            [
                'component' => 'Vbdev_FeaturedProduct/js/view/featured-stock',
                'config' => [
                    'template' => 'Vendor_Theme/featured-stock',
                    'endpointUrl' => 'http://localhost/vbdev_featured/stock/',
                ],
            ],
            $component
        );
    }

    public function testCacheTagIsFeaturedProductOnly(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(42);
        $this->viewModel->method('getProduct')->willReturn($product);

        $this->assertSame(['cat_p_42'], $this->block->getIdentities());
    }

    public function testNoCacheTagWithoutProduct(): void
    {
        $this->viewModel->method('getProduct')->willReturn(null);

        $this->assertSame([], $this->block->getIdentities());
    }
}
