<?php
declare(strict_types=1);

namespace Vbdev\FeaturedProduct\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\PageCache\Model\Cache\Type as PageCacheType;

class InvalidatePageCache extends Value
{
    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            $this->cacheTypeList->invalidate(PageCacheType::TYPE_IDENTIFIER);
        }

        return parent::afterSave();
    }
}
