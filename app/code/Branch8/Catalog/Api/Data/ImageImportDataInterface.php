<?php

namespace Branch8\Catalog\Api\Data;

interface ImageImportDataInterface
{
    const _FILE = 'file';

    const _TYPE = 'type';

    /**
     * @param string $file
     * @return mixed
     */
    public function setFile(string $file);

    /**
     * @return string
     */
    public function getFile():string;

    /**
     * @param string $type
     * @return mixed
     */
    public function setType(string $type);

    /**
     * @return string
     */
    public function getType():string;
}