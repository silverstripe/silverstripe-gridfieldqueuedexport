# GridField Queued Export

[![Build Status](https://travis-ci.org/silverstripe/silverstripe-gridfieldqueuedexport.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-gridfieldqueuedexport)

## Introduction

Allows for large data set exports from a `GridField`. By using an asynchronous job queue, we avoid
running out of PHP memory or exceeding any maximum execution time limits.

The exact limitations of a standard `GridField` export vary based on the server configuration, 
server capacity and the complexity of the exported `DataObject`. 
As a rough guide, you should consider using this module
when more than 1000 records need to be exported. The module should be able to export
10,000 records on a standard server configuration within a few minutes.

## Requirements

 * SilverStripe 3.3+
 * The [queuedjobs](https://github.com/silverstripe-australia/silverstripe-queuedjobs) module

## Installation via Composer

	cd path/to/my/silverstripe/site
	composer require "silverstripe/gridfieldqueuedexport:*"

## Configuration

Since this component operates on a `GridField`, you can simply use it's `addComponent()` API.

```php
$gridField = new GridField('Pages', 'All pages', SiteTree::get())
$config = $gridField->getConfig();
$config->addComponent(new GridFieldQueuedExportButton('buttons-after-left'));
```

If you want to replace the `GridFieldExportButton` created by the default GridField configuration,
you also need to call `removeComponentsByType()`.

```php
// Find GridField
$gridField = $fields->fieldByName('MyGridField');
$config = $gridField->getConfig();

// Add new component
$oldExportButton = $config->getComponentByType('GridFieldExportButton');
$config->addComponent($newExportButton = new GridFieldQueuedExportButton('buttons-after-left'));

// Set Header and Export columns on new Export Button
$newExportButton->setCsvHasHeader($oldExportButton->getCsvHasHeader()); 
$newExportButton->setExportColumns($oldExportButton->getExportColumns());

// Remove original component
$config->removeComponentsByType('GridFieldExportButton');
```

Note: This module is preconfigured to work with the
[silverstripe/userforms](http://github.com/silverstripe/silverstripe-userforms)
submission CSV export.

## Related

 * [silverstripe/queuedjobcsvexport](https://github.com/open-sausages/queuedjobcsvexport): General purpose CSV exports through queuedjobs (without a dependency on GridField)
