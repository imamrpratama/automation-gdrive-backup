<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use File;
use Exception;

class BackupFileCommand extends Command
{
    protected $signature = 'backup:files
                            {--timestamped : Create timestamped backup folders}
                            {--skip-existing : Skip files that already exist on Google Drive}
                            {--max-retries=3 : Maximum retry attempts for failed uploads}
                            {--no-email : Disable email notification}';

    protected $description = 'Backup all files in the gdrive-backup directory to Google Drive';

    private $successCount = 0;
    private $failCount = 0;
    private $skippedCount = 0;
    private $totalSize = 0;
    private $failedFiles = [];

    public function handle()
    {
        $startTime = microtime(true);

        $this->info('Starting Google Drive backup...');
        $this->newLine();

        $directoryPath = storage_path('app/gdrive-backup');

        if (!is_dir($directoryPath)) {
            $this->error("Directory does not exist at path: $directoryPath");
            Log::error("Backup failed: Directory not found at $directoryPath");
            return Command::FAILURE;
        }

        $files = File::allFiles($directoryPath);

        if (empty($files)) {
            $this->info('No files found to backup.');
            return Command::SUCCESS;
        }

        $this->info("Found " . count($files) . " file(s) to backup.");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        $baseBackupPath = $this->getBaseBackupPath();

        foreach ($files as $file) {
            $this->backupFile($file, $directoryPath, $baseBackupPath);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displaySummary($startTime);

        // Send email notification
        if (!$this->option('no-email')) {
            $this->sendEmailNotification($startTime);
        }

        return $this->failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function backupFile($file, $directoryPath, $baseBackupPath)
    {
        $filePath = $file->getRealPath();
        // Use getRelativePathname() to get the correct relative path
        $relativePath = $file->getRelativePathname();
        $remotePath = "$baseBackupPath/$relativePath";

        // Skip existing files if option is enabled
        if ($this->option('skip-existing') && Storage::disk('google')->exists($remotePath)) {
            $this->skippedCount++;
            return;
        }

        $maxRetries = (int) $this->option('max-retries');
        $attempt = 0;
        $uploaded = false;

        while ($attempt < $maxRetries && !$uploaded) {
            try {
                $attempt++;

                // Use streaming for better memory efficiency
                $stream = fopen($filePath, 'r');

                if (!$stream) {
                    throw new Exception("Failed to open file stream");
                }

                $uploaded = Storage::disk('google')->writeStream($remotePath, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                if ($uploaded) {
                    $this->successCount++;
                    $this->totalSize += $file->getSize();

                    Log::info("Backup successful: $relativePath");
                } else {
                    throw new Exception("Upload returned false");
                }

            } catch (Exception $e) {
                if (isset($stream) && is_resource($stream)) {
                    fclose($stream);
                }

                if ($attempt >= $maxRetries) {
                    $this->failCount++;
                    $this->failedFiles[] = [
                        'file' => $relativePath,
                        'error' => $e->getMessage()
                    ];

                    Log::error("Backup failed after $maxRetries attempts: $relativePath - " . $e->getMessage());
                } else {
                    // Wait before retry (exponential backoff)
                    sleep(pow(2, $attempt - 1));
                }
            }
        }
    }

    private function getBaseBackupPath()
    {
        if ($this->option('timestamped')) {
            $timestamp = now()->format('Y-m-d_His');
            return "gbackup/$timestamp";
        }

        return "gbackup";
    }

    private function displaySummary($startTime)
    {
        $duration = round(microtime(true) - $startTime, 2);

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  BACKUP SUMMARY');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->line("  <fg=green>✓</> Successful:  <fg=green>{$this->successCount}</>");

        if ($this->skippedCount > 0) {
            $this->line("  <fg=yellow>⊘</> Skipped:     <fg=yellow>{$this->skippedCount}</>");
        }

        if ($this->failCount > 0) {
            $this->line("  <fg=red>✗</> Failed:      <fg=red>{$this->failCount}</>");
        }

        $this->line("  <fg=cyan>◉</> Total size:   <fg=cyan>{$this->formatBytes($this->totalSize)}</>");
        $this->line("  <fg=cyan>⏱</> Duration:     <fg=cyan>{$duration}s</>");

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Display failed files if any
        if (!empty($this->failedFiles)) {
            $this->newLine();
            $this->error('Failed Files:');
            foreach ($this->failedFiles as $failed) {
                $this->line("  • {$failed['file']}");
                $this->line("    Error: {$failed['error']}");
            }
        }

        $this->newLine();

        if ($this->successCount > 0) {
            $this->info('✓ Backup completed successfully!');
        } elseif ($this->failCount > 0) {
            $this->error('✗ Backup completed with errors.');
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function sendEmailNotification($startTime)
    {
        $this->info('Preparing to send email notification...');
        Log::info('Starting email notification process');

        try {
            $duration = round(microtime(true) - $startTime, 2);
            $status = $this->failCount > 0 ? 'completed with errors' : 'completed successfully';
            $statusEmoji = $this->failCount > 0 ? '⚠️' : '✓';

            $recipientEmail = config('backup.notification_email', env('BACKUP_NOTIFICATION_EMAIL'));

            Log::info('Email configuration check', [
                'recipient' => $recipientEmail,
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_from' => config('mail.from.address'),
            ]);

            if (!$recipientEmail) {
                $this->warn('No notification email configured. Skipping email notification.');
                $this->warn('Please set BACKUP_NOTIFICATION_EMAIL in your .env file');
                Log::warning('Backup email notification skipped: No recipient email configured');
                return;
            }

            $this->info("Sending email to: $recipientEmail");

            $emailSent = false;
            $emailError = null;

            try {
                Mail::send('emails.backup-notification', [
                    'status' => $status,
                    'statusEmoji' => $statusEmoji,
                    'successCount' => $this->successCount,
                    'failCount' => $this->failCount,
                    'skippedCount' => $this->skippedCount,
                    'totalSize' => $this->formatBytes($this->totalSize),
                    'duration' => $duration,
                    'failedFiles' => $this->failedFiles,
                    'timestamp' => now()->timezone(config('app.timezone', 'UTC'))->format('d-m-Y H:i:s'),
                ], function ($message) use ($status, $statusEmoji, $recipientEmail) {
                    $message->to($recipientEmail)
                        ->subject("$statusEmoji Google Drive Backup " . ucfirst($status));
                });

                $emailSent = true;
            } catch (Exception $mailException) {
                $emailError = $mailException->getMessage();
            }

            if ($emailSent) {
                $this->info('✓ Email notification sent successfully.');
                Log::info('Backup email notification sent successfully', [
                    'recipient' => $recipientEmail,
                    'status' => $status,
                    'success_count' => $this->successCount,
                    'fail_count' => $this->failCount,
                ]);
            } else {
                $this->error('✗ Email failed to send: ' . $emailError);
                Log::error('Backup email notification failed to send', [
                    'recipient' => $recipientEmail,
                    'error' => $emailError
                ]);
            }

        } catch (Exception $e) {
            $this->error('✗ Failed to send email notification: ' . $e->getMessage());
            Log::error('Failed to send backup email notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check mail configuration
            $this->warn('Mail Configuration:');
            $this->warn('  Driver: ' . config('mail.default'));
            $this->warn('  Host: ' . config('mail.mailers.smtp.host', 'Not set'));
            $this->warn('  Port: ' . config('mail.mailers.smtp.port', 'Not set'));
            $this->warn('  From: ' . config('mail.from.address', 'Not set'));
        }
    }
}
