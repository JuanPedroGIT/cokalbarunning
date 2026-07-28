---
name: Servidores
description: Servidores del proyecto: media-tools (producción) y server (casa)
type: reference
---

- **media-tools**: producción en Oracle Cloud, IP 82.70.92.158, usuario ubuntu, clave ~/.ssh/ssh-key-oracle.key. SSH alias: `media-tools`. Corre infra (postgres, nginx, redis, cloudflared) y cokalbarunning.
- **server**: local/casa, IP 192.168.1.52, usuario jpvicente. SSH alias: `server`.
- BD PostgreSQL 16 en contenedor `shared-postgres-db`, usuario `cokalba`, BD `cokalba_running`.
