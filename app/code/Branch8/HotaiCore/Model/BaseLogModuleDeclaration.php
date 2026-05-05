<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Model;

/**
 * Class BaseLogModuleDeclaration
 */
class BaseLogModuleDeclaration implements LogModuleDeclarationInterface
{
    /**
     * @param string $value
     * @param string $label
     * @param string $note
     */
    public function __construct(
        private readonly string $value,
        private readonly string $label = '',
        private readonly string $note = ''
    ) {
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'note'  => $this->note
        ];
    }
}