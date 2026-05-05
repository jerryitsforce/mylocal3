<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HotaiConnected\CheckoutManagement\Plugin\Webkul\SellerSubAccount\Helper;

use HotaiConnected\CheckoutManagement\Helper\Data as CheckoutManagementHelper;

/**
 * Plugin for Webkul\SellerSubAccount\Helper\Data
 * 
 * 擴展點總結：
 * 
 * 1. afterGetAllPermissionTypes()
 *    - 功能：新增權限類型到子帳號權限管理系統
 *    - 用途：在權限管理介面中顯示新的權限選項
 *    - 時機：當系統收集所有可用權限時調用
 *    - 返回：array ['controller_path' => __('Label')]
 * 
 * 2. afterGetControllerMappedPermissionsSellerSubAccount()
 *    - 功能：將子選單路徑映射到父權限
 *    - 用途：當子帳號存取子選單時，檢查對應的父權限
 *    - 時機：權限檢查時，用於判斷子選單的存取權限
 *    - 返回：array ['子選單路徑' => '父權限路徑']
 *    - 邏輯：如果子帳號有父權限，則可以存取所有映射到該父權限的子選單
 * 
 * 3. afterGetMappedLabelsArr() (可選，較少使用)
 *    - 功能：新增控制器路徑到顯示標籤的映射
 *    - 用途：在權限管理介面中顯示可讀的標籤名稱
 *    - 時機：當系統需要顯示權限標籤時調用
 *    - 返回：array ['controller_path' => 'Display Label']
 * 
 * 權限檢查流程：
 * 1. 子帳號登入
 * 2. 檢查子帳號權限 (getAllAllowedActions)
 *    - 取得子帳號的 permission_type
 *    - 取得賣家的 sub_account_permission
 *    - 取交集（子帳號權限 ∩ 賣家權限）
 * 3. 檢查控制器路徑權限
 *    - 檢查直接權限（是否在允許列表中）
 *    - 檢查映射權限 (getControllerMappedPermissionsSellerSubAccount)
 *      如果子選單路徑有映射，則檢查對應的父權限
 * 4. 允許/拒絕存取
 * 
 * 範例：
 * - 子選單：marketplace/checkoutarea/index
 * - 映射到：marketplace/account/bill
 * - 檢查邏輯：如果子帳號有 'marketplace/account/bill' 權限，則可以存取 checkoutarea/index
 */
class Data
{
    /**
     * @var CheckoutManagementHelper
     */
    private $checkoutManagementHelper;

    /**
     * @param CheckoutManagementHelper $checkoutManagementHelper
     */
    public function __construct(
        CheckoutManagementHelper $checkoutManagementHelper
    ) {
        $this->checkoutManagementHelper = $checkoutManagementHelper;
    }

    /**
     * Add CheckoutManagement permissions to sub account permission types
     * 
     * 此方法會在 /sellersubaccount/account/edit/ 頁面的「允許的帳戶權限」表單中
     * 新增或刪除權限選項
     *
     * @param \Webkul\SellerSubAccount\Helper\Data $subject
     * @param array $result 所有權限類型的陣列 ['controller_path' => __('Label')]
     * @return array 修改後的權限類型陣列
     */
    public function afterGetAllPermissionTypes($subject, $result)
    {
        $config = $this->checkoutManagementHelper->getPermissionConfig();

        // ========== 新增權限 ==========
        foreach ($config as $parentPath => $parentConfig) {
            // 母選單權限（主要權限，用於顯示母選單）
            $result[$parentPath] = $parentConfig['label'];
            
            // 子選單權限（子選單項目的權限）
            // 在權限管理介面中，子選單標籤會加上父選單名稱前綴
            // 模板文字顯示，使用 getText()，需使用 __() 將文字資料先轉換為 Phrase 物件
            // __(1) 測試回傳格式 var_dump: object(Magento\Framework\Phrase)#7022 (2) { ["text":"Magento\Framework\Phrase":private]=> string(1) "1" ["arguments":"Magento\Framework\Phrase":private]=> array(0) { } }
            
            if (isset($parentConfig['children']) && is_array($parentConfig['children'])) {
                foreach ($parentConfig['children'] as $childPath => $childLabel) {
                    $result[$childPath] = __($parentConfig['label'] . ' - ' . $childLabel);
                }
            }
        }
        
        // ========== 刪除權限 ==========
        // 如果不需要某個權限，可以使用 unset() 移除
        // 例如：移除已廢棄的 reconciliation 權限
        // if (isset($result['marketplace/account/reconciliation'])) {
        //     unset($result['marketplace/account/reconciliation']);
        // }
        
        // 範例：移除其他不需要的權限
        // unset($result['marketplace/account/other_permission']);

        return $result;
    }

    /**
     * Add controller path mappings for CheckoutManagement
     * 用於將子選單的路徑映射到母選單權限
     *
     * @param \Webkul\SellerSubAccount\Helper\Data $subject
     * @param array $result
     * @return array
     */
    public function afterGetControllerMappedPermissionsSellerSubAccount($subject, $result)
    {
        $config = $this->checkoutManagementHelper->getPermissionConfig();
        
        // 將子選單的路徑映射到母選單權限
        // 格式：'實際子選單路徑' => '母選單權限路徑'
        foreach ($config as $parentPath => $parentConfig) {
            if (isset($parentConfig['children']) && is_array($parentConfig['children'])) {
                foreach ($parentConfig['children'] as $childPath => $childLabel) {
                    $result[$childPath] = $parentPath;
                }
            }
        }
        
        return $result;
    }
}

