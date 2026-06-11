#!/bin/bash
set -euo pipefail

cd /var/www/teboos

BACKUP_DIR="${BACKUP_DIR:-/var/backups/teboos}"
KEEP="${KEEP:-14}"

mkdir -p "$BACKUP_DIR"

DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"'"'" | tr -d '\r')
DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d= -f2- | tr -d '"'"'" | tr -d '\r')
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"'"'" | tr -d '\r')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"'"'" | tr -d '\r')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"'"'" | tr -d '\r')

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

STAMP=$(date +%Y%m%d-%H%M%S)
FILE="$BACKUP_DIR/${DB_DATABASE}-${STAMP}.sql"

echo "==> Backing up database to $FILE"
mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" \
  --single-transaction --quick "$DB_DATABASE" > "$FILE"
gzip -f "$FILE"

echo "==> Backup saved: ${FILE}.gz"
ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +$((KEEP + 1)) | xargs -r rm -f
echo "==> Keeping latest $KEEP backups"
