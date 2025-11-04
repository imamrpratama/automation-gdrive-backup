# 🚀 Google Drive Backup for Laravel

A production-ready **Laravel console command** that automates file backups to **Google Drive**, complete with **email notifications**, **retry logic**, and **comprehensive logging**.  
Designed for reliability, scalability, and hands-free maintenance — perfect for Laravel-based systems that require secure cloud data storage.

---

## ✨ Key Features

-   📤 **Automated Google Drive Backup** — Seamlessly upload files or folders to Google Drive.
-   📧 **Email Notifications** — Receive detailed success/failure reports after each backup.
-   🔁 **Smart Retry Mechanism** — Automatic reattempts with exponential backoff for failed uploads.
-   📊 **Progress Tracking** — Real-time progress bar for transparent backup operations.
-   🗂️ **Preserved Directory Structure** — Maintains the original file hierarchy.
-   💾 **Memory-Efficient Streaming** — Optimized for large file transfers.
-   ⏱️ **Timestamped Backups** — Organizes backups using date-based folder naming.
-   ⚡ **Skip Existing Files** — Avoids redundant uploads for unchanged files.
-   🧪 **Comprehensive Test Suite** — Validates setup and backup flow.
-   📝 **Detailed Logging** — Full traceability of all backup activities.

---

## 🧩 Requirements

-   PHP **8.0+**
-   Laravel **11.x+**
-   Google Drive API credentials
-   Composer

---

## 🚀 Installation

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/imamrpratama/automation-gdrive-backup.git
cd gdrive-backup
```

### 2️⃣ Install Dependencies

```bash
composer install
```

### 3️⃣ Configure Environment

Copy `.env.example` to `.env` and set up the following:

```env
APP_NAME="Google Drive Backup"
APP_TIMEZONE=Asia/Jakarta

GOOGLE_DRIVE_CLIENT_ID=your-client-id
GOOGLE_DRIVE_CLIENT_SECRET=your-client-secret
GOOGLE_DRIVE_REFRESH_TOKEN=your-refresh-token
GOOGLE_DRIVE_FOLDER_ID=your-folder-id

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

BACKUP_NOTIFICATION_EMAIL=admin@example.com
```

### 4️⃣ Enable Google Drive API

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create/select a project
3. Enable **Google Drive API**
4. Create **OAuth 2.0 credentials**
5. Generate and use your **refresh token**
6. Add them to `.env`

### 5️⃣ Configure Filesystem

Add the Google Drive disk to `config/filesystems.php`:

```php
'google' => [
    'driver' => 'google',
    'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folder' => env('GOOGLE_DRIVE_FOLDER_ID'),
],
```

---

## 📖 Usage

### Run a Basic Backup

```bash
php artisan backup:files
```

### Available Options

```bash
php artisan backup:files --timestamped --skip-existing --max-retries=5
```

| Option            | Description                       | Default |
| :---------------- | :-------------------------------- | :------ |
| `--timestamped`   | Create date-based backup folders  | `false` |
| `--skip-existing` | Skip files already uploaded       | `false` |
| `--max-retries`   | Retry attempts for failed uploads | `3`     |
| `--no-email`      | Disable email report              | `false` |

---

## 🧪 Testing

Ensure all components are properly configured:

```bash
php artisan test --filter=BackupTest --verbose
```

Includes validation for:

-   Google Drive API connection
-   Email notification setup
-   Backup directory existence
-   Command registration
-   Timezone and configuration integrity

---

## ⏰ Scheduled Automation

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:files')->dailyAt('02:00');
}
```

Cron job example:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📧 Email Notifications

Each backup triggers an automatic email report that includes:

-   Status (Success/Failure)
-   Number of files uploaded, skipped, or failed
-   Total file size and duration
-   Timestamp (based on app timezone)
-   Error details (if any)

---

## 📁 Project Structure

```
app/Console/Commands/BackupFileCommand.php
resources/views/emails/backup-notification.blade.php
storage/app/gdrive-backup/
tests/Feature/BackupTest.php
config/filesystems.php
config/backup.php
```

---

## 🧠 Logging

All backup activities are logged to `storage/logs/laravel.log`:

```log
[2025-11-02 14:38:55] local.INFO: Backup successful: test.txt
[2025-11-02 14:38:55] local.INFO: Notification email sent successfully.
```

---

## 🐛 Troubleshooting

### Google Drive Connection Issues

-   Ensure credentials in `.env` are correct
-   Verify Drive API is enabled
-   Refresh token is valid and folder ID exists

### Email Notification Fails

-   Confirm `BACKUP_NOTIFICATION_EMAIL` is set
-   Use Gmail App Password for SMTP
-   Check logs for detailed error messages

### File Upload Errors

-   Verify files in `storage/app/gdrive-backup`
-   Check file permissions and available Drive space

---

## 🤝 Contributing

Contributions are welcome!  
To contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes
4. Push and open a Pull Request

---

## 👤 Author

**Imam Rizky Pratama**

🔗 [GitHub](https://github.com/imamrpratama)

---

## 📄 License

Released under the [MIT License](LICENSE).
