<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       30/03/2026
 */

namespace Branch8\Blog\Model;

use Branch8\HotaiCore\Model\LogModuleDeclarationInterface;

class LogDeclaration implements LogModuleDeclarationInterface
{
    /**
     * @return array
     */
    public function getInfo(): array
    {
        return [
            'value' => 'Branch8_Blog',
            'label' => 'Branch8_Blog',
            'note' => 'critical,debug=>exception.log'
        ];
    }

}
