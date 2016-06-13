<?php

/**
 * Adjusts UserDefinedForm to use GridFieldQueuedExportButton instead of GridFieldExportButton (the default)
 */
class UserFormUseQueuedExportExtension extends DataExtension {
    function updateCMSFields(FieldList $fields) {
        $gridField = $fields->fieldByName('Root.Submissions.Submissions');

        $config = $gridField->getConfig();
        $oldExportButton = $config->getComponentsByType('GridFieldExportButton');
        $config->addComponent($newExportButton = new GridFieldQueuedExportButton('buttons-after-left'));
        
        // Set Header and Export columns on new Export Button
        $newExportButton->setCsvHasHeader($oldExportButton->getCsvHasHeader()); 
        $newExportButton->setExportColumns($oldExportButton->getExportColumns());
        
        $config->removeComponentsByType('GridFieldExportButton');
    }
}
