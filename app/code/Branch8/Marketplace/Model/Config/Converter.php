<?php
namespace Branch8\Marketplace\Model\Config;


class Converter implements \Magento\Framework\Config\ConverterInterface
{
    public function convert($source)
    {
        $urls = $source->getElementsByTagName('item');
        $seller2FaUrls = [];
        $iterator = 0;
        foreach ($urls as $_url) {
            foreach ($_url->childNodes as $_urlInfor){
                if(isset($_urlInfor->tagName) && $_urlInfor->nodeName == 'url'){
                    $seller2FaUrls[] = $_urlInfor->nodeValue;
                }
            }
            $iterator++;
        }
        return ['seller_2fa_urls' => $seller2FaUrls];
    }
}