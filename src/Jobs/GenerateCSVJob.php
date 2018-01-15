<?php

namespace SilverStripe\GridfieldQueuedExport\Jobs;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Security\RandomGenerator;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;

use SilverStripe\GridfieldQueuedExport\Forms\GridFieldQueuedExportButtonResponse;

/**
 * Iteratively exports GridField data to a CSV file on disk, in order to support large exports.
 * The generated file can be downloaded by the user through a CMS UI provided in {@link GridFieldQueuedExportButton}.
 *
 * Simulates a request to the GridFieldQueuedExportButton controller to retrieve the GridField instance,
 * from which the original data context can be derived (as an {@link SS_List instance).
 * This is a necessary workaround due to the limitations on serialising GridField's data description logic.
 * While a DataList is serialisable, other SS_List instances might not be.
 * We'd also need to consider custom value transformations applied via GridField->customDataFields lambdas.
 *
 * Relies on GridField being accessible in its original CMS controller context to the user
 * who triggered the export.
 */
class GenerateCSVJob extends AbstractQueuedJob
{
    /**
     * @var array
     */
    protected $session;

    public function __construct()
    {
        $this->ID = Injector::inst()->create(RandomGenerator::class)->randomToken('sha1');
        $this->Seperator = ',';
        $this->IncludeHeader = true;
        $this->HeadersOutput = false;
        $this->totalSteps = 1;
    }

    /**
     * @return string
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Export a CSV of a Gridfield";
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return md5(get_class($this) . '-' . $this->ID);
    }

    /**
     * @param GridField $gridField
     */
    public function setGridField(GridField $gridField)
    {
        $this->GridFieldName = $gridField->getName();
        $this->GridFieldURL = $gridField->Link();
    }

    /**
     * @param array $session
     * @return $this
     */
    public function setSession($session)
    {
        // None of the gridfield actions are needed, and they make the stored session bigger, so pull
        // them out.
        $actionkeys = array_filter(array_keys($session), function ($i) {
            return strpos($i, 'gf_') === 0;
        });

        $session = array_diff_key($session, array_flip($actionkeys));

        // This causes problems with logins
        unset($session['HTTP_USER_AGENT']);

        $this->session = $session;

        return $this;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function setColumns($columns)
    {
        $this->Columns = $columns;
    }

    public function setSeparator($seperator)
    {
        $this->Separator = $seperator;
    }

    public function setIncludeHeader($includeHeader)
    {
        $this->IncludeHeader = $includeHeader;
    }

    protected function getOutputPath()
    {
        $base = ASSETS_PATH . '/.exports';
        if (!is_dir($base)) {
            mkdir($base, 0770, true);
        }

        // Although the string is random, so should be hard to guess, also try and block access directly.
        // Only works in Apache though
        if (!file_exists("$base/.htaccess")) {
            file_put_contents("$base/.htaccess", "Deny from all\nRewriteRule .* - [F]\n");
        }

        $folder = $base . '/' . $this->getSignature();
        if (!is_dir($folder)) {
            mkdir($folder, 0770, true);
        }

        return $folder . '/' . $this->getSignature() . '.csv';
    }


    /**
     * @throws HTTPResponse_Exception
     * @return GridField
     */
    protected function getGridField()
    {
        /** @var array $session */
        $session = $this->getSession();

        // Store state in session, and pass ID to client side.
        $state = [
            'grid' => $this->GridFieldName,
            'actionName' => 'findgridfield',
            'args' => null
        ];

        // Ensure $id doesn't contain only numeric characters
        $id = 'gf_' . substr(md5(serialize($state)), 0, 8);

        // Simulate CSRF token use, hardcode to a random value in our fake session
        // so GridField can evaluate it in the Director::test() execution
        $token = Injector::inst()->create(RandomGenerator::class)->randomToken('sha1');

        // Add new form action into session for GridField to find when Director::test is called below
        $session[$id] = $state;
        $session['SecurityID'] = $token;

        // Construct the URL
        $actionKey = 'action_gridFieldAlterAction?' . http_build_query(['StateID' => $id]);
        $actionValue = 'Find Gridfield';

        $url = Controller::join_links(
            $this->GridFieldURL,
            '?' . http_build_query([$actionKey => $actionValue, 'SecurityID' => $token])
        );

        // Restore into the current session the user the job is exporting as
        Injector::inst()->get(HTTPRequest::class)->getSession()->set("loggedInAs", $session['loggedInAs']);

        // Then make a sub-query that should return a special SS_HTTPResponse with the gridfield object
        $res = Director::test($url, null, new Session($session), 'GET');

        // Great, it did, we can return it
        if ($res instanceof GridFieldQueuedExportButtonResponse) {
            $gridField = $res->getGridField();
            $gridField->getConfig()->removeComponentsByType(GridFieldPaginator::class);
            $gridField->getConfig()->removeComponentsByType(GridFieldPageCount::class);

            return $gridField;
        } else {
            user_error('Couldn\'t restore GridField', E_USER_ERROR);
        }
    }

    /**
     * @param $gridField
     * @param $columns
     */
    protected function outputHeader($gridField, $columns)
    {
        $fileData = '';
        $separator = $this->Separator;

        $headers = [];

        // determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
        // source name as the header instead
        foreach ($columns as $columnSource => $columnHeader) {
            $headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
        }

        $fileData .= "\"" . implode("\"{$separator}\"", array_values($headers)) . "\"";
        $fileData .= "\n";

        file_put_contents($this->getOutputPath(), $fileData, FILE_APPEND);
    }

    /**
     * This method is adapted from GridField->generateExportFileData()
     *
     * @param GridField $gridField
     * @param array $columns
     * @param int $start
     * @param int $count
     */
    protected function outputRows(GridField $gridField, $columns, $start, $count)
    {
        $fileData = '';
        $separator = $this->Separator;

        $items = $gridField->getManipulatedList();
        $items = $items->limit($count, $start);

        foreach ($items as $item) {
            if (!$item->hasMethod('canView') || $item->canView()) {
                $columnData = [];

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

                    $value = str_replace(["\r", "\n"], "\n", $value);
                    $columnData[] = '"' . str_replace('"', '""', $value) . '"';
                }

                $fileData .= implode($separator, $columnData);
                $fileData .= "\n";
            }

            if ($item->hasMethod('destroy')) {
                $item->destroy();
            }
        }

        file_put_contents($this->getOutputPath(), $fileData, FILE_APPEND);
    }

    public function setup()
    {
        $gridField = $this->getGridField();
        $this->totalSteps = $gridField->getManipulatedList()->count();
    }


    /**
     * Generate export fields for CSV.
     */
    public function process()
    {
        $gridField = $this->getGridField();

        if ($this->Columns) {
            $columns = $this->Columns;
        } elseif ($dataCols = $gridField->getConfig()->getComponentByType(GridFieldDataColumns::class)) {
            $columns = $dataCols->getDisplayFields($gridField);
        } else {
            $columns = singleton($gridField->getModelClass())->summaryFields();
        }

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
