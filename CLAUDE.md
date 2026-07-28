# Cokalba Running

## Servidores

### media-tools (producción)
- **SSH:** `ssh media-tools` (configurado en `~/.ssh/config`)
  - Host: 82.70.92.158
  - User: ubuntu
  - Identity: ~/.ssh/ssh-key-oracle.key
- **Servicios:** infra (nginx, postgres, cloudflared, redis) + cokalbarunning

### server (local/casa)
- **SSH:** `ssh server` (configurado en `~/.ssh/config`)
  - Host: 192.168.1.52
  - User: jpvicente

## Infraestructura

La BD PostgreSQL 16 corre en el contenedor `shared-postgres-db` en la red Docker `shared-network`.
El proyecto `infra` (`../infra`) gestiona la infraestructura compartida.

### Conectarse a BD en media-tools
```bash
ssh media-tools "docker exec -i shared-postgres-db psql -U cokalba -d cokalba_running"
```

### Backup de BD (skill: backup-db)
```bash
./scripts/backup-db.sh
```
Esto hace backup en media-tools en `~/apps/cokalbarunning/backups/` y borra los de más de 7 días.

### Restaurar BD
```bash
# Limpiar schema
ssh media-tools "docker exec -i shared-postgres-db psql -U postgres -d cokalba_running -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO cokalba;'"

# Restaurar
ssh media-tools "gunzip -c ~/apps/cokalbarunning/backups/backup_cokalbarunning_XXXX.sql.gz | docker exec -i shared-postgres-db psql -U cokalba -d cokalba_running"
```
