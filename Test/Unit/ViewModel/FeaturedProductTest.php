<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Test\Unit\ViewModel;

use Vbdev\FeaturedProduct\Model\FeaturedProductResolver;
use Vbdev\FeaturedProduct\ViewModel\FeaturedProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class FeaturedProductTest extends TestCase
{
    public function testUnavailableProductIsNull(): void
    {
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($this->createMock(StoreInterface::class));
        $resolver = $this->createMock(FeaturedProductResolver::class);
        $resolver->method('execute')->willThrowException(new NoSuchEntityException());

        $this->assertNull((new FeaturedProduct($resolver, $storeManager))->getProduct());
    }
}
