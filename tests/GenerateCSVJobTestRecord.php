<?php

namespace SilverStripe\GridFieldQueuedExport\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class GenerateCSVJobTestRecord extends DataObject implements TestOnly
{
    private static $table_name = 'GenerateCSVJobTestRecord';

    private static $summary_fields = [
        'Title',
        'Content',
        'PublishOn',
    ];

    private static $default_sort = [
        'Title',
    ];

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'Varchar',
        'PublishOn' => 'Datetime'
    ];
}
