<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Controller\Stock;

use Vbdev\FeaturedProduct\Model\FeaturedProductResolver;
use Vbdev\FeaturedProduct\Model\Stock\SalableQtyProvider;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Index implements HttpGetActionInterface
{
    private const CACHE_CONTROL = 'public, max-age=5, s-maxage=5';

    /**
     * @param JsonFactory $jsonFactory
     * @param StoreManagerInterface $storeManager
     * @param FeaturedProductResolver $featuredProductResolver
     * @param SalableQtyProvider $salableQtyProvider
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly FeaturedProductResolver $featuredProductResolver,
        private readonly SalableQtyProvider $salableQtyProvider
    ) {
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $store = $this->storeManager->getStore();
            $product = $this->featuredProductResolver->execute((int)$store->getId());
            $result->setData(
                $this->salableQtyProvider->execute((string)$product->getSku(), (int)$store->getWebsiteId())
            );
        } catch (LocalizedException) {
            $result->setHttpResponseCode(404)->setData([]);
        }

        $result->setHeader('Cache-Control', self::CACHE_CONTROL, true);

        return $result;
    }
}
