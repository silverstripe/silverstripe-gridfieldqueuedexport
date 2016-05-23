<?php

class GenerateCSVJob extends AbstractQueuedJob {

    public function __construct() {
        $this->ID = uniqid();
        $this->Seperator = ',';
        $this->IncludeHeader = true;
        $this->HeadersOutput = false;
        $this->totalSteps = 1;
    }

    public function getJobType() {
        return QueuedJob::QUEUED;
    }

    public function getTitle() {
        return "Export a CSV of a Gridfield";
    }

    public function getSignature() {
        return md5(get_class($this) . '-' . $this->ID);
    }

    function setGridField($gridField) {
        $this->GridFieldName = $gridField->getName();
        $this->GridFieldURL = $gridField->Link();
    }

    function setSession($session) {
        // None of the gridfield actions are needed, and they make the stored session bigger, so pull
        // them out.
        $actionkeys = array_filter(array_keys($session), function ($i) {
            return strpos($i, 'gf_') === 0;
        });

        $session = array_diff_key($session, array_flip($actionkeys));

        // This causes problems with logins
        unset($session['HTTP_USER_AGENT']);

        $this->Session = $session;
    }

    function setColumns($columns) {
        $this->Columns = $columns;
    }

    function setSeparator($seperator) {
        $this->Separator = $seperator;
    }

    function setIncludeHeader($includeHeader) {
        $this->IncludeHeader = $includeHeader;
    }

    protected function getGridField() {
        $session = $this->Session;

        // Store state in session, and pass ID to client side.
        $state = array(
            'grid'       => $this->GridFieldName,
            'actionName' => 'findgridfield',
            'args'       => null
        );

        // Ensure $id doesn't contain only numeric characters
        $id = 'gf_' . substr(md5(serialize($state)), 0, 8);

        // Add new form action into session for GridField to find when Director::test is called below
        $session[$id] = $state;
        $session['SecurityID'] = '1';

        // Construct the URL
        $actionKey = 'action_gridFieldAlterAction?' . http_build_query(['StateID' => $id]);
        $actionValue = 'Find Gridfield';

        $url = $this->GridFieldURL . '?' .http_build_query([
            $actionKey => $actionValue,
            'SecurityID' => 1
        ]);

        // Restore into the current session the user the job is exporting as
        Session::set("loggedInAs", $session['loggedInAs']);

        // Then make a sub-query that should return a special SS_HTTPResponse with the gridfield object
        $res = Director::test($url, null, new Session($session), 'GET');

        // Great, it did, we can return it
        if ($res instanceof GridFieldQueuedExportButton_Response) {
            $gridField = $res->getGridField();
            $gridField->getConfig()->removeComponentsByType('GridFieldPaginator');
            $gridField->getConfig()->removeComponentsByType('GridFieldPageCount');

            return $gridField;
        } else {
            user_error('Couldn\'t restore GridField', E_USER_ERROR);
        }
    }

    protected function outputHeader($gridField, $columns) {
        $fileData = '';
        $separator = $this->Separator;

        $headers = array();

        // determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
        // source name as the header instead
        foreach ($columns as $columnSource => $columnHeader) {
            $headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
        }

        $fileData .= "\"" . implode("\"{$separator}\"", array_values($headers)) . "\"";
        $fileData .= "\n";

        file_put_contents('/tmp/' . $this->getSignature() . '.csv', $fileData, FILE_APPEND);
    }

    protected function outputRows($gridField, $columns, $start, $count) {
        $fileData = '';
        $separator = $this->Separator;

        $items = $gridField->getManipulatedList();
        $items = $items->limit($count, $start);

        foreach ($items as $item) {
            if (!$item->hasMethod('canView') || $item->canView()) {
                $columnData = array();

                foreach ($columns as $columnSource => $columnHeader) {
                    if (!is_string($columnHeader) && is_callable($columnHeader)) {
                        if ($item->hasMethod($columnSource)) {
                            $relObj = $item->{$columnSource}();
                        } else {
                            $relObj = $item->relObject($columnSource);
                        }

                        $value = $columnHeader($relObj);
                    } else {
                        $value = $gridField->getDataFieldValue($item, $columnSource);

                        if ($value === null) {
                            $value = $gridField->getDataFieldValue($item, $columnHeader);
                        }
                    }

                    $value = str_replace(array("\r", "\n"), "\n", $value);
                    $columnData[] = '"' . str_replace('"', '""', $value) . '"';
                }

                $fileData .= implode($separator, $columnData);
                $fileData .= "\n";
            }

            if ($item->hasMethod('destroy')) {
                $item->destroy();
            }
        }

        file_put_contents('/tmp/' . $this->getSignature() . '.csv', $fileData, FILE_APPEND);
    }


    public function setup() {
        $gridField = $this->getGridField();
        $this->totalSteps = $gridField->getManipulatedList()->count();
    }

    /**
     * Generate export fields for CSV.
     *
     * @param GridField $gridField
     * @return array
     */
    public function process() {
        $gridField = $this->getGridField();
        $columns = $this->Columns ?: singleton($gridField->getModelClass())->summaryFields();

        if ($this->IncludeHeader && !$this->HeadersOutput) {
            $this->outputHeader($gridField, $columns);
            $this->HeadersOutput = true;
        }

        $this->outputRows($gridField, $columns, $this->currentStep, 100);

        $this->currentStep += 100;

        if ($this->currentStep >= $this->totalSteps) {
            $this->isComplete = true;
        }
    }
}
