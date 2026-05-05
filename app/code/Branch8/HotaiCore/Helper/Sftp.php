<?php

namespace Branch8\HotaiCore\Helper;

use Magento\Framework\Filesystem\Io\Sftp as OriMagentoSftp;

class Sftp extends OriMagentoSftp
{
    public function getSFTPErrors(): array
    {
        return $this->_connection->getSFTPErrors();
    }
}
