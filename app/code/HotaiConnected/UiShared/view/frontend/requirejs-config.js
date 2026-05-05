/**
 * HotaiConnected UiShared - RequireJS Configuration
 * 
 * 此配置文件定義了所有共用的 UI 組件、工具和第三方套件的全域路徑映射
 * 其他 HotaiConnected 模組可以直接使用這些別名，無需重複配置
 * 
 * 包含的套件：
 * - Tabulator: 互動式資料表格庫
 * - SumoSelect: jQuery 多選下拉選單插件
 * 
 * @module HotaiConnected_UiShared
 * @version 1.0.0
 */
var config = {
    map: {
        '*': {
            // ==========================================
            // 第三方套件別名
            // ==========================================
            'tabulator': 'HotaiConnected_UiShared/js/vendor/tabulator.min',
            'sumoselect': 'HotaiConnected_UiShared/js/vendor/sumoselect.min',
            
            // ==========================================
            // 配置文件別名
            // ==========================================
            'tabulatorConfig': 'HotaiConnected_UiShared/js/config/tabulator-config',
            'sumoselectConfig': 'HotaiConnected_UiShared/js/config/sumoselect-config',
            
            // ==========================================
            // 共用服務別名
            // ==========================================
            'hotaiRequest': 'HotaiConnected_UiShared/js/services/request',
            
            // ==========================================
            // 共用組件別名
            // ==========================================
            'hotaiLoadingMask': 'HotaiConnected_UiShared/js/components/loading-mask',
            'hotaiSelectedItemsDisplay': 'HotaiConnected_UiShared/js/components/selected-items-display',
            'hotaiSearchableDialogMultiselect': 'HotaiConnected_UiShared/js/components/searchable-dialog-multiselect',
            'hotaiTriStateGroupMultiselect': 'HotaiConnected_UiShared/js/components/tri-state-group-multiselect',
            'tabulatorColumnControl': 'HotaiConnected_UiShared/js/components/tabulator-column-control',
            'hotaiFiltersBase': 'HotaiConnected_UiShared/js/components/filters-base',
            
            // ==========================================
            // 共用工具別名
            // ==========================================
            'hotaiToastMessage': 'HotaiConnected_UiShared/js/utils/toast-message',
            'hotaiDatePickers': 'HotaiConnected_UiShared/js/utils/date-pickers',
            'hotaiFileNameParts': 'HotaiConnected_UiShared/js/utils/file-name-parts',
            'hotaiSumoselect': 'HotaiConnected_UiShared/js/utils/sumoselect',
            'hotaiLogger': 'HotaiConnected_UiShared/js/utils/logger'
        }
    },
    shim: {
        // ==========================================
        // 定義套件依賴關係
        // ==========================================
        'tabulator': {
            deps: ['jquery']
        },
        'sumoselect': {
            deps: ['jquery']
        }
    }
};

