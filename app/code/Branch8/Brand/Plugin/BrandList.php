<?php

namespace Branch8\Brand\Plugin;

use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BrandList
{

    /**
     * Scope config
     *
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CategoryRepository
     */
    protected CategoryRepository $categoryRepository;

    /**
     * BrandListAbstract constructor.
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepository $categoryRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param Option $option
     * @return string
     */
    public function aroundGetBrandUrl($subject, \Closure $proceed, Option $option)
    {
        $categoryId = $this->scopeConfig->getValue('b8brand/general/product_category');
        $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());

        return $category->getUrl().'?brand='.$option->getLabel();
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetItems($subject, \Closure $proceed) {
        $result = $proceed();
        $categoryId = $this->scopeConfig->getValue('b8brand/general/product_category');
        if ($subject->getCategory()) {
            $categoryId = $subject->getCategory();
        }
        try {
            $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
            foreach ($result as $key => $item) {
                $item->setUrl($category->getUrl() . '?brand=' . $item->getLabel());
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return $result;
    }
}
