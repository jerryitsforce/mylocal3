<?php

namespace Branch8\GA4\Plugin;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\Datalayer;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Layout;

class ResponseHttp
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Datalayer
     */
    private $datalayer;

    /**
     * @param Config $config
     * @param Datalayer $datalayer
     */
    public function __construct(
        Config    $config,
        Datalayer $datalayer
    )
    {
        $this->datalayer = $datalayer;
        $this->config = $config;
    }

    /**
     * @param \Magento\Framework\App\Http\Context $subject
     * @return null
     */
    public function afterRenderResult(Layout $subject, Layout $result, ResponseInterface $httpResponse)
    {
        $content = $httpResponse->getContent();
        if ($this->config->isEnabled()) {
            $gtmCodeSnippet = $this->datalayer->getDataLayerScript();
            $gtmCodeSnippet .= "\n";
            $content = str_replace("<!-- ##ga4_snippet_scripts## -->", $gtmCodeSnippet, $content);
            $httpResponse->setContent($content);
        }
        return $result;
    }
}
