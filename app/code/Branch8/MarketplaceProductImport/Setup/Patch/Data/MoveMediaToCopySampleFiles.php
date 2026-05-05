<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpMassUpload
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceProductImport\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use \Psr\Log\LoggerInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class MoveMediaToCopySampleFiles implements
    DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var Reader $reader
     */
    private $_reader;

    /**
     * @var Filesystem $filesSystem
     */
    private $_filesystem;

     /**
      * @var File $fileDriver
      */
    private $_fileDriver;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Reader $reader
     * @param Filesystem $filesSystem
     * @param File $fileDriver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Reader $reader,
        Filesystem $filesSystem,
        File $fileDriver,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->_reader = $reader;
        $this->_filesystem = $filesSystem;
        $this->_fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moveDirToMediaDir();
    }

    /**
     * Copy Sample CSV,XML and XSL files to Media
     */
    private function moveDirToMediaDir()
    {
        try {
            $type = \Magento\Framework\App\Filesystem\DirectoryList::MEDIA;
            $sampleFilePath = $this->_filesystem->getDirectoryRead($type)
                            ->getAbsolutePath().'marketplace/massupload/samples/';
            $files = [
                'simple_default.xls',
                'ticket.xls',
                'ticket_edenred.xls',
                'ticket_fami.xls',
                'ticket_redeem.xls',
                'ticket_non_redeem.xls',
                'ticket_yoxi.xls',
            ];
            if ($this->_fileDriver->isExists($sampleFilePath)) {
                $this->_fileDriver->deleteDirectory($sampleFilePath);
            }
            if (!$this->_fileDriver->isExists($sampleFilePath)) {
                $this->_fileDriver->createDirectory($sampleFilePath, 0777);
            }
            foreach ($files as $file) {
                $filePath = $sampleFilePath.$file;
                if (!$this->_fileDriver->isExists($filePath)) {
                    $path = '/pub/media/marketplace/massupload/samples/'.$file;
                    $mediaFile = $this->_reader->getModuleDir('', 'Branch8_MarketplaceProductImport').$path;
                    if ($this->_fileDriver->isExists($mediaFile)) {
                        $this->_fileDriver->copy($mediaFile, $filePath);
                    }
                }
            }
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceProductImport', 'exceptionlog')){
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}
