<?php

namespace Branch8\Brand\Plugin;

use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BrandListAbstract
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
        $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
        foreach ($result as $key => $item) {
            $item->setUrl($category->getUrl().'?brand='.$item->getLabel());
        }

        return $result;
    }



    /**
     * @return array
     */
    public function aroundGetAllLetters($subject, \Closure $proceed)
    {
        $brandLetters = [];
        $brandLetters['en'] = [];
        $brandLetters['ch'] = [];
        /** @codingStandardsIgnoreStart */
        foreach ($subject->getIndex() as $language => $arrayLetters) {
            foreach ($arrayLetters as $letters) {
                $brandLetters[$language] = array_merge($brandLetters[$language], array_keys($letters));
            }
        }
        /** @codingStandardsIgnoreEnd */

        return $brandLetters;
    }

    /**
     * @return array
     */
    public function aroundGetIndex($subject, \Closure $proceed)
    {
        $items = $subject->getItems();
        if (!$items) {
            return [];
        }

        $letters = $this->sortByLetters($items);
        $index = $this->breakByColumns($subject, $letters);

        return $index;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    private function sortByLetters($items)
    {
        $letters = $this->items2letters($items);

        return $letters;
    }

    /**
     * @param array $letters
     *
     * @return array
     */
    private function breakByColumns($subject, $letters)
    {
        $columnCount = abs((int)$subject->getData('columns'));
        if (!$columnCount) {
            $columnCount = 1;
        }

        $row = 0; // current row
        $num = 0; // current number of items in row
        $index = [];
        if (!empty($letters['en'])) {
            foreach ($letters['en'] as $letter => $items) {
                $index['en'][$row][$letter] = $items['items'];
                $num++;
                if ($num >= $columnCount) {
                    $num = 0;
                    $row++;
                }
            }
        }
        if (!empty($letters['ch'])) {
            $row = 0; // current row
            $num = 0; // current number of items in row
            foreach ($letters['ch'] as $letter => $items) {
                $index['ch'][$row][$letter] = $items['items'];
                $num++;
                if ($num >= $columnCount) {
                    $num = 0;
                    $row++;
                }
            }
        }

        return $index;
    }

    /**
     * @param array $items
     * @return array
     */
    protected function items2letters($items)
    {
        $letters = [];
        foreach ($items as $item) {
            if (!$item['en_alphabet']) {
                continue;
            }
            $letter = $this->getLetter($item['en_alphabet']);
            if (!isset($letters['en'][$letter]['items'])) {
                $letters['en'][$letter]['items'] = [];
            }

            $letters['en'][$letter]['items'][] = $item;
            if (!isset($letters['en'][$letter]['count'])) {
                $letters['en'][$letter]['count'] = 0;
            }

            $letters['en'][$letter]['count']++;
        }
        if (!empty($letters['en'])) {
            $tempEn = $letters['en']['#'] ?? [];
            unset($letters['en']['#']);
            ksort($letters['en']);
            if (!empty($tempEn)) {
                $letters['en']['#'] = $tempEn;
            }
        }
        foreach ($items as $item) {
            if (!$item['ch_alphabet']) {
                continue;
            }
            $letterCh = $this->getLetter($item['ch_alphabet']);
            if (!isset($letters['ch'][$letterCh]['items'])) {
                $letters['ch'][$letterCh]['items'] = [];
            }

            $letters['ch'][$letterCh]['items'][] = $item;
            if (!isset($letters['ch'][$letterCh]['count'])) {
                $letters['ch'][$letterCh]['count'] = 0;
            }

            $letters['ch'][$letterCh]['count']++;
        }
        if (!empty($letters['ch'])) {
            $chAlphabet = ['ㄅ','ㄆ','ㄇ','ㄈ','ㄉ','ㄊ','ㄋ','ㄌ','ㄍ','ㄎ','ㄏ','ㄐ','ㄑ','ㄒ','ㄓ','ㄔ','ㄕ','ㄖ','ㄗ','ㄘ','ㄙ','ㄧ','ㄨ','ㄩ','ㄚ','ㄛ','ㄜ','ㄝ','ㄞ','ㄟ','ㄠ','ㄡ','ㄢ','ㄣ','ㄤ','ㄥ','ㄦ','#'];
            $tempCh = $letters['ch'];
            $letters['ch'] = [];
            foreach($chAlphabet as $key) {
                if (!empty($tempCh[$key])) {
                    $letters['ch'][$key] = $tempCh[$key];
                }
            }
        }

        return $letters;
    }

    /**
     * @param $item
     * @return false|mixed|string|string[]|null
     */
    public function getLetter($label)
    {
        if (function_exists('mb_strtoupper')) {
            $letter = mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'));
        } else {
            $letter = strtoupper(substr($label, 0, 1));
        }

        if (is_numeric($letter)) {
            $letter = '#';
        }

        return $letter;
    }
}
