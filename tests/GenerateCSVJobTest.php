<?php

namespace SilverStripe\GridFieldQueuedExport\Tests;

use SilverStripe\Assets\Filesystem;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\GridfieldQueuedExport\Jobs\GenerateCSVJob;

class GenerateCSVJobTest extends SapphireTest
{

    protected static $fixture_file = 'GenerateCSVJobTest.yml';

    protected static $extra_dataobjects = [GenerateCSVJobTestRecord::class];

    protected static $extra_controllers = [GenerateCSVJobTestController::class];

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()
            ->merge(Director::class, 'rules', [
                'jobtest//$Action/$ID/$OtherID' => GenerateCSVJobTestController::class
            ])
            ->set(GenerateCSVJob::class, 'sync_sleep_seconds', 0);
    }

    protected $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            Filesystem::removeFolder(dirname($path));
        }
        parent::tearDown();
    }

    public function testGenerateExport()
    {
        // Build session
        $this->logInWithPermission('ADMIN');
        $sessionData = Controller::curr()->getRequest()->getSession()->getAll();

        // Build controller
        $controller = new GenerateCSVJobTestController();
        $form = $controller->Form();
        $gridfield = $form->Fields()->fieldByName('MyGridfield');

        // Build job
        $job = $this->createJob($gridfield, $sessionData);
        $path = sprintf('%1$s/.exports/%2$s/%2$s.csv', ASSETS_PATH, $job->getSignature());
        $this->paths[] = $path; // Mark for cleanup later

        // Test that the job runs
        $this->assertFileDoesNotExist($path);
        $job->setup();
        $job->process();
        $job->afterComplete();
        $this->assertFileExists($path);

        // Test that the output matches the expected
        $expected = [
            '"Record 1","<p>""Record 1"" Body</p>","2015-01-01 23:34:01"',
            '"Record 2","<p>""Record 2"" Body</p>","2015-01-02 23:34:01"',
            '"Record 3","<p>""Record 3"" Body</p>","2015-01-03 23:34:01"',
            '',
        ];
        $actual = file_get_contents($path);
        // Note: strtolower() is for case insensitive comparison, since field label casing changed in SS 4.3
        $this->assertStringContainsString('title,content,"publish on"', strtolower($actual));
        $this->assertStringContainsString(implode("\r\n", $expected), $actual);
    }

    public function testGenerateExportOverMultipleSteps()
    {
        Config::modify()->set(GenerateCSVJob::class, 'chunk_size', 1);

        // Build session
        $this->logInWithPermission('ADMIN');
        $sessionData = Controller::curr()->getRequest()->getSession()->getAll();

        // Build controller
        $controller = new GenerateCSVJobTestController();
        $form = $controller->Form();
        /** @var GridField $gridfield */
        $gridfield = $form->Fields()->fieldByName('MyGridfield');

        // Build job
        $job = $this->createJob($gridfield, $sessionData);
        $path = sprintf('%1$s/.exports/%2$s/%2$s.csv', ASSETS_PATH, $job->getSignature());
        $this->paths[] = $path; // Mark for cleanup later

        // Test that the job runs
        $this->assertFileDoesNotExist($path);
        $count = 0;
        while (!$job->jobFinished()) {
            ++$count;
            if ($job->currentStep) {
                $job->prepareForRestart();
            } else {
                $job->setup();
            }
            $job->process();
        }
        $job->afterComplete();
        $this->assertFileExists($path);
        $this->assertEquals(3, $count);

        // Test that the output matches the expected
        $expected = [
            '"Record 1","<p>""Record 1"" Body</p>","2015-01-01 23:34:01"',
            '"Record 2","<p>""Record 2"" Body</p>","2015-01-02 23:34:01"',
            '"Record 3","<p>""Record 3"" Body</p>","2015-01-03 23:34:01"',
            '',
        ];
        $actual = file_get_contents($path);
        // Note: strtolower() is for case insensitive comparison, since field label casing changed in SS 4.3
        $this->assertStringContainsString('title,content,"publish on"', strtolower($actual));
        $this->assertStringContainsString(implode("\r\n", $expected), $actual);
    }

    /**
     * Rough copy of GridFieldQueuedExportButton::startExport
     *
     * @param GridField $gridField
     * @param array $session
     * @return GenerateCSVJob
     */
    protected function createJob($gridField, $session)
    {
        $job = new GenerateCSVJob();
        $job->setGridField($gridField);
        $job->setSession($session);
        $job->setSeparator(',');
        $job->setIncludeHeader(true);
        return $job;
    }
}
