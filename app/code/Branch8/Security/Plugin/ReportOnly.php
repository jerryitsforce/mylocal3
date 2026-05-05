<?php

namespace Branch8\Security\Plugin;

use Branch8\Security\Helper\Configuration;
use Magento\Csp\Api\Data\ModeConfiguredInterface;
use Magento\Csp\Model\Mode\ConfigManager;
use Magento\Csp\Model\Mode\Data\ModeConfigured;

class ReportOnly
{
    /** @var Configuration */
    protected $configuration;

    public function __construct(
        Configuration $configuration
    )
    {
        $this->configuration = $configuration;
    }

    /**
     * @param ConfigManager $subject
     * @param ModeConfiguredInterface $result
     * @return ModeConfiguredInterface
     */
    public function afterGetConfigured(ConfigManager $subject, ModeConfiguredInterface $result): ModeConfiguredInterface
    {
        if (!$this->configuration->isEnabled()) {
            return $result;
        }
        $reportMode = $this->configuration->isReport();
        $uri = $result->getReportUri();

        return new ModeConfigured($reportMode, !empty($uri) ? $uri : null);
    }
}
