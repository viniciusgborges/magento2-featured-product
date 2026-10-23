<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

class FeaturedSku extends InvalidatePageCache
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowed
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly GetProductTypesBySkusInterface $getProductTypesBySkus,
        private readonly IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowed,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $sku = trim((string)$this->getValue());
        $this->setValue($sku);

        if ($sku !== '') {
            $this->validateSku($sku);
        }

        return parent::beforeSave();
    }

    /**
     * @param string $sku
     * @return void
     * @throws LocalizedException
     */
    private function validateSku(string $sku): void
    {
        $types = $this->getProductTypesBySkus->execute([$sku]);

        if (!isset($types[$sku])) {
            throw new LocalizedException(__('Product with SKU "%1" does not exist.', $sku));
        }

        if (!$this->isSourceItemManagementAllowed->execute($types[$sku])) {
            throw new LocalizedException(
                __(
                    'Products of type "%1" have no stock of their own. '
                    . 'Choose a simple, virtual or downloadable product.',
                    $types[$sku]
                )
            );
        }
    }
}
