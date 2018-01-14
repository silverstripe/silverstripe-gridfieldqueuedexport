<?php

namespace SilverStripe\GridfieldQueuedExport;


use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldExportButton;

use SilverStripe\GridfieldQueuedExport\GridFieldQueuedExportButton;




class GenerateCSVJobTest_Controller extends Controller implements TestOnly
{
    private static $allowed_actions = array('Form');

    public function Link()
    {
        return 'jobtest/';
    }

    public function Form()
    {
        // Get records
        $records = GenerateCSVJobTest_Record::get();

        // Set config
        $config = GridFieldConfig_RecordEditor::create();
        $config->removeComponentsByType(GridFieldExportButton::class);
        $config->addComponent(new GridFieldQueuedExportButton('buttons-after-left'));
        $fields = new GridField('MyGridfield', 'My Records', $records, $config);
        return new Form($this, Form::class, new FieldList($fields), new FieldList());
    }
}
