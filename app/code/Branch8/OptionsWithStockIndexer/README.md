# Branch8_OptionsWithStockIndexer

## Overview

`Branch8_OptionsWithStockIndexer` is a custom Magento 2 module designed to **store and index inventory quantity for all products**, including:

* Simple products
* Configurable products (products with variations)

The module aggregates quantity data in a **custom index** so it can be queried efficiently without relying on Magento core inventory indexers.

---

## Why this module exists

We intentionally **do NOT use Magento core inventory indexers (MSI indexers)** for the following reasons:

* Avoid known and potential **core MSI bugs**
* Reduce coupling with complex core inventory logic
* Have **full control** over indexing behavior
* Make inventory data easier to extend and debug

This module acts as a **safe abstraction layer** on top of MSI tables.

---

## What data this module handles

The index stores **all quantity information of a product**, including:

* Quantity per SKU
* Quantity aggregated from multiple sources
* Quantity for products with variations (configurable products)

For configurable products:

* Quantities are calculated from their associated simple products
* The parent product quantity reflects the sum (or business-defined logic) of child quantities

---

## Indexing strategy

* Uses a **custom Magento indexer**
* Indexer runs in **Update on Schedule** mode
* Uses `mview.xml` to listen to MSI-related table changes

### Subscribed tables (MSI)

The indexer listens to changes from:

* `inventory_source_item`
* `inventory_reservation`
* `inventory_source_stock_link`

Any change in these tables will trigger a partial reindex.

---

## Indexed data destination

The indexed data is stored in a **custom table**, for example:

```
branch8_catalog_stock_index
```

Typical columns:

* `product_id`
* `sku`
* `qty`
* `is_in_stock`
* `updated_at`

(This structure can be adjusted based on business needs.)

---

## Benefits

* Fast quantity lookup
* Predictable indexing behavior
* Independent from Magento core inventory bugs
* Easier to customize for business-specific logic

---

## Typical use cases

* Product listing pages (PLP)
* Search result pages
* Custom APIs
* Reports requiring fast inventory access

---

## Notes

* This module **does not replace MSI**, it reads data from MSI tables
* Core inventory logic (reservation, source deduction) is still handled by Magento
* This module only **indexes and exposes inventory data** in a simplified form

---

## Maintenance & Debugging

Helpful commands:

```bash
bin/magento indexer:status
bin/magento indexer:reindex branch8_catalog_stock_indexer
```

Check mview changelog:

```sql
SELECT * FROM mview_changelog_branch8_catalog_stock_indexer;
```

---

## Future improvements

* Multi-stock support
* Website-level quantity
* Cache integration
* Async reindex optimization

---

## Author

Branch8 Engineering Team
