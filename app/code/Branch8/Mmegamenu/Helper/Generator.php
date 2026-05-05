<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Mmegamenu\Helper;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\StoreManagerInterface;

class Generator extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CACHE_ID_MEGAMENU = 'branch8_megamenu_cached_html';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var StateInterface
     */
    private StateInterface $_cacheState;

    /**
     * @var CacheInterface
     */
    private CacheInterface $_cache;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param FilterProvider $filterProvider
     * @param BlockFactory $blockFactory
     * @param ResourceConnection $resource
     * @param DateTime $date
     * @param StateInterface $cacheState
     * @param CacheInterface $cache
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        FilterProvider        $filterProvider,
        BlockFactory          $blockFactory,
        ResourceConnection    $resource,
        DateTime              $date,
        StateInterface        $cacheState,
        CacheInterface        $cache,
        Filesystem            $filesystem
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_filterProvider  = $filterProvider;
        $this->_blockFactory = $blockFactory;
        $this->_resource     = $resource;
        $this->_date         = $date;
        $this->_cacheState    = $cacheState;
        $this->_cache         = $cache;
        $this->filesystem     = $filesystem;
    }

    /**
     * Get Menu Cache Html
     * @param $menuId
     * @param $typeMenu
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMenuCacheHtml($menuId, $typeMenu = NULL)
    {
        $store = $this->_storeManager->getStore();
        $storeId = $store->getId();
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $cacheDir = $varDir->getAbsolutePath('cache/branch8_megamenu');
        $cacheEnable = $this->_cacheState->isEnabled(\Branch8\Mmegamenu\Model\Cache\Type::TYPE_IDENTIFIER);
        $cacheKey = self::CACHE_ID_MEGAMENU . '-' . $menuId . '-' . $storeId;
        if ($cacheEnable && $varDir->isExist($cacheDir . '/branch8_megamenu_cache')) {
            $html = $this->_cache->load($cacheKey);
            if ($html) {
                return json_decode($html, true);
            }
        }
        if (!$varDir->isExist($cacheDir)) {
            $varDir->create($cacheDir);
        }
        if ($varDir->isExist($cacheDir . '/branch8_megamenu_cache')) {
            $html = $varDir->readFile($cacheDir . '/branch8_megamenu_cache');
            if ($cacheEnable) {
                $this->_cache->save($html, $cacheKey, [\Branch8\Mmegamenu\Model\Cache\Type::CACHE_TAG], \Branch8\Mmegamenu\Model\Cache\Type::CACHE_LIFETIME);
            }
            if ($html) {
                return json_decode($html, true);
            }
        }
        $html = $this->generateMenuHtml($menuId);
        if ($cacheEnable) {
            $cachedMenu = json_encode($html);
            $this->_cache->save($cachedMenu, $cacheKey, [\Branch8\Mmegamenu\Model\Cache\Type::CACHE_TAG], \Branch8\Mmegamenu\Model\Cache\Type::CACHE_LIFETIME);
        }
        $varDir->writeFile($cacheDir . '/branch8_megamenu_cache', json_encode($html));
        return $html;
    }

    /**
     * Get Menu Cache Data
     * @return array
     */
    public function getMenuCacheData()
    {
        $store = $this->_storeManager->getStore();
        $storeId = $store->getId();
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $cacheDir = $varDir->getAbsolutePath('cache/branch8_megamenu');
        $cacheEnable = $this->_cacheState->isEnabled(\Branch8\Mmegamenu\Model\Cache\Type::TYPE_IDENTIFIER);
        $cacheKey = self::CACHE_ID_MEGAMENU . '-data-' . $storeId;
        $cacheFile = '/branch8_megamenu_data_cache_' . $storeId;

        if ($cacheEnable && $varDir->isExist($cacheDir . $cacheFile)) {
            $data = $this->_cache->load($cacheKey);
            if ($data) {
                return json_decode($data, true);
            }
        }

        if (!$varDir->isExist($cacheDir)) {
            $varDir->create($cacheDir);
        }

        if ($varDir->isExist($cacheDir . $cacheFile)) {
            $data = $varDir->readFile($cacheDir . $cacheFile);
            if ($cacheEnable) {
                $this->_cache->save($data, $cacheKey, [\Branch8\Mmegamenu\Model\Cache\Type::CACHE_TAG], \Branch8\Mmegamenu\Model\Cache\Type::CACHE_LIFETIME);
            }
            if ($data) {
                return json_decode($data, true);
            }
        }

        try {
            /** @var \Branch8\Mmegamenu\Block\Mmegamenu $block */
            $block = $this->_blockFactory->createBlock('Branch8\Mmegamenu\Block\Mmegamenu');
            $menuData = $block->getMenuDataTree();

            if ($cacheEnable) {
                $cachedMenu = json_encode($menuData);
                $this->_cache->save($cachedMenu, $cacheKey, [\Branch8\Mmegamenu\Model\Cache\Type::CACHE_TAG], \Branch8\Mmegamenu\Model\Cache\Type::CACHE_LIFETIME);
            }

            $varDir->writeFile($cacheDir . $cacheFile, json_encode($menuData));
            return $menuData;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate Menu Html
     * @param $menuId
     * @return mixed
     */
    protected function generateMenuHtml($menuId)
    {
        return $this->_blockFactory->createBlock('Branch8\Mmegamenu\Block\Mmegamenu')->setMenuId($menuId)->setTemplate("Branch8_Mmegamenu::cache/top_menu.phtml")->toHtml();
    }

    /**
     * Generate Horizontal Menu Html
     * @param $menuId
     * @return mixed
     */
    protected function generateHorizontalMenuHtml($menuId)
    {
        return $this->_blockFactory->createBlock('Branch8\Mmegamenu\Block\Horizontal')->setMenuId($menuId)->setTemplate("Branch8_Mmegamenu::cache/horizontal_menu.phtml")->toHtml();
    }

    /**
     * Generate Vertical Menu Html
     * @param $menuId
     * @return mixed
     */
    protected function generateVerticalMenuHtml($menuId)
    {
        return $this->_blockFactory->createBlock('Branch8\Mmegamenu\Block\Vertical')->setMenuId($menuId)->setTemplate("Branch8_Mmegamenu::cache/vertical_menu.phtml")->toHtml();
    }

    /**
     * Filter
     * @param $str
     * @return string
     * @throws \Exception
     */
    public function filter($str)
    {
        $filter = $this->_filterProvider->getPageFilter();
        return $filter->filter($str);
    }
}
