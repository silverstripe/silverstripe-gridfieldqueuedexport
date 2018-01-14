<?php

namespace SilverStripe\GridfieldQueuedExport;


use SilverStripe\Dev\TestOnly;



class GenerateCSVJobTest_Record extends \SilverStripe\ORM\DataObject implements TestOnly
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
