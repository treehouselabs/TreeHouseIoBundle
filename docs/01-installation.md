## Installation

Install using composer:

```
composer require "treehouselabs/io-bundle:~1.0"
```

Enable the bundle in the kernel:

```php
// app/AppKernel.php
$bundles[] = new TreeHouse\IoBundle\TreeHouseIoBundle();
```

## Configuration

### Reference

Below is the complete reference, including the default values.

```yaml
tree_house_io:
  # base directory where data will be stored
  data_dir: %kernel.root_dir%/var/data
  # alias to a service implementing OriginManagerInterface, required
  origin_manager_id: # my_bundle.origin_manager
  # alias to a service implementing SourceManagerInterface, required
  source_manager_id: # my_bundle.source_manager
  # alias to a service implementing SourceProcessorInterface
  source_processor_id: tree_house.io.source.processor.delegating
  # alias to a service implementing SourceCleanerInterface
  source_cleaner_id: tree_house.io.source.cleaner.delegating
  import:
    # directory where imported feeds are saved to
    dir: %tree_house.io.data_dir%/import
    # service to use for logging items during imports
    item_logger:
      type: redis
      client: # service-id of a \Redis instance
  export:
    # Will store cache files for individual items
    cache_dir: %tree_house.io.data_dir%/export
    # Will store final export file for all exported feeds
    output_dir: %tree_house.io.data_dir%/export
  bridges:
    # Enable available bridges
    - WorkerBundle
```
