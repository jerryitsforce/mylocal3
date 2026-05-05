<?php

namespace Branch8\MagentoVisualMerchandiser\Model;

interface BuilderInterface
{
    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param $createIndexTable
     * @return mixed
     */
    public function buildCategory(\Magento\Catalog\Model\Category $category, $createIndexTable = true);
}
