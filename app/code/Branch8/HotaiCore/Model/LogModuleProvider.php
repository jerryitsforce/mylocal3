<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Model;

class LogModuleProvider
{
    private array $providers;

    /**
     * @param array $list
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * @return array[]
     */
    public function collect()
    {
        $result = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof LogModuleDeclarationInterface) {
                continue;
            }
            $info = $provider->getInfo();
            if (empty($info['label'])) {
                $info['label'] = $info['value'];
            }
            if (empty($info['note'])) {
                $info['note'] = '';
            }
            $result[] = $info;
        }
        // Sort alphabetically by module name
        usort($result, function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        return $result;
    }
}
