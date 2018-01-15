<?php

namespace SilverStripe\GridFieldQueuedExport\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class GenerateCSVJobTestRecord extends DataObject implements TestOnly
{
    private static $summary_fields = array(
        'Title',
        'Content',
        'PublishOn',
    );

    private static $default_sort = array(
        'Title',
    );

    private static $db = array(
        'Title' => 'Varchar',
        'Content' => 'Varchar',
        'PublishOn' => 'SS_DateTime'
    );
}
