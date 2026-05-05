<?php

namespace Branch8\MarketplaceStaging\Model;

use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\ObjectManager;

class WysiwygSetupConfig
{
    private static $instances = [];

    /**
     * @param $filesBrowserWindowUrl
     * @return false|mixed|string
     */
    public static function getToolBarConfig($filesBrowserWindowUrl = null)
    {
        if (isset(self::$instances[$filesBrowserWindowUrl])) {
            return self::$instances[$filesBrowserWindowUrl];
        }
        /**
         * @var $assetRepo Repository
         */
        $assetRepo = ObjectManager::getInstance()->get(Repository::class);
        $settings = [
            //'fixed_toolbar_container' => '.pagebuilder-content-type',
            'font_size_formats' => '10px 12px 14px 16px 18px 20px 24px 26px 28px 32px 34px 36px 38px 40px 42px 48px 52px 56px 64px 72px',
            'lineheight_formats' => '10px 12px 14px 16px 18px 20px 24px 26px 28px 32px 34px 36px 38px 40px 42px 48px 52px 56px 64px 72px',
            'style_formats' => [
                'paragraph' => [
                    'title' => 'Paragraph',
                    'block' => 'p',
                ],
                'heading1' => [
                    'title' => 'Heading 1',
                    'block' => 'h1',
                ],
                'heading2' => [
                    'title' => 'Heading 2',
                    'block' => 'h2',
                ],
                'heading3' => [
                    'title' => 'Heading 3',
                    'block' => 'h3',
                ],
                'heading4' => [
                    'title' => 'Heading 4',
                    'block' => 'h4',
                ],
                'heading5' => [
                    'title' => 'Heading 5',
                    'block' => 'h5',
                ],
                'heading6' => [
                    'title' => 'Heading 6',
                    'block' => 'h6',
                ],
                'important' => [
                    'title' => 'Important',
                    'block' => 'div',
                    'classes' => 'cms-content-important',
                ],
                'preformatted' => [
                    'title' => 'Preformatted',
                    'block' => 'pre',
                ],
            ],
            'font_family_formats' => 'Andale Mono=andale mono,monospace;Arial=arial,helvetica,sans-serif;Arial Black=arial black,sans-serif;Book Antiqua=book antiqua,palatino,serif;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier,monospace;Georgia=georgia,palatino,serif;Helvetica=helvetica,arial,sans-serif;Impact=impact,sans-serif;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco,monospace;Times New Roman=times new roman,times,serif;Trebuchet MS=trebuchet ms,geneva,sans-serif;Verdana=verdana,geneva,sans-serif;Webdings=webdings;Wingdings=wingdings,zapf dingbats'
        ];
        $tools = [
            'undo redo',
            'styles',
            'fontfamily fontsizeinput',
            'lineheight',
            'forecolor backcolor',
            'bold italic underline',
            'alignleft aligncenter alignright',
            'numlist bullist',
            'link image table charmap'
        ];
        $plugins = [
            'advlist',
            'autolink lists link charmap media',
            'table code help table image'
        ];
        $config = [
            "width" => "100%",
            "height" => "500px",
            "plugins" => [["name" => "image"]],
            "tinymce" => [
                "toolbar" => join(' | ', $tools),
                "plugins" => join(' ', $plugins),
                "content_css" => $assetRepo->getUrl('Branch8_MarketplaceStaging::css/tinymce.css')
            ],
            'settings' => [
                'font_size_formats' => $settings['font_size_formats'],
                'lineheight_formats' => $settings['lineheight_formats'],
                'style_formats' => $settings['style_formats'],
                'font_family_formats' => $settings['font_family_formats'],
                'default_font_stack' => ['-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Open-Sans']
            ],
            'font_size_formats' => $settings['font_size_formats'],
            'lineheight_formats' => $settings['lineheight_formats'],
            'style_formats' => $settings['style_formats'],
            'font_family_formats' => $settings['font_family_formats'],
            'default_font_stack' => ['-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Open-Sans']
        ];


        if ($filesBrowserWindowUrl) {
            $config['files_browser_window_url'] = $filesBrowserWindowUrl;
        }
        self::$instances[$filesBrowserWindowUrl]= json_encode($config);
        return self::$instances[$filesBrowserWindowUrl];
    }
}
