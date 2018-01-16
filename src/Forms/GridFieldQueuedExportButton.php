<?php

namespace SilverStripe\GridfieldQueuedExport\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\GridfieldQueuedExport\Jobs\GenerateCSVJob;

/**
 * A button you can add to a GridField to export that GridField as a CSV. Should work with any sized GridField,
 * as the export is done using a queuedjob in the background.
 */
class GridFieldQueuedExportButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler
{

    /**
     * @var array Map of a property name on the exported objects, with values being the column title in the CSV file.
     * Note that titles are only used when {@link $csvHasHeader} is set to TRUE.
     */
    protected $exportColumns;

    /**
     * @var string
     */
    protected $csvSeparator = ",";

    /**
     * @var boolean
     */
    protected $csvHasHeader = true;

    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     * @param array $exportColumns The columns to include in the export
     */
    public function __construct($targetFragment = "after", $exportColumns = null)
    {
        $this->targetFragment = $targetFragment;
        $this->exportColumns = $exportColumns;
    }

    /**
     * Place the export button in a <p> tag below the field
     */
    public function getHTMLFragments($gridField)
    {
        $button = GridField_FormAction::create(
            $gridField,
            'export',
            _t('SilverStripe\\Forms\\GridField\\GridField.CSVEXPORT', 'Export to CSV'),
            'export',
            null
        );
        $button->setAttribute('data-icon', 'download-csv');
        $button->addExtraClass('btn btn-secondary no-ajax font-icon-down-circled action_export action_batch_export');
        $button->setForm($gridField->getForm());

        return [
            $this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
        ];
    }


    /**
     * This class is an action button
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['export', 'findgridfield'];
    }

    /**
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'export') {
            return $this->startExport($gridField);
        } elseif ($actionName == 'findgridfield') {
            return new GridFieldQueuedExportButtonResponse($gridField);
        }
    }

    /**
     * @param GridField $gridField
     * @return HTTPResponse
     */
    public function startExport($gridField)
    {
        $job = new GenerateCSVJob();

        // Set the parameters that allow re-discovering this gridfield during background execution
        $job->setGridField($gridField);
        $job->setSession(Injector::inst()->get(HTTPRequest::class)->getSession()->getAll());

        // Set the parameters that control CSV exporting
        $job->setSeparator($this->csvSeparator);
        $job->setIncludeHeader($this->csvHasHeader);
        if ($this->exportColumns) {
            $job->setColumns($this->exportColumns);
        }

        // Queue the job
        singleton(QueuedJobService::class)->queueJob($job);

        // Redirect to the status update page
        return Controller::curr()->redirect($gridField->Link('/export/' . $job->getSignature()));
    }


    /**
     * This class is also a URL handler
     *
     * @param GridField $gridField
     * @return array
     */
    public function getURLHandlers($gridField)
    {
        return [
            'export/$ID' => 'checkExport',
            'export_download/$ID' => 'downloadExport'
        ];
    }

    protected function getExportPath($id)
    {
        if ($id instanceof QueuedJobDescriptor) {
            $id = $id->Signature;
        }
        return ASSETS_PATH . "/.exports/$id/$id.csv";
    }


    /**
     * Handle the export, for both the action button and the URL
     *
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return HTTPResponse|DBHTMLText
     */
    public function checkExport($gridField, $request = null)
    {
        $id = $request->param('ID');
        $job = QueuedJobDescriptor::get()->filter('Signature', $id)->first();

        if ((int)$job->RunAsID !== Security::getCurrentUser()->ID) {
            return Security::permissionFailure();
        }

        $controller = $gridField->getForm()->getController();

        $breadcrumbs = $controller->Breadcrumbs(false);
        $breadcrumbs->push(new ArrayData([
            'Title' => _t('SilverStripe\\Forms\\GridField\\GridField.CSVEXPORT', 'Export to CSV'),
            'Link' => false
        ]));

        $parents = $controller->Breadcrumbs(false)->items;
        $backlink = array_pop($parents)->Link;

        $data = new ArrayData([
            'ID' => $id,
            'Link' => Controller::join_links($gridField->Link(), 'export', $job->Signature),
            'Backlink' => $backlink,
            'Breadcrumbs' => $breadcrumbs,
            'GridName' => $gridField->getname()
        ]);

        if ($job->JobStatus == QueuedJob::STATUS_COMPLETE) {
            if (file_exists($this->getExportPath($id))) {
                $data->DownloadLink = $gridField->Link('/export_download/' . $job->Signature);
            } else {
                $data->ErrorMessage = _t(
                    'SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.ERROR_REMOVED',
                    'This export has already been downloaded. ' .
                    'For security reasons each export can only be downloaded once.'
                );
            }
        } elseif ($job->JobStatus == QueuedJob::STATUS_BROKEN) {
            $data->ErrorMessage = _t(
                __CLASS__.'.ERROR_GENERAL',
                'Sorry, but there was an error exporting the CSV'
            );
        } elseif ($job->JobStatus == QueuedJob::STATUS_CANCELLED) {
            $data->ErrorMessage = _t(
                'SilverStripe\\GridfieldQueuedExport\\GridFieldQueuedExportButton.CANCELLED',
                'This export job was cancelled'
            );
        } else {
            $data->Count = $job->StepsProcessed;
            $data->Total = $job->TotalSteps;
        }

        Requirements::add_i18n_javascript('silverstripe/gridfieldqueuedexport:client/lang');
        Requirements::javascript('silverstripe/gridfieldqueuedexport:client/GridFieldQueuedExportButton.js');
        Requirements::css('silverstripe/gridfieldqueuedexport:client/GridFieldQueuedExportButton.css');

        $return = $data->renderWith(GridFieldQueuedExportButton::class);

        if ($request->isAjax()) {
            return $return;
        } else {
            return $controller->customise(['Content' => $return]);
        }
    }

    /**
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function downloadExport($gridField, $request = null)
    {
        $id = $request->param('ID');
        $job = QueuedJobDescriptor::get()->filter('Signature', $id)->first();

        if ((int)$job->RunAsID !== Security::getCurrentUser()->ID) {
            return Security::permissionFailure();
        }

        $now = Date("d-m-Y-H-i");
        $servedName = "export-$now.csv";

        $path = $this->getExportPath($id);
        $content = file_get_contents($path);

        unlink($path);
        rmdir(dirname($path));

        $response = HTTPRequest::send_file($content, $servedName, 'text/csv');
        $response->addHeader('Set-Cookie', 'downloaded_' . $id . '=true; Path=/');
        $response->output();
        exit;
    }

    /**
     * @return array
     */
    public function getExportColumns()
    {
        return $this->exportColumns;
    }

    /**
     * @param array
     * @return $this
     */
    public function setExportColumns($cols)
    {
        $this->exportColumns = $cols;
        return $this;
    }

    /**
     * @return string
     */
    public function getCsvSeparator()
    {
        return $this->csvSeparator;
    }

    /**
     * @param string
     * @return $this
     */
    public function setCsvSeparator($separator)
    {
        $this->csvSeparator = $separator;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCsvHasHeader()
    {
        return $this->csvHasHeader;
    }

    /**
     * @param boolean
     * @return $this
     */
    public function setCsvHasHeader($bool)
    {
        $this->csvHasHeader = $bool;
        return $this;
    }
}
