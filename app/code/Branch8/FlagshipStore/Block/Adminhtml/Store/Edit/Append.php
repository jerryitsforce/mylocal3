<?php

namespace Branch8\FlagshipStore\Block\Adminhtml\Store\Edit;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;

class Append  extends \Magento\Backend\Block\Template
{

    protected $registry;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Registry                                $registry,
        array                                   $data = [],
        ?JsonHelper                             $jsonHelper = null,
        ?DirectoryHelper                        $directoryHelper = null
    )
    {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->registry = $registry;
    }


}