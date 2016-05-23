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

TODO
