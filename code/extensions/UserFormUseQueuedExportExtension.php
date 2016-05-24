<?php

/**
 * Adjusts UserDefinedForm to use GridFieldQueuedExportButton instead of GridFieldExportButton (the default)
 */
class UserFormUseQueuedExportExtension extends DataExtension {
    function updateCMSFields(FieldList $fields) {
        $gridField = $fields->fieldByName('Root.Submissions.Submissions');

        $config = $gridField->getConfig();
        $config->removeComponentsByType('GridFieldExportButton');
        $config->addComponent(new GridFieldQueuedExportButton('buttons-after-left'));
    }
}
