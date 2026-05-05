<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       26/03/2026
 */

namespace Branch8\HotaiCore\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
class LoggerProxy extends \Magento\Framework\Logger\LoggerProxy
{
    private $scopeConfig = null;
    private ObjectManagerInterface $objectManager;
    private $settings = null;
    /**
     * @var string
     */
    private string $yourModuleName;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $moduleName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        string $moduleName
    ) {
        $this->objectManager = $objectManager;
        $this->yourModuleName = $moduleName;
        parent::__construct($objectManager);
    }

    /**
     * @return ScopeConfigInterface
     */
    private function getScopeConfigInterface(): ScopeConfigInterface
    {
        if (null === $this->scopeConfig) {
            $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        }
        return $this->scopeConfig;
    }

    /**
     * @param $level
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, $level)) {
            return;
        }
        parent::log($level, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'info')) {
            return;
        }
        parent::info($message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'critical')) {
            return;
        }
        parent::critical($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function error(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'error')) {
            return;
        }
        parent::error($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function notice(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'notice')) {
            return;
        }
        parent::notice($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function warning(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'warning')) {
            return;
        }
        parent::warning($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function debug(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'debug')) {
            return;
        }
        parent::debug($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function alert(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'alert')) {
            return;
        }
        parent::alert($message, $context);
    }

    /**
     * @param \Stringable|string $message
     * @param array $context
     * @return void
     */
    public function emergency(\Stringable|string $message, array $context = []): void
    {
        if (empty($this->yourModuleName) || !$this->isAllowed($this->yourModuleName, 'emergency')) {
            return;
        }
        parent::emergency($message, $context);
    }

    /**
     * @return array
     */
    public function getParsedConfig()
    {
        if (null === $this->settings) {
            $value = $this->getScopeConfigInterface()->getValue('branch8_debug/setting/white_list_modules');
            if (empty($value)) {
                return [];
            }
            if (is_string($value)) {
                $value = json_decode($value, true);
            }
            if (!is_array($value)) {
                return [];
            }
            $result = [];
            foreach ($value as $row) {
                if (empty($row['module_name'])) {
                    continue;
                }
                $module = trim($row['module_name']);
                $levels = [];
                if (!empty($row['log_levels'])) {
                    if (is_string($row['log_levels'])) {
                        $levels = explode(',', $row['log_levels']);
                    } elseif (is_array($row['log_levels'])) {
                        $levels = $row['log_levels'];
                    }
                }
                $levels = array_filter(array_map('trim', $levels));
                if (!isset($result[$module])) {
                    $result[$module] = [];
                }
                $result[$module] = array_values(array_unique(array_merge(
                    $result[$module],
                    $levels
                )));
            }
            $this->settings = $result;
        }
        return $this->settings;
    }
    /**
     * @param string $module
     * @param string $level
     * @return bool
     */
    public function isAllowed(string $module, string $level): bool
    {
        $config = $this->getParsedConfig();
        if (isset($config['*'])) {
            return in_array('*', $config['*']) || in_array($level, $config['*']);
        }
        if (!isset($config[$module])) {
            return false;
        }
        return in_array('*', $config[$module]) || in_array($level, $config[$module]);
    }
}
