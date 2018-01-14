<?php

namespace SilverStripe\GridfieldQueuedExport;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\HTTPResponse;

/**
 * A special type of SS_HTTPResponse that GridFieldQueuedExportButton returns in response to the "findgridfield"
 * action, which includes a reference to the gridfield
 */
class GridFieldQueuedExportButton_Response extends HTTPResponse
{
    private $gridField;

    public function __construct(GridField $gridField)
    {
        $this->gridField = $gridField;
        parent::__construct('', 500);
    }

    public function getGridField()
    {
        return $this->gridField;
    }
}
