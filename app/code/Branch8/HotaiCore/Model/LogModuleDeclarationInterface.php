<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Model;

interface LogModuleDeclarationInterface
{

    /**
     * Return module information
     *
     * @return array
     * [
     *     [
     *         'value' => 'Module_Name',
     *         'label'=>'label'
     *         'note' => 'Some description'
     *     ],
     *     ...
     * ]
     */
    public function getInfo(): array;
}
