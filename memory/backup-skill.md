---
name: Backup DB skill
description: Cómo crear backups de la BD de cokalbarunning
type: reference
---

Script: `./scripts/backup-db.sh` — hace backup de `cokalba_running` en media-tools (`~/apps/cokalbarunning/backups/`) y limpia los de más de 7 días.

Restauración: limpiar schema público con postgres, luego `gunzip -c backup.sql.gz | docker exec -i shared-postgres-db psql -U cokalba -d cokalba_running`.
