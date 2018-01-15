<?php

namespace SilverStripe\GridfieldQueuedExport\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\GridfieldQueuedExport\Forms\GridFieldQueuedExportButton;
use SilverStripe\ORM\DataExtension;

/**
 * Adjusts UserDefinedForm to use GridFieldQueuedExportButton instead of GridFieldExportButton (the default)
 */
class UserFormUseQueuedExportExtension extends DataExtension
{
    public function updateCMSFields(FieldList $fields)
    {
        $gridField = $fields->fieldByName('Root.Submissions.Submissions');

        $config = $gridField->getConfig();
        $oldExportButton = $config->getComponentByType(GridFieldExportButton::class);
        $config->addComponent($newExportButton = new GridFieldQueuedExportButton('buttons-after-left'));

        // Set Header and Export columns on new Export Button
        $newExportButton->setCsvHasHeader($oldExportButton->getCsvHasHeader());
        $newExportButton->setExportColumns($oldExportButton->getExportColumns());

        $config->removeComponentsByType(GridFieldExportButton::class);
    }
}
