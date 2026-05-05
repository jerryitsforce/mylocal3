<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Model\Config\Source\LogModules;

use Branch8\HotaiCore\Model\LogModuleProvider;
use Magento\Framework\Option\ArrayInterface;

class Modules implements ArrayInterface
{
    protected $moduleList;

    public function __construct(LogModuleProvider $moduleList)
    {
        $this->moduleList = $moduleList;
    }

    public function toOptionArray()
    {
        $options = [];
        foreach ($this->moduleList->collect() as $module) {
            $options[] = [
                'label' => $module['name'],
                'value' => $module['name']
            ];
        }
        return $options;
    }
}
