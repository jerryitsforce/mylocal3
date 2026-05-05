<?php

namespace Branch8\Rma\Model\Config\Source;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Build dynamic log options from Branch8_Rma classes.
 */
class LogOption implements OptionSourceInterface
{
    /**
     * @var string
     */
    private const MODULE_NAME = 'Branch8_Rma';

    /**
     * Return options for Rma log selection.
     *
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        $modulePath = (new ComponentRegistrar())->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        if (!$modulePath || !is_dir($modulePath)) {
            return [];
        }

        $options = [];
        $valueCount = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($modulePath));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($modulePath . '/', '', $file->getPathname());
            if (!str_contains($relativePath, '/')) {
                continue;
            }

            $parts = explode('/', $relativePath);
            $className = pathinfo((string) array_pop($parts), PATHINFO_FILENAME);
            $typeFolder = (string) ($parts[0] ?? '');
            if ($className === '' || $typeFolder === '') {
                continue;
            }

            $originalLabel = $this->buildOriginalLabel($typeFolder, $className);
            $value = $className;
            $valueCount[$value] = ($valueCount[$value] ?? 0) + 1;
            if ($valueCount[$value] > 1) {
                $value = ($parts[1] ?? $typeFolder) . $className;
            }

            $options[] = [
                'value' => $value,
                'label' => __(
                    '%1 (var/log/Rma/%2/{Y_m_d}.log)',
                    $originalLabel,
                    $value
                ),
            ];
        }

        usort($options, static fn(array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));
        return $options;
    }

    /**
     * Build human readable original label name.
     *
     * @param string $typeFolder Top-level type folder.
     * @param string $className Class short name.
     * @return string
     */
    private function buildOriginalLabel(string $typeFolder, string $className): string
    {
        $typeMap = [
            'Console' => 'Command',
            'Controller' => 'Command',
            'Model' => 'Model',
            'Helper' => 'Helper',
            'Cron' => 'Cron',
            'Block' => 'Block',
            'Observer' => 'Observer',
            'Plugin' => 'Plugin',
            'ViewModel' => 'ViewModel',
            'Ui' => 'Ui',
            'Api' => 'Api',
        ];

        $prefix = $typeMap[$typeFolder] ?? $typeFolder;
        return $prefix . '_' . $className;
    }
}
