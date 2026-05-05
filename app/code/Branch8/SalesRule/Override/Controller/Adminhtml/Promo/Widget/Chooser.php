<?php

namespace Branch8\SalesRule\Override\Controller\Adminhtml\Promo\Widget;

class Chooser extends \Magento\SalesRule\Controller\Adminhtml\Promo\Widget\Chooser
{
    public function execute()
    {
        $request = $this->getRequest();

        switch ($request->getParam('attribute')) {
            case 'sku':
                $block = $this->_view->getLayout()->createBlock(
                    \Magento\CatalogRule\Block\Adminhtml\Promo\Widget\Chooser\Sku::class,
                    'promo_widget_chooser_sku',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                );
                break;

            case 'category_ids':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int)$id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }

                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }

                $block = $this->_view->getLayout()->createBlock(
                    \Branch8\SalesRule\Block\ProductCategory::class,
                    'promo_widget_chooser_category_ids',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                )->setCategoryIds(
                    $ids
                );
                break;
            case 'seller':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int)$id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }
                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }
                $block = $this->_view->getLayout()->createBlock(
                    'SellerAttributeDropdownBlock',
                    'promo_widget_chooser_seller_ids',
                    ['data' =>
                        [
                            'js_form_object' => $request->getParam('form'),
                            'prefix_name' => 'sellerOptions',
                            'validationUrl' => 'sales_rule/salerules/GetSellerSelected',
                            'searchUrl' => 'sales_rule/salerules/SearchSellerOptions'
                        ]
                    ]
                )->setSelected(
                    $ids
                );
                break;
            case 'flagship_store':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int)$id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }
                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }
                $block = $this->_view->getLayout()->createBlock(
                    'FlagshipStoreAttributeDropdownBlock',
                    'promo_widget_chooser_flagship_store_ids',
                    ['data' =>
                        [
                            'js_form_object' => $request->getParam('form'),
                            'prefix_name' => 'sellerOptions',
                            'validationUrl' => 'sales_rule/salerules/GetFlagshipStoreSelected',
                            'searchUrl' => 'sales_rule/salerules/SearchFlagshipStoreOptions'
                        ]
                    ]
                )->setSelected(
                    $ids
                );
                break;
            case 'brand':
            case 'children::brand':
            case 'parent::brand':
                $ids = $request->getParam('selected', []);
                if (!is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int)$id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }
                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }
                $block = $this->_view->getLayout()->createBlock(
                    'BrandAttributeDropdownBlock',
                    'promo_widget_chooser_brand_ids',
                    ['data' =>
                        [
                            'js_form_object' => $request->getParam('form'),
                            'prefix_name' => 'brandOptions',
                            'validationUrl' => 'sales_rule/salerules/GetBrandSelected',
                            'searchUrl' => 'sales_rule/salerules/SearchBrandOptions'
                        ]
                    ]
                )->setSelected(
                    array_unique($ids)
                );
                break;
                break;
            default:
                $block = false;
                break;
        }

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
