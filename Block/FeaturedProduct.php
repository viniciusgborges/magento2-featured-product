<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Block;

use Vbdev\FeaturedProduct\ViewModel\FeaturedProduct as FeaturedProductViewModel;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Pricing\Render;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class FeaturedProduct extends Template implements IdentityInterface
{
    public const STOCK_COMPONENT = 'vbdevFeaturedStock';

    /**
     * @param Context $context
     * @param ImageFactory $imageFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly ImageFactory $imageFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return FeaturedProductViewModel
     */
    public function getViewModel(): FeaturedProductViewModel
    {
        return $this->getData('view_model');
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getImageHtml(Product $product): string
    {
        return $this->imageFactory->create($product, (string)$this->getData('image_id'))->toHtml();
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getPriceHtml(Product $product): string
    {
        $priceRender = $this->getLayout()->getBlock('product.price.render.default');
        if (!$priceRender instanceof Render) {
            return '';
        }

        return $priceRender->render(FinalPrice::PRICE_CODE, $product, ['zone' => Render::ZONE_ITEM_LIST]);
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->jsLayout['components'][self::STOCK_COMPONENT]['config'] += [
            'endpointUrl' => $this->getUrl('vbdev_featured/stock'),
        ];

        return parent::getJsLayout();
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        $product = $this->getViewModel()->getProduct();

        return $product ? [Product::CACHE_TAG . '_' . $product->getId()] : [];
    }

    /**
     * @return string
     */
    protected function _toHtml(): string
    {
        return $this->getViewModel()->getProduct() ? parent::_toHtml() : '';
    }
}
