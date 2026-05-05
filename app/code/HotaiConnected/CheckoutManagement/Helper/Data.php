<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HotaiConnected\CheckoutManagement\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * CheckoutManagement Helper
 *
 * 提供統一的權限配置訪問
 *
 * 使用位置：
 * - Plugin：HotaiConnected\CheckoutManagement\Plugin\Webkul\SellerSubAccount\Helper\Data
 *   → 透過 getPermissionConfig() 取得母選單/子選單與權限映射
 * - 前台選單模板：app/design/frontend/Branch8/hotai/Webkul_Marketplace/templates/layout2/account/navigation.phtml
 *   → 透過 getParentMenuConfig()/getChildMenus() 動態生成 Bill 母選單與子選單
 *
 * 權限控制介面：
 * - 後台路徑：市場管理 > 管理特約商 > 特約商子帳戶 - 操作 > 允許的帳戶權限
 * - 權限選項設定透過資料庫 core_config_data 表進行設定
 *   - 路徑：sellersubaccount/sub_account_permission/manage_sub_account_permission
 *   - 值格式：以逗號分隔的權限路徑字串
 *   - 範例 SQL：
 *     UPDATE core_config_data
 *     SET value = 'seller_chat,marketplace/account/dashboard,marketplace/account/editprofile,marketplace/product_attribute/NEW,marketplace/product/ADD,marketplace/product/productlist,marketplace/account/customer,marketplace/account/review,marketplace/TRANSACTION/history,marketplace/order/shipping,marketplace/order/history,marketplace/account/earning,mppreorder/seller/configuration,mpmassupload/product/VIEW,mpmassupload/product/export,mpmassupload/dataflow/profile,mpmassupload/profilelisting/profilelist,mprmasystem/seller/allrma,sellersubaccount/account/manage,marketplace/bill,marketplace/checkoutarea/'
 *     WHERE PATH = 'sellersubaccount/sub_account_permission/manage_sub_account_permission'
 */
class Data extends AbstractHelper
{
    /**
     * 統一的權限配置
     * 
     * 格式：
     * [
     *     'parent_menu_path' => [
     *         'label' => '母選單顯示名稱',
     *         'children' => [
     *             'child_menu_path' => '子選單顯示名稱'
     *         ]
     *     ]
     * ]
     *
     * @return array
     */
    public function getPermissionConfig()
    {
        return [
            // Bill: 帳單
            'marketplace/bill' => [
                'label' => __('Bill'),
                'children' => [
                    // Checkout Area: 結帳專區
                    'marketplace/checkoutarea/' => __('Checkout Area')
                ]
            ]
        ];
    }

    /**
     * 取得指定母選單的配置
     *
     * @param string $parentPath 母選單路徑
     * @return array|null
     */
    public function getParentMenuConfig($parentPath)
    {
        $config = $this->getPermissionConfig();
        return $config[$parentPath] ?? null;
    }

    /**
     * 取得所有子選單配置
     *
     * @param string $parentPath 母選單路徑
     * @return array
     */
    public function getChildMenus($parentPath)
    {
        $parentConfig = $this->getParentMenuConfig($parentPath);
        return $parentConfig['children'] ?? [];
    }
}

