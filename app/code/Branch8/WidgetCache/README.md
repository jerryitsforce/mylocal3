# Branch8 Widget Cache Module

## Overview

The Branch8 Widget Cache module provides a custom caching solution specifically designed for widget content in Magento 2. This module enhances the performance of widget rendering by implementing intelligent caching mechanisms that reduce server load and improve page load times.

## Features

- **Custom Widget Caching**: Implements a dedicated cache system for widget content
- **Smart Cache Keys**: Generates unique cache keys based on widget type, store, customer group, and widget data
- **Plugin-Based Integration**: Uses Magento 2 plugins to seamlessly integrate with existing widget blocks
- **Configurable Cache Lifetime**: Admin-configurable cache lifetime settings
- **Debug Mode**: Built-in debug logging for cache operations
- **Cache Statistics**: Admin panel statistics for monitoring cache performance
- **Multiple Widget Support**: Supports various widget types including:
  - Catalog Product List Widget
  - CMS Block Widget
  - Product Point Widget
  - Best Seller Widget
  - Brand List Widget
  - Category List Widget
  - Generic Template Widgets

## Installation

### Method 1: Manual Installation

1. Copy the module files to `app/code/Branch8/WidgetCache/`
2. Enable the module:
   ```bash
   php bin/magento module:enable Branch8_WidgetCache
   ```
3. Run setup upgrade:
   ```bash
   php bin/magento setup:upgrade
   ```
4. Compile and deploy:
   ```bash
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```
5. Flush cache:
   ```bash
   php bin/magento cache:flush
   ```

### Method 2: Composer Installation

1. Add the module to your `composer.json`:
   ```json
   {
     "require": {
       "branch8/module-widget-cache": "1.0.0"
     }
   }
   ```
2. Run composer install:
   ```bash
   composer install
   ```
3. Enable the module:
   ```bash
   php bin/magento module:enable Branch8_WidgetCache
   ```
4. Run setup upgrade and deploy as above.

## Configuration

### Admin Configuration

Navigate to **Stores > Configuration > Advanced > Widget Cache** to configure:

- **Enable Widget Cache**: Enable/disable the widget cache functionality
- **Cache Lifetime**: Set cache lifetime in seconds (default: 3600 seconds = 1 hour)
- **Enable Debug Mode**: Enable debug logging for cache operations
- **Cache Statistics**: View current cache status and statistics

### Programmatic Configuration

```php
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

// Check if cache is enabled
$isEnabled = $widgetCacheHelper->isWidgetCacheEnabled();

// Get cache lifetime
$lifetime = $widgetCacheHelper->getWidgetCacheLifetime();

// Clear widget cache
$widgetCacheHelper->clearWidgetCache();
```

## Usage

### Basic Usage

The module automatically applies caching to supported widget blocks. No additional configuration is required for basic functionality.

### Advanced Usage

#### Custom Widget Integration

To add caching to a custom widget block:

1. Create a plugin for your widget block:

```php
<?php
namespace YourModule\Plugin;

use YourModule\Block\Widget\YourWidget;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

class YourWidgetPlugin
{
    private $widgetCacheHelper;

    public function __construct(WidgetCacheHelper $widgetCacheHelper)
    {
        $this->widgetCacheHelper = $widgetCacheHelper;
    }

    public function aroundToHtml(YourWidget $subject, \Closure $proceed): string
    {
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return $proceed();
        }

        $cacheKey = $this->widgetCacheHelper->generateWidgetCacheKey($subject);
        $cachedContent = $this->widgetCacheHelper->getCachedWidgetContent($cacheKey);
        
        if ($cachedContent !== null) {
            return $cachedContent;
        }

        $content = $proceed();
        $this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, ['your_widget']);
        
        return $content;
    }
}
```

2. Register the plugin in `di.xml`:

```xml
<type name="YourModule\Block\Widget\YourWidget">
    <plugin name="your_module_widget_cache_plugin"
            type="YourModule\Plugin\YourWidgetPlugin"
            sortOrder="10"/>
</type>
```

#### Cache Key Customization

```php
use Branch8\WidgetCache\Model\WidgetCache;

$widgetCache = $objectManager->get(WidgetCache::class);

// Generate custom cache key
$cacheKey = $widgetCache->generateCacheKey(
    'your_widget_type',
    ['custom_param' => 'value'],
    ['additional_data' => 'extra_info']
);

// Save custom content
$widgetCache->save($cacheKey, $content, ['custom_tag'], 7200);
```

## Cache Management

### Clearing Cache

#### Admin Panel
- Navigate to **System > Cache Management**
- Click "Flush Magento Cache" or "Flush Widget Cache"

#### Command Line

##### Clear All Magento Cache
```bash
# Clear all cache
php bin/magento cache:flush

# Clear specific cache type
php bin/magento cache:clean block_html

# Clear widget cache type specifically
php bin/magento cache:clean branch8_widget_cache
```

##### Clear Widget Cache Using Module Command
```bash
# Clear all widget cache
php bin/magento widget:cache:clear

# Clear cache by specific tags (single tag)
php bin/magento widget:cache:clear --tags=catalog_product_123

# Clear cache by multiple tags (comma-separated)
php bin/magento widget:cache:clear --tags=catalog_product_123,catalog_product_456

# Clear cache for specific widget types
php bin/magento widget:cache:clear --tags=cms_block
php bin/magento widget:cache:clear --tags=product_point_widget
php bin/magento widget:cache:clear --tags=bestseller_widget
php bin/magento widget:cache:clear --tags=brand_list_widget
php bin/magento widget:cache:clear --tags=category_list_widget
php bin/magento widget:cache:clear --tags=widget_cache

# Clear cache for all product-related widgets
php bin/magento widget:cache:clear --tags=catalog_product

# View command help
php bin/magento widget:cache:clear --help
```

##### Available Cache Tags
The module uses the following cache tags for precise cache management:

**Core Tags:**
- `WIDGET_CACHE` - Main cache type tag (automatically added to all entries)
- `widget_cache` - General widget cache tag

**Widget-Specific Tags:**
- `cms_block` - CMS Block Widget
- `catalog_product` - Product-related widgets
- `catalog_category` - Category-related widgets
- `catalog_product_list` - Catalog Product List Widget
- `template` - Generic Template Widget
- `product_point_widget` - Product Point Widget
- `bestseller_widget` - Best Seller Widget
- `brand_list_widget` - Brand List Widget
- `category_list_widget` - Category List Widget

**Dynamic Tags:**
- `catalog_product_{productId}` - Product-specific tags (e.g., `catalog_product_123`)

#### Programmatically
```php
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

// Clear all widget cache
$widgetCacheHelper->clearWidgetCache();

// Clear cache by specific tags
$widgetCacheHelper->clearWidgetCache(['catalog_product', 'widget_cache']);
```

### Cache Statistics

Monitor cache performance through:

1. **Admin Panel**: Stores > Configuration > Advanced > Widget Cache > Cache Statistics
2. **Command Line**:
   ```bash
   php bin/magento widget:cache:stats
   ```
3. **Programmatically**:
   ```php
   $stats = $widgetCacheHelper->getCacheStatistics();
   ```

## Troubleshooting

### Common Issues

1. **Cache not working**
   - Verify module is enabled: `php bin/magento module:status Branch8_WidgetCache`
   - Check configuration: Ensure "Enable Widget Cache" is set to "Yes"
   - Verify cache permissions and disk space

2. **Widget content not updating**
   - Clear cache: `php bin/magento cache:flush`
   - Check cache lifetime settings
   - Verify widget data changes are reflected in cache keys

3. **Performance issues**
   - Enable debug mode to monitor cache operations
   - Adjust cache lifetime based on content update frequency
   - Review cache statistics for hit/miss ratios

### Debug Mode

Enable debug mode in admin configuration to log cache operations:

```
[WidgetCache] Widget cache hit for catalog product list
[WidgetCache] Widget cache saved for CMS block
```

Debug logs are written to `var/log/system.log` or `var/log/debug.log`.

## API Reference

### WidgetCache Model

- `generateCacheKey(string $widgetType, array $widgetData, array $additionalData): string`
- `load(string $cacheKey): ?string`
- `save(string $cacheKey, string $content, array $tags, int $lifetime): bool`
- `remove(string $cacheKey): bool`
- `clearAll(): bool`
- `clearByTags(array $tags): bool`
- `isCacheEnabled(): bool`
- `getCacheStats(): array`

### WidgetCacheHelper

- `isWidgetCacheEnabled(?int $storeId): bool`
- `getWidgetCacheLifetime(?int $storeId): int`
- `isDebugModeEnabled(?int $storeId): bool`
- `generateWidgetCacheKey(BlockInterface $block, array $additionalData): string`
- `getCachedWidgetContent(string $cacheKey): ?string`
- `saveWidgetContentToCache(string $cacheKey, string $content, array $tags): bool`
- `clearWidgetCache(?array $tags): bool`
- `logDebug(string $message, array $context): void`
- `getCacheStatistics(): array`

## Requirements

- PHP 7.4+ or 8.1+
- Magento 2.4.x
- Magento Framework 103.0+ or 104.0+
- Magento Widget Module
- Magento Catalog Widget Module
- Magento CMS Module

## Compatibility

- Magento 2.4.0 - 2.4.7
- PHP 7.4.0 - 8.1.x
- All Magento editions (Community, Commerce, Cloud)

## Support

For support and questions:
- Check the debug logs for detailed error information
- Review the cache statistics for performance insights
- Ensure all module dependencies are properly installed

## License

This module is licensed under the Open Software License v. 3.0 (OSL-3.0).

## Changelog

### Version 1.0.0
- Initial release
- Basic widget caching functionality
- Support for major widget types
- Admin configuration interface
- Debug mode and statistics
