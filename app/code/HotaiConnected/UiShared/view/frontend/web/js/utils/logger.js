/**
 * HotaiConnected UiShared - Logger Utility
 * 
 * @module HotaiConnected_UiShared/js/utils/logger
 * @version 1.0.0
 * @author HotaiConnected
 * @created 2025-10-29 - 初始版本：统一的日志管理工具
 * 
 * 功能说明：
 * - 提供统一的日志输出接口
 * - 支持全局 DEBUG 开关控制
 * - 支持模块级别的 DEBUG 开关
 * - 自动添加模块标识前缀
 * - console.error 永远输出（不受 DEBUG 控制）
 * 
 * 使用场景：
 * - 所有需要日志输出的模块
 * - 开发调试和生产环境日志控制
 * - 统一的日志格式和管理
 * 
 * 设计理念：
 * - 简单易用的 API
 * - 灵活的控制方式（全局 + 模块级别）
 * - 零依赖（只依赖原生 console）
 * 
 * @example
 * // 基本使用
 * define(['hotaiLogger'], function(logger) {
 *     var log = logger.create('MyModule');
 *     
 *     log.info('初始化完成');
 *     log.warn('警告訊息');
 *     log.error('錯誤訊息');
 * });
 * 
 * @example
 * // 全局启用 DEBUG
 * require(['hotaiLogger'], function(logger) {
 *     logger.setGlobalDebug(true);
 * });
 * 
 * @example
 * // 模块级别控制
 * var log = logger.create('MyModule', { debug: true });
 * log.info('只有這個模組會輸出');
 */
define([], function() {
    'use strict';

    /**
     * 全局 DEBUG 开关
     * @type {boolean}
     * @private
     */
    var globalDebug = false;

    /**
     * Logger 类
     * @class
     * @param {string} moduleName - 模块名称
     * @param {Object} options - 配置选项
     * @param {boolean} [options.debug] - 模块级别的 DEBUG 开关（覆盖全局设置）
     */
    function Logger(moduleName, options) {
        this.moduleName = moduleName || 'Unknown';
        this.options = options || {};
        this.moduleDebug = this.options.debug;
    }

    /**
     * 判断是否应该输出日志
     * @private
     * @returns {boolean}
     */
    Logger.prototype._shouldLog = function() {
        // 模块级别优先
        if (this.moduleDebug !== undefined) {
            return this.moduleDebug;
        }
        // 使用全局设置
        return globalDebug;
    };

    /**
     * 格式化日志前缀
     * @private
     * @returns {string}
     */
    Logger.prototype._getPrefix = function() {
        return '[' + this.moduleName + ']';
    };

    /**
     * 内部日志输出方法
     * @private
     * @param {string} type - 日志类型 ('log', 'warn', 'error', 'info', 'debug')
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype._log = function(type) {
        // console.error 永远输出
        if (type === 'error') {
            var errorArgs = [this._getPrefix()].concat(Array.prototype.slice.call(arguments, 1));
            console.error.apply(console, errorArgs);
            return;
        }
        
        // 其他日志类型需要检查 DEBUG 开关
        if (!this._shouldLog()) {
            return;
        }
        
        var method = console[type] || console.log;
        var logArgs = [this._getPrefix()].concat(Array.prototype.slice.call(arguments, 1));
        method.apply(console, logArgs);
    };

    /**
     * 输出一般信息日志
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype.log = function() {
        var args = ['log'].concat(Array.prototype.slice.call(arguments));
        this._log.apply(this, args);
    };

    /**
     * 输出信息日志（别名）
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype.info = function() {
        var args = ['log'].concat(Array.prototype.slice.call(arguments));
        this._log.apply(this, args);
    };

    /**
     * 输出调试日志
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype.debug = function() {
        var args = ['log'].concat(Array.prototype.slice.call(arguments));
        this._log.apply(this, args);
    };

    /**
     * 输出警告日志
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype.warn = function() {
        var args = ['warn'].concat(Array.prototype.slice.call(arguments));
        this._log.apply(this, args);
    };

    /**
     * 输出错误日志（永远输出，不受 DEBUG 控制）
     * @param {...*} args - 要输出的参数
     */
    Logger.prototype.error = function() {
        var args = ['error'].concat(Array.prototype.slice.call(arguments));
        this._log.apply(this, args);
    };

    /**
     * 设置模块级别的 DEBUG 开关
     * @param {boolean} enabled - 是否启用
     */
    Logger.prototype.setDebug = function(enabled) {
        this.moduleDebug = !!enabled;
    };

    /**
     * Logger 工厂和工具函数
     */
    return {
        /**
         * 创建一个 Logger 实例
         * @param {string} moduleName - 模块名称
         * @param {Object} [options] - 配置选项
         * @param {boolean} [options.debug] - 模块级别的 DEBUG 开关
         * @returns {Logger} Logger 实例
         */
        create: function(moduleName, options) {
            return new Logger(moduleName, options);
        },

        /**
         * 设置全局 DEBUG 开关
         * @param {boolean} enabled - 是否启用
         */
        setGlobalDebug: function(enabled) {
            globalDebug = !!enabled;
            console.log('[Logger] 全局 DEBUG 已', enabled ? '启用' : '禁用');
        },

        /**
         * 获取全局 DEBUG 状态
         * @returns {boolean}
         */
        getGlobalDebug: function() {
            return globalDebug;
        },

        /**
         * 临时启用 DEBUG（用于控制台快速调试）
         * @example
         * require(['hotaiLogger'], function(logger) { logger.enableDebug(); });
         */
        enableDebug: function() {
            this.setGlobalDebug(true);
        },

        /**
         * 临时禁用 DEBUG
         * @example
         * require(['hotaiLogger'], function(logger) { logger.disableDebug(); });
         */
        disableDebug: function() {
            this.setGlobalDebug(false);
        }
    };
});

