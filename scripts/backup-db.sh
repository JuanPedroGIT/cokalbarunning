#!/bin/bash
# Backup de la BD de cokalbarunning en media-tools
# Uso: ./scripts/backup-db.sh
set -e

BACKUP_DIR="~/apps/cokalbarunning/backups"
RETENTION_DAYS=7

echo "=== Backup cokalba_running ==="
ssh media-tools "mkdir -p $BACKUP_DIR && docker exec shared-postgres-db pg_dump -U cokalba cokalba_running | gzip > $BACKUP_DIR/backup_cokalbarunning_\$(date +%Y%m%d_%H%M%S).sql.gz"

echo "=== Limpiando backups de más de $RETENTION_DAYS días ==="
ssh media-tools "find $BACKUP_DIR -name 'backup_cokalbarunning_*.sql.gz' -mtime +$RETENTION_DAYS -delete"

echo "=== Backups actuales ==="
ssh media-tools "ls -lh $BACKUP_DIR"
