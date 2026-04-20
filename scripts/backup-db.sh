#!/bin/bash
# =============================================================================
# Floor Manager — MariaDB Backup Script
# Runs on: rmserver2
# Destination: NFS-mounted NAS at /mnt/nas_storage/backups/db/
# Deploy to: /usr/local/bin/backup-db.sh
# Cron: see bottom of this file
# =============================================================================

set -euo pipefail

# --- Config ------------------------------------------------------------------
DB_NAME="fm_laravel"
DB_HOST="192.168.1.201"
DB_USER="root"
CREDENTIALS_FILE="/etc/mysql/backup.my.cnf"   # stores password securely
BACKUP_DIR="/mnt/nas_storage/backups/db"
RETAIN_DAYS=30
LOG_FILE="/var/log/fm-db-backup.log"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
FILENAME="${DB_NAME}_${TIMESTAMP}.sql.gz"

# --- Helpers -----------------------------------------------------------------
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

# --- Pre-flight checks -------------------------------------------------------
if ! mountpoint -q /mnt/nas_storage; then
    log "ERROR: NAS is not mounted at /mnt/nas_storage — aborting backup."
    exit 1
fi

mkdir -p "$BACKUP_DIR"

# --- Dump & compress ---------------------------------------------------------
log "Starting backup of '$DB_NAME' → $BACKUP_DIR/$FILENAME"

mysqldump \
    --defaults-file="$CREDENTIALS_FILE" \
    --user="$DB_USER" \
    --host="$DB_HOST" \
    --protocol=TCP \
    --single-transaction \
    --routines \
    --triggers \
    --hex-blob \
    --column-statistics=0 \
    "$DB_NAME" \
  | gzip -9 > "$BACKUP_DIR/$FILENAME"

SIZE=$(du -sh "$BACKUP_DIR/$FILENAME" | cut -f1)
log "Backup complete — $FILENAME ($SIZE)"

# --- Mirror to OneDrive ------------------------------------------------------
log "Uploading to OneDrive..."
if cd /var/www/myapp && php artisan backup:upload-db "$BACKUP_DIR/$FILENAME" >> "$LOG_FILE" 2>&1; then
    log "OneDrive upload complete."
else
    log "WARNING: OneDrive upload failed — backup is still safe on NAS."
fi

# --- Prune old backups -------------------------------------------------------
DELETED=$(find "$BACKUP_DIR" -name "${DB_NAME}_*.sql.gz" -mtime +${RETAIN_DAYS} -print -delete | wc -l)
if [ "$DELETED" -gt 0 ]; then
    log "Pruned $DELETED backup(s) older than ${RETAIN_DAYS} days."
fi

log "Done."

# =============================================================================
# SETUP INSTRUCTIONS (run once on rmserver2 as root)
# =============================================================================
#
# 1. Create the credentials file (keeps password out of the script):
#
#    sudo nano /etc/mysql/backup.my.cnf
#
#    Paste:
#      [mysqldump]
#      password=YOUR_DB_ROOT_PASSWORD
#
#    Lock it down:
#      sudo chmod 600 /etc/mysql/backup.my.cnf
#      sudo chown root:root /etc/mysql/backup.my.cnf
#
# 2. Deploy this script:
#
#    sudo cp scripts/backup-db.sh /usr/local/bin/backup-db.sh
#    sudo chmod +x /usr/local/bin/backup-db.sh
#
# 3. Create the log file:
#
#    sudo touch /var/log/fm-db-backup.log
#    sudo chmod 644 /var/log/fm-db-backup.log
#
# 4. Add the cron job (runs daily at 2:00 AM):
#
#    sudo crontab -e
#
#    Add this line:
#      0 2 * * * /usr/local/bin/backup-db.sh >> /var/log/fm-db-backup.log 2>&1
#
# 5. Test it manually first:
#
#    sudo /usr/local/bin/backup-db.sh
#
# 6. Verify the backup is readable:
#
#    gunzip -c /mnt/nas_storage/backups/db/fm_laravel_<timestamp>.sql.gz | head -20
#
# =============================================================================
