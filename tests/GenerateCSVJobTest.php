<?php

class GenerateCSVJobTest extends SapphireTest {

    protected static $fixture_file = 'GenerateCSVJobTest.yml';

    protected $extraDataObjects = array('GenerateCSVJobTest_Record');

    public function setUp() {
        parent::setUp();
        Config::inst()->update('Director', 'rules', array(
            'jobtest//$Action/$ID/$OtherID' => 'GenerateCSVJobTest_Controller'
        ));
    }

    protected $paths = array();

    public function tearDown() {
        foreach($this->paths as $path) {
            Filesystem::removeFolder(dirname($path));
        }
        parent::tearDown();
    }

    public function testGenerateExport() {
        // Build session
        $memberID = $this->logInWithPermission('ADMIN');
        $session = array('loggedInAs' => $memberID);

        // Build controller
        $controller = new GenerateCSVJobTest_Controller();
        $form = $controller->Form();
        $gridfield = $form->Fields()->fieldByName('MyGridfield');

        // Build job
        $job = $this->createJob($gridfield, $session);
        $path = sprintf('%1$s/.exports/%2$s/%2$s.csv', ASSETS_PATH, $job->getSignature());
        $this->paths[] = $path; // Mark for cleanup later

        // Test that the job runs
        $this->assertFileNotExists($path);
        $job->process();
        $this->assertFileExists($path);

        // Test that the output matches the expected
        $expected = <<<EOS
"Title","Content","Publish On"
"Record 1","<p>""Record 1"" Body</p>","2015-01-01 23:34:01"
"Record 2","<p>""Record 2"" Body</p>","2015-01-02 23:34:01"
"Record 3","<p>""Record 3"" Body</p>","2015-01-03 23:34:01"

EOS;
        $actual = file_get_contents($path);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Rough copy of GridFieldQueuedExportButton::startExport
     *
     * @param GridField $gridField
     * @param array $session
     * @return GenerateCSVJob
     */
    protected function createJob($gridField, $session) {
        $job = new GenerateCSVJob();
        $job->setGridField($gridField);
        $job->setSession($session);
        $job->setSeparator(',');
        $job->setIncludeHeader(true);
        return $job;
    }
}


class GenerateCSVJobTest_Record extends DataObject implements TestOnly {

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

class GenerateCSVJobTest_Controller extends Controller implements TestOnly {
    private static $allowed_actions = array('Form');

    public function Link() {
        return 'jobtest/';
    }

    public function Form() {
        // Get records
        $records = GenerateCSVJobTest_Record::get();

        // Set config
        $config = GridFieldConfig_RecordEditor::create();
        $config->removeComponentsByType('GridFieldExportButton');
        $config->addComponent(new GridFieldQueuedExportButton('buttons-after-left'));
        $fields = new GridField('MyGridfield', 'My Records', $records, $config);
        return new Form($this, 'Form', new FieldList($fields), new FieldList());
    }
}
