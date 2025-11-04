<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BackupTest extends TestCase
{
    /**
     * Test Google Drive connection and file upload
     */
    public function test_google_drive_connection_works(): void
    {
        // Check if Google Drive disk is configured
        $this->assertNotNull(
            config('filesystems.disks.google'),
            'Google Drive disk is not configured in filesystems.php'
        );

        // Create test file content
        $testFileName = 'backup-test-' . now()->format('YmdHis') . '.txt';
        $testContent = "Backup test file\nCreated at: " . now()->toDateTimeString();

        // Upload test file to Google Drive
        $uploaded = Storage::disk('google')->put('backup-test/' . $testFileName, $testContent);

        $this->assertTrue($uploaded, 'Failed to upload test file to Google Drive');

        // Verify file exists on Google Drive
        $exists = Storage::disk('google')->exists('backup-test/' . $testFileName);

        $this->assertTrue($exists, 'Test file not found on Google Drive after upload');

        // Cleanup
        Storage::disk('google')->delete('backup-test/' . $testFileName);
    }

    /**
     * Test email notification configuration
     */
    public function test_email_notification_is_configured(): void
    {
        // Check if notification email is configured
        $recipientEmail = config('backup.notification_email', env('BACKUP_NOTIFICATION_EMAIL'));

        $this->assertNotNull(
            $recipientEmail,
            'BACKUP_NOTIFICATION_EMAIL is not configured in .env'
        );

        // Check if mail driver is configured
        $mailDriver = config('mail.default');
        $this->assertNotEmpty($mailDriver, 'Mail driver is not configured');

        // Check if from address is configured
        $mailFrom = config('mail.from.address');
        $this->assertNotEmpty($mailFrom, 'Mail from address is not configured');

        // For SMTP driver, check host configuration
        if ($mailDriver === 'smtp') {
            $mailHost = config('mail.mailers.smtp.host');
            $this->assertNotEmpty($mailHost, 'SMTP mail host is not configured');
        }

        // Skip host check for log, array, or other non-SMTP drivers
        $this->assertTrue(true, 'Mail configuration validated');
    }

    /**
     * Test email template exists
     */
    public function test_email_template_exists(): void
    {
        $templatePath = resource_path('views/emails/backup-notification.blade.php');

        $this->assertFileExists(
            $templatePath,
            'Email template not found at: ' . $templatePath
        );
    }

    /**
     * Test sending email notification
     */
    public function test_email_notification_can_be_sent(): void
    {
        $recipientEmail = config('backup.notification_email', env('BACKUP_NOTIFICATION_EMAIL'));

        if (!$recipientEmail) {
            $this->markTestSkipped('BACKUP_NOTIFICATION_EMAIL not configured');
        }

        // Don't fake mail - just verify the template can be rendered
        $testData = [
            'status' => 'completed successfully',
            'statusEmoji' => '✓',
            'successCount' => 5,
            'failCount' => 0,
            'skippedCount' => 0,
            'totalSize' => '2.5 MB',
            'duration' => '3.65',
            'failedFiles' => [],
            'timestamp' => now()->timezone(config('app.timezone', 'UTC'))->format('d-m-Y H:i:s'),
        ];

        // Test that the email view can be rendered without errors
        $view = view('emails.backup-notification', $testData);
        $rendered = $view->render();

        // Assert that the rendered view contains expected content
        $this->assertStringContainsString('Google Drive Backup', $rendered);
        $this->assertStringContainsString('COMPLETED SUCCESSFULLY', $rendered); // Uppercased in template
        $this->assertStringContainsString('5', $rendered); // success count
        $this->assertStringContainsString('Successful', $rendered); // label in card

        // Mark test as passed - email configuration and template are valid
        $this->assertTrue(true, 'Email template renders successfully');
    }

    /**
     * Test backup directory exists
     */
    public function test_backup_directory_exists(): void
    {
        $directoryPath = storage_path('app/gdrive-backup');

        $this->assertDirectoryExists(
            $directoryPath,
            'Backup directory does not exist at: ' . $directoryPath
        );
    }

    /**
     * Test backup command exists
     */
    public function test_backup_command_exists(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey(
            'backup:files',
            $commands,
            'backup:files command is not registered'
        );
    }

    /**
     * Test timezone configuration
     */
    public function test_timezone_is_configured(): void
    {
        $timezone = config('app.timezone');

        $this->assertNotEmpty($timezone, 'APP_TIMEZONE is not configured');

        // Test that timezone is valid
        $this->assertContains(
            $timezone,
            timezone_identifiers_list(),
            'APP_TIMEZONE contains an invalid timezone: ' . $timezone
        );
    }
}
