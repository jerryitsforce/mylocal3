<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       26/03/2026
 */

namespace Branch8\HotaiCore\Block\Adminhtml\Form\Field;

use Branch8\HotaiCore\Block\Adminhtml\Form\Field\WhiteListModuleLog\LogLevels;
use Branch8\HotaiCore\Block\Adminhtml\Form\Field\WhiteListModuleLog\ModuleName;
use Branch8\HotaiCore\Block\Adminhtml\Form\Field\WhiteListModuleLog\Note;
use Branch8\HotaiCore\Model\LogModuleProvider;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Vendor\Module\Block\Adminhtml\Form\Field\TaxColumn;

class WhiteListModuleLog extends AbstractFieldArray
{

    protected $_template = 'Branch8_HotaiCore::system/config/whitelistModules.phtml';
    /**
     * @var LogModuleProvider
     */
    private $provider;

    /**
     * @var
     */
    private $nameRender;
    /**
     * @var
     */
    private $logLevelRender;
    /**
     * @var
     */
    private $noteRender;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param LogModuleProvider $provider
     * @param array $data
     * @param \Magento\Framework\View\Helper\SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        LogModuleProvider $provider,
        array $data = [],
        ?\Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer = null
    )
    {
        $this->provider = $provider;
        parent::__construct($context, $data, $secureRenderer);
    }
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('module_name', ['label' => __('Module Name'), 'renderer' => $this->getNameRender()]);
        $this->addColumn('log_levels', ['label' => __('Log Levels'), 'renderer' => $this->getLogLevelsRender()]);
        $this->addColumn('note', ['label' => __('Note'), 'renderer' => $this->getNoteRender()]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $row->setData('option_extra_attrs', []);
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    private function getNameRender()
    {
        if (!$this->nameRender) {
            $this->nameRender = $this->getLayout()->createBlock(
                ModuleName::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->nameRender;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    private function getLogLevelsRender()
    {
        if (!$this->logLevelRender) {
            $this->logLevelRender = $this->getLayout()->createBlock(
                LogLevels::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->logLevelRender;
    }
    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    private function getNoteRender()
    {
        if (!$this->noteRender) {
            $this->noteRender = $this->getLayout()->createBlock(
                Note::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->noteRender;
    }

    /**
     * @return string
     */
    public function getUsedModulesJson(): string
    {
        $usedModules = [];
        foreach ($this->getArrayRows() as $row) {
            $usedModules[] = $row->getData('module_name') ?: '';
        }
        return json_encode(array_values(array_filter($usedModules)));
    }

    /**
     * @return array[]
     */
    public function getModules()
    {
        return $this->provider->collect();
    }
}
