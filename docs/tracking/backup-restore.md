# Backup y Restauración de Base de Datos

## Datos de conexión

| Campo | Valor |
|-------|-------|
| Motor | PostgreSQL 16 |
| Host | `shared-postgres-db` (interno Docker) |
| Puerto | 5432 |
| Usuario | `cokalba` |
| Base de datos | `cokalba_running` |
| Contenedor | `shared-postgres-db` |

---

## 1. Backup (servidor origen)

### Opción A — pg_dump dentro del contenedor (recomendado)

```bash
# Sin comprimir
docker exec shared-postgres-db pg_dump -U cokalba cokalba_running > backup_cokalbarunning_$(date +%Y%m%d_%H%M%S).sql

# Con compresión gzip
docker exec shared-postgres-db pg_dump -U cokalba cokalba_running | gzip > backup_cokalbarunning_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Opción B — pg_dump desde el host (si PostgreSQL acepta conexiones externas)

```bash
pg_dump -h <IP_DEL_SERVIDOR> -U cokalba -d cokalba_running -f backup_cokalbarunning_$(date +%Y%m%d_%H%M%S).sql
```

---

## 2. Transferir el backup al nuevo servidor

```bash
scp backup_cokalbarunning_*.sql.gz usuario@nuevo-servidor:/ruta/destino/
```

---

## 3. Restauración (servidor destino)

### Requisitos previos

- PostgreSQL 16 instalado y corriendo
- Tener credenciales de superusuario o del rol `cokalba`

### Paso 1 — Crear el usuario y la base de datos

```bash
# Crear el rol (si no existe)
psql -U postgres -c "CREATE ROLE cokalba WITH LOGIN PASSWORD 'secret';"

# Crear la base de datos
psql -U postgres -c "CREATE DATABASE cokalba_running OWNER cokalba;"
```

### Paso 2 — Restaurar el dump

```bash
# Si está sin comprimir
psql -U cokalba -d cokalba_running -f backup_cokalbarunning_YYYYMMDD_HHMMSS.sql

# Si está comprimido con gzip
gunzip -c backup_cokalbarunning_YYYYMMDD_HHMMSS.sql.gz | psql -U cokalba -d cokalba_running
```

### Paso 3 — Verificar

```bash
psql -U cokalba -d cokalba_running -c "\dt"
```

---

## 4. Programar backups automáticos (cron)

Ejemplo para backup diario a las 3:00 AM:

```bash
# Editar crontab
crontab -e

# Añadir línea (guarda en /backups/ con retención de 7 días)
0 3 * * * docker exec shared-postgres-db pg_dump -U cokalba cokalba_running | gzip > /backups/cokalbarunning_$(date +\%Y\%m\%d).sql.gz && find /backups/ -name "cokalbarunning_*.sql.gz" -mtime +7 -delete
```

---

## Notas

- Si la contraseña de producción está en variable `COKALBA_DB_PASS`, usar `-U cokalba` y configurar el archivo `~/.pgpass` para evitar que pida contraseña interactivamente:

  ```
  # ~/.pgpass
  shared-postgres-db:5432:cokalba_running:cokalba:<password>
  ```

- Para backups muy grandes, considerar `pg_dump -Fc` (formato personalizado) que permite restauración paralela con `pg_restore -j`.
