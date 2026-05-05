<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Mmegamenu\Controller\Adminhtml\Mmegamenu;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;

class FlushCacheItem extends \Branch8\Mmegamenu\Controller\Adminhtml\Mmegamenu
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Context $context
     * @param ResourceConnection $resource
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->_resource = $resource;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            /*$resource   = $this->_resource;
            $table      = $resource->getTableName('branch8_megamenu_cache');
            $connection = $resource->getConnection();
            $connection->truncateTable($table);*/
            $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $cacheDir = $varDir->getAbsolutePath('cache/branch8_megamenu');
            if ($varDir->isExist($cacheDir)) {
                $varDir->delete($cacheDir);
            }
            $this->messageManager->addSuccess(__('The Mega Menu Cache has been flushed.'));
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong in progressing.'));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
