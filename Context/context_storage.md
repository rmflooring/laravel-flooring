# Storage System Context — Floor Manager
Updated: 2026-04-02

---

## Overview

File storage uses the Laravel `public` disk backed by an NFS-mounted WD My Cloud NAS, with an automatic OneDrive mirror for redundancy. The app sees everything as a plain local disk — no special driver needed.

---

## Primary Storage — NAS (NFS)

| Detail | Value |
|---|---|
| NAS device | WD My Cloud (`RMF-NAS1`) |
| NAS IP | `192.168.1.143` |
| NFS share | `192.168.1.143:/nfs/app_storage` |
| Mount point | `/mnt/nas_storage` on `rmserver2` |
| NAS real path | `/mnt/HD/HD_a2/app_storage` |
| Laravel symlink | `storage/app/public` → `/mnt/nas_storage` |
| fstab entry | `192.168.1.143:/nfs/app_storage  /mnt/nas_storage  nfs  defaults,_netdev  0  0` |
| Permissions | `777` on NAS side (root squash cannot be disabled via WD My Cloud UI — must SSH into NAS to chmod) |

### How it works
Laravel writes to `storage/app/public/` as normal. That path is a symlink to the NFS mount so all files land on the NAS transparently. Laravel driver stays as `local` / `public` disk — no app config change needed.

---

## Folder Naming Convention

All opportunity files are stored under `opportunities/{folder}/` where folder is:

| Condition | Folder name |
|---|---|
| Has job site name + job_no | `{JobSiteName} - {job_no}` e.g. `Sandra_Cokinass - 26-0001` |
| Has job site name, no job_no | `{JobSiteName} - {opportunity_id}` |
| No job site name | `opportunity-{opportunity_id}` |

- Spaces → underscores, invalid filesystem chars stripped (`/ \ : * ? " < > |`)
- `job_no` is free-text entered by staff — no enforced format
- Method: `Opportunity::storageFolderName()` in `app/Models/Opportunity.php`

### Migration command
```
php artisan app:migrate-opportunity-folders [--dry-run]
```
Renames existing folders on disk and updates `opportunity_documents.path` + `thumbnail_path` in DB.

---

## OneDrive Mirror (Backup)

Every file saved to primary storage is automatically mirrored to Richard's OneDrive (`richard@rmflooring.ca`) in the background via a queued job.

### Architecture
- **Service**: `app/Services/GraphOneDriveService.php`
  - Uses app-level Graph API token (same Azure registration as mail/calendar)
  - Simple upload for files ≤4MB, chunked upload session for larger files
  - OneDrive path: `FloorManager/{same relative path as primary disk}`
- **Job**: `app/Jobs/MirrorFileToOneDrive.php`
  - Queued, 3 retries, 120s timeout
  - Dispatched automatically from `OpportunityDocument::booted()` on `created` event
- **Queue worker**: `laravel-queue.service` systemd service on `rmserver2`
  - `sudo systemctl status laravel-queue`
  - Runs as `www-data`, `queue:work --sleep=3 --tries=3 --max-time=3600`

### Important
- App **always reads from NAS** — OneDrive is write-only backup, never read
- If OneDrive mirror fails, user sees no error — failure is logged only
- Azure app requires `Files.ReadWrite.All` application permission (admin consented ✓)

### Mirror existing files command
```
php artisan app:mirror-to-onedrive [--dry-run]
```
Queues mirror jobs for all existing `opportunity_documents` records.

---

## NAS Health Monitoring

### Command
`app/Console/Commands/CheckNasHealth.php` — `php artisan nas:check-health`
- Writes + reads a test file (`.nas-health-check.txt`) on the `public` disk
- Stores result in `app_settings`: `nas_status` (`online`/`offline`), `nas_last_checked`
- Runs every 5 minutes via Laravel scheduler

### Alerts
- Status changes `online → offline`: sends email to `richard@rmflooring.ca` via Track 1 Graph mail
- Status changes `offline → online`: sends "all clear" email

### UI
- **Main layout** (`resources/views/layouts/app.blade.php`): red banner shown to all logged-in users when `nas_status = offline`
- **Storage settings page**: green/red/unknown status indicator with last-checked timestamp

---

## Admin Storage Settings Page

- Route: `admin.settings.storage.*`
- Controller: `app/Http/Controllers/Admin/StorageSettingsController.php`
- View: `resources/views/admin/settings/storage.blade.php`
- Supported drivers: Local, S3/cloud, SFTP (switchable via UI)
- Detects NAS symlink — shows purple banner when `storage/app/public` is a symlink
- Shows NAS health status (green/red/unknown) with last checked time
- **Bug fixed**: Test Connection form was nested inside Save form — now correctly outside

### DocumentStorageService
`app/Services/DocumentStorageService::disk()` — returns active disk name.
- S3/SFTP: registers a `documents` disk at runtime from DB settings
- Local: returns `'public'`

---

## Upload Path — Where Files Are Stored

All uploads go through one of:
- `app/Http/Controllers/OpportunityDocumentController.php` — main upload handler
- `app/Http/Controllers/Mobile/RfmController.php` — mobile RFM photo uploads
- `app/Http/Controllers/Mobile/WorkOrderController.php` — mobile WO photo uploads

All use `DocumentStorageService::disk()` and `$opportunity->storageFolderName()`.

Thumbnails are generated inline for images (≤600px wide, JPEG 80%) and stored in the same folder as the original with a `thumb_` prefix.

`app/Console/Commands/GenerateMediaThumbnails.php` — regenerates missing thumbnails. Uses `dirname($doc->path)` to derive folder (not `opportunity_id`) so it works correctly with new naming.

---

## PHP Upload Limits (rmserver2)

- Config: `/etc/php/8.3/fpm/php.ini`
- `upload_max_filesize` and `post_max_size` — increase if staff report "post data too large" errors
- Nginx: `client_max_body_size` in `/etc/nginx/sites-available/myapp` must match
- Restart after changes: `sudo systemctl restart php8.3-fpm nginx`
