# ESPECIFICACIONES.MD - Especificaciones Técnicas y de Infraestructura
## Proyecto: Plataforma Cokalba Running (Symfony Backend & Vue 3 Frontend)

---

## 1. Introducción y Objetivos

El objetivo de este proyecto es migrar y evolucionar la landing page de la **IX Edición de la Carrera Solidaria "Un Nuevo Impulso"** hacia una aplicación web moderna, modular y altamente escalable.

Se implementará una separación absoluta entre la interfaz de usuario (**frontend**) y la lógica de negocio (**backend**) utilizando un enfoque de **Diseño Guiado por el Dominio (DDD)** y **Arquitectura Hexagonal**. Todo el sistema correrá contenedorizado en Docker, integrándose de forma limpia con la infraestructura de red, base de datos y servidor web ya existentes en el servidor Debian de producción.

---

## 2. Arquitectura del Sistema

La aplicación se rige bajo los principios de la **Arquitectura Hexagonal (Puertos y Adaptadores)**. La lógica de negocio reside en el núcleo y está completamente aislada de dependencias externas como frameworks (Symfony), bases de datos (PostgreSQL) o librerías HTTP.

### 2.1 Capas del Sistema (Dirección de Dependencias)

```
Domain ←── Application ←── Infrastructure
  ↑              ↑                ↑
Sin deps     Orquesta         Detalles
externas     casos de uso     técnicos
```

1. **Dominio (Domain):** El núcleo del software. Contiene el estado, las reglas de negocio, entidades, agregados, objetos de valor (Value Objects) con sus propias validaciones e invariantes, y excepciones del negocio. No importa ninguna librería externa ni componentes de Symfony.

2. **Aplicación (Application):** Orquesta los casos de uso del sistema. Maneja la ejecución de comandos (Commands) y consultas (Queries), gestionando el flujo sin conocer los detalles de infraestructura.

3. **Infraestructura (Infrastructure):** La capa más externa. Contiene los detalles técnicos e implementaciones concretas: controladores HTTP de Symfony, repositorios de Doctrine ORM, migraciones y configuración de servicios.

### 2.2 Flujo de Trabajo CQRS

**Command Query Responsibility Segregation** separa las operaciones de escritura de las de lectura, evitando que las consultas públicas (clasificaciones, galería) compitan con las operaciones de administración.

* **Escritura (Commands):** Mutaciones del estado del sistema (ej. `InscribirCorredor`, `ImportarResultados`). Pasan por el **Symfony Messenger** con middleware transaccional de Doctrine (`doctrine_transaction`). El transport por defecto será **asíncrono vía Redis** para operaciones costosas (ver sección 5).
* **Lectura (Queries):** Consultas de datos optimizadas (ej. `ObtenerClasificacionCarrera`). Pasan por un bus de consultas independiente sin sobrecarga transaccional. Pueden acceder directamente a Doctrine DBAL para consultas SQL optimizadas sin pasar por el modelo de dominio.

### 2.3 Bounded Contexts (Contextos de Negocio)

| Contexto | Responsabilidad | Agregados principales |
|---|---|---|
| **Race** | Gestión de ediciones anuales y categorías | `RaceEdition`, `Category` |
| **Results** | Clasificaciones y tiempos por edición | `Result`, `FinishTime`, `Position` |
| **Registration** | Inscripciones de corredores | `Registration`, `Runner`, `BibNumber` |
| **Media** | Fotos, carteles, gestión de archivos | `Photo`, `Poster`, `BlogPost` |
| **Club** | Info del club y patrocinadores | `Club`, `Sponsor` |

---

## 3. Integración con Infraestructura Compartida

Para maximizar la eficiencia y evitar la duplicación de recursos en el servidor Debian de producción, el entorno Docker de este desarrollo se acopla a los recursos globales de infraestructura ya activos en la máquina host.

### 3.1 Red Compartida (`shared-network`)

El proyecto no creará una red propia aislada de tipo bridge, sino que se unirá de forma explícita a la red externa preexistente llamada `shared-network`. Esto permite la comunicación interna de alta velocidad por DNS de Docker entre este proyecto y otros servicios del servidor.

### 3.2 Persistencia (PostgreSQL Centralizado)

No se levantará un contenedor de base de datos exclusivo dentro de este proyecto. El backend se conectará directamente al contenedor global de **PostgreSQL** (`postgres-infra`) que ya corre en el Docker de infraestructura del servidor, utilizando una base de datos específica llamada `cokalba_running`.

### 3.3 Servidor Web Central (`nginx-infra`)

**Este proyecto no incluirá un contenedor Nginx propio en producción.** El servidor Nginx centralizado de la máquina actuará como proxy inverso global y servidor estático. Dicho Nginx compartido apuntará directamente al directorio público del contenedor PHP para la API, y servirá los archivos estáticos compilados del frontend desde el volumen compartido definido en la sección 7.

---

## 4. Entornos: Desarrollo vs. Producción

Una distinción fundamental que el proyecto debe mantener limpia desde el inicio es la separación entre el entorno **local de desarrollo** (cada desarrollador en su máquina) y el entorno de **producción** (el servidor Debian compartido).

### 4.1 Estrategia de Ficheros Docker Compose

Se utilizará la composición de ficheros de Docker Compose para no duplicar configuración:

```
docker-compose.yml              ← Base común (backend, frontend, queue, redis)
docker-compose.override.yml     ← Desarrollo local: añade postgres, nginx
docker-compose.prod.yml         ← Producción: se une a shared-network, sin postgres ni nginx propios
```

**En desarrollo** (`docker compose up`): Docker Compose aplica automáticamente el `override`, levantando un PostgreSQL local y un Nginx local. El desarrollador no necesita infraestructura externa.

**En producción** (`docker compose -f docker-compose.yml -f docker-compose.prod.yml up`): Solo se levantan los contenedores de aplicación, conectándose a los servicios de infraestructura ya existentes.

### 4.2 docker-compose.yml (Base)

```yaml
version: '3.9'

services:

  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    volumes:
      - ./backend:/var/www/backend   # solo en dev, override lo gestiona
    environment:
      - APP_ENV=${APP_ENV:-dev}
      - DATABASE_URL=${DATABASE_URL}
      - MESSENGER_TRANSPORT_DSN=${MESSENGER_TRANSPORT_DSN}
      - REDIS_URL=${REDIS_URL}
      - JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
      - JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
      - JWT_PASSPHRASE=${JWT_PASSPHRASE}
      - STORAGE_DRIVER=${STORAGE_DRIVER:-local}
      - STORAGE_LOCAL_PATH=/var/www/backend/public/uploads
    networks:
      - cokalba-net

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    environment:
      - VITE_API_URL=${VITE_API_URL:-http://localhost/api/v1}
    networks:
      - cokalba-net

  queue:
    build:
      context: ./backend
      dockerfile: Dockerfile
    command: php bin/console messenger:consume async --time-limit=3600
    depends_on:
      - backend
    networks:
      - cokalba-net
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    networks:
      - cokalba-net

volumes:
  redis_data:

networks:
  cokalba-net:
    driver: bridge
```

### 4.3 docker-compose.override.yml (Desarrollo Local)

```yaml
version: '3.9'

services:

  backend:
    volumes:
      - ./backend:/var/www/backend   # hot-reload en desarrollo

  # PostgreSQL local para desarrollo
  postgres:
    image: postgres:16-alpine
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: cokalba_running
      POSTGRES_USER: cokalba
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_dev_data:/var/lib/postgresql/data
    networks:
      - cokalba-net
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U cokalba"]
      interval: 5s
      retries: 5

  # Nginx local para desarrollo
  nginx:
    image: nginx:1.25-alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx/dev.conf:/etc/nginx/conf.d/default.conf
      - ./backend/public:/var/www/backend/public
    depends_on:
      - backend
      - frontend
    networks:
      - cokalba-net

volumes:
  postgres_dev_data:
```

### 4.4 docker-compose.prod.yml (Producción)

```yaml
version: '3.9'

services:

  backend:
    volumes: []   # sin volumen de código en producción

  frontend:
    volumes:
      # Build estático accesible por nginx-infra del servidor
      - frontend_static:/var/www/frontend/dist

  queue:
    restart: always

networks:
  cokalba-net:
    # En producción se une a la red compartida del servidor
    external: true
    name: shared-network

volumes:
  frontend_static:
    external: true   # gestionado por nginx-infra
```

---

## 5. Mensajería Asíncrona (Symfony Messenger + Redis)

Las operaciones costosas o diferibles no se ejecutarán de forma síncrona en la petición HTTP. Se gestionarán mediante el **Symfony Messenger** con un transport Redis.

### 5.1 Operaciones asíncronas

| Operación | Motivo |
|---|---|
| `ImportarResultadosCSV` | Puede procesar cientos de filas; bloquearía la petición |
| `GenerarThumbnailFoto` | Redimensionado de imágenes costoso en CPU |
| `EnviarEmailConfirmacion` | El correo no debe bloquear la respuesta al admin |
| `RecalcularPosiciones` | Tras importar, recalcula posiciones generales/categoría |

### 5.2 Configuración del Messenger

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      async:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'  # redis://redis:6379/messages
        options:
          auto_setup: true
        retry_strategy:
          max_retries: 3
          delay: 1000
          multiplier: 2

    routing:
      App\Application\Results\ImportResults\ImportResultsCommand: async
      App\Application\Media\GenerateThumbnail\GenerateThumbnailCommand: async
      App\Application\Notification\SendEmail\SendEmailCommand: async
```

### 5.3 Worker (contenedor `queue`)

El contenedor `queue` consume mensajes de forma continua. En producción se reinicia automáticamente (`restart: always`) y limita su tiempo de vida para evitar fugas de memoria (`--time-limit=3600`).

---

## 6. Autenticación y Seguridad (JWT)

El panel de administración requiere autenticación. Al tratarse de una SPA Vue desacoplada del backend, se utilizará **JSON Web Tokens (JWT)** en lugar de sesiones de servidor.

### 6.1 Stack de autenticación

* **Backend:** `lexik/jwt-authentication-bundle` + Symfony Security
* **Frontend:** Axios interceptors + Pinia auth store + Vue Router guards

### 6.2 Flujo de autenticación

```
1. Admin POST /api/v1/auth/login  { email, password }
        ↓
2. Symfony verifica credenciales
        ↓
3. Responde con { token: "eyJ...", refresh_token: "..." }
        ↓
4. Vue guarda token en memoria (NO en localStorage)
   El refresh_token va en cookie HttpOnly
        ↓
5. Axios añade  Authorization: Bearer <token>  en cada petición
        ↓
6. Symfony valida el JWT en cada request protegido
        ↓
7. Cuando el token expira (1h), Vue usa el refresh_token
   para obtener uno nuevo sin re-login
```

> **Seguridad importante:** El JWT de corta vida se guarda en memoria (variable de Pinia), nunca en `localStorage`, para mitigar ataques XSS. El `refresh_token` de larga vida viaja en cookie `HttpOnly; Secure; SameSite=Strict`.

### 6.3 Roles y permisos

| Rol | Acceso |
|---|---|
| `ROLE_ADMIN` | Panel completo: ediciones, resultados, fotos, blog, sponsors |
| `ROLE_EDITOR` | Solo blog y galería (sin importar resultados ni gestionar ediciones) |
| `ROLE_PUBLIC` | Solo endpoints GET públicos (sin autenticación) |

### 6.4 Endpoints protegidos

```
POST /api/v1/auth/login              → público
POST /api/v1/auth/refresh            → público (con refresh_token cookie)
POST /api/v1/auth/logout             → requiere JWT

GET  /api/v1/*                       → público (solo lectura)
POST /api/v1/admin/*                 → ROLE_ADMIN
PUT  /api/v1/admin/*                 → ROLE_ADMIN
DELETE /api/v1/admin/*               → ROLE_ADMIN
POST /api/v1/admin/posts             → ROLE_EDITOR mínimo
```

---

## 7. Gestión de Archivos y Almacenamiento

El proyecto maneja varios tipos de archivos: **carteles** de cada edición, **fotos** de galería y **avatares/logos** de patrocinadores. La estrategia debe funcionar tanto en desarrollo como en producción.

### 7.1 Puerto de dominio (StoragePort)

El dominio define una interfaz agnóstica al driver de almacenamiento:

```php
// src/Domain/Media/Port/StoragePort.php
interface StoragePort
{
    public function store(UploadedFile $file, string $path): string; // retorna URL pública
    public function delete(string $path): void;
    public function url(string $path): string;
}
```

### 7.2 Adaptadores disponibles

| Driver | Entorno | Descripción |
|---|---|---|
| `LocalStorageAdapter` | Desarrollo | Guarda en `public/uploads/`, Nginx lo sirve directamente |
| `S3StorageAdapter` | Producción (opcional) | AWS S3 o compatible (Cloudflare R2, MinIO) |
| `SharedVolumeAdapter` | Producción (inicial) | Volumen Docker montado, servido por `nginx-infra` |

### 7.3 Configuración por entorno

```
# .env.dev
STORAGE_DRIVER=local
STORAGE_LOCAL_PATH=/var/www/backend/public/uploads
STORAGE_PUBLIC_URL=http://localhost/uploads

# .env.prod (opción volumen compartido)
STORAGE_DRIVER=shared_volume
STORAGE_LOCAL_PATH=/var/www/media/cokalba
STORAGE_PUBLIC_URL=https://cokalba-running.es/media

# .env.prod (opción S3/R2 en el futuro)
STORAGE_DRIVER=s3
S3_BUCKET=cokalba-media
S3_REGION=auto
S3_ENDPOINT=https://xxxx.r2.cloudflarestorage.com
S3_KEY=...
S3_SECRET=...
```

### 7.4 Estructura de directorios de almacenamiento

```
uploads/
├── posters/
│   ├── 2026/cartel-ix-edicion.jpg
│   └── 2025/cartel-viii-edicion.jpg
├── gallery/
│   ├── 2026/
│   │   ├── original/foto-001.jpg
│   │   └── thumbnail/foto-001.jpg   ← generado por queue worker
│   └── 2025/
├── sponsors/
│   └── logos/erbe.png
└── blog/
    └── covers/noticia-001.jpg
```

---

## 8. Estructura de Carpetas del Proyecto

### 8.1 Raíz del Proyecto

```
cokalba-running/
├── docker-compose.yml
├── docker-compose.override.yml
├── docker-compose.prod.yml
├── .env.example
├── .gitignore
├── especificaciones.md
├── backend/
├── frontend/
├── nginx/
│   ├── dev.conf
│   └── prod-vhost.conf        ← configuración para nginx-infra del servidor
└── docs/
    ├── api.md                  ← documentación de endpoints
    └── deployment.md           ← guía de despliegue
```

### 8.2 Backend (Symfony — Arquitectura Hexagonal)

```
backend/
├── Dockerfile
├── composer.json
├── symfony.lock
├── .env
├── .env.test
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── messenger.yaml
│   │   ├── security.yaml
│   │   └── lexik_jwt_authentication.yaml
│   ├── jwt/
│   │   ├── private.pem         ← NO subir a git
│   │   └── public.pem
│   └── routes/
│       └── api.yaml
├── public/
│   ├── index.php
│   └── uploads/                ← archivos en desarrollo
├── src/
│   ├── Domain/                 ← NÚCLEO — cero dependencias externas
│   │   ├── Race/
│   │   │   ├── Entity/
│   │   │   │   ├── RaceEdition.php
│   │   │   │   └── Category.php
│   │   │   ├── ValueObject/
│   │   │   │   ├── RaceEditionId.php
│   │   │   │   ├── Distance.php
│   │   │   │   └── EditionYear.php
│   │   │   ├── Repository/
│   │   │   │   └── RaceEditionRepositoryInterface.php
│   │   │   └── Event/
│   │   │       └── RaceEditionCreated.php
│   │   ├── Results/
│   │   │   ├── Entity/
│   │   │   │   └── Result.php
│   │   │   ├── ValueObject/
│   │   │   │   ├── FinishTime.php
│   │   │   │   └── Position.php
│   │   │   ├── Repository/
│   │   │   │   └── ResultRepositoryInterface.php
│   │   │   └── Service/
│   │   │       └── PositionCalculator.php   ← lógica pura de dominio
│   │   ├── Registration/
│   │   │   ├── Entity/
│   │   │   │   ├── Registration.php
│   │   │   │   └── Runner.php
│   │   │   └── ValueObject/
│   │   │       ├── BibNumber.php
│   │   │       └── Category.php
│   │   ├── Media/
│   │   │   ├── Entity/
│   │   │   │   ├── Photo.php
│   │   │   │   ├── Poster.php
│   │   │   │   └── BlogPost.php
│   │   │   └── Port/
│   │   │       └── StoragePort.php          ← interfaz de almacenamiento
│   │   └── Club/
│   │       ├── Entity/
│   │       │   └── Sponsor.php
│   │       └── Repository/
│   │           └── SponsorRepositoryInterface.php
│   │
│   ├── Application/            ← CASOS DE USO — orquesta el dominio
│   │   ├── Race/
│   │   │   ├── CreateRaceEdition/
│   │   │   │   ├── CreateRaceEditionCommand.php
│   │   │   │   └── CreateRaceEditionHandler.php
│   │   │   └── GetRaceEditions/
│   │   │       ├── GetRaceEditionsQuery.php
│   │   │       └── GetRaceEditionsHandler.php
│   │   ├── Results/
│   │   │   ├── ImportResults/
│   │   │   │   ├── ImportResultsCommand.php  ← se despacha de forma async
│   │   │   │   └── ImportResultsHandler.php
│   │   │   └── GetClassification/
│   │   │       ├── GetClassificationQuery.php
│   │   │       └── GetClassificationHandler.php
│   │   └── Media/
│   │       ├── UploadPhoto/
│   │       │   ├── UploadPhotoCommand.php
│   │       │   └── UploadPhotoHandler.php    ← usa StoragePort
│   │       └── GenerateThumbnail/
│   │           ├── GenerateThumbnailCommand.php
│   │           └── GenerateThumbnailHandler.php  ← async via Messenger
│   │
│   └── Infrastructure/         ← DETALLES TÉCNICOS
│       ├── Http/
│       │   ├── Controller/
│       │   │   ├── Api/
│       │   │   │   ├── RaceController.php
│       │   │   │   ├── ResultController.php
│       │   │   │   ├── RegistrationController.php
│       │   │   │   ├── PhotoController.php
│       │   │   │   ├── BlogController.php
│       │   │   │   └── SponsorController.php
│       │   │   └── Auth/
│       │   │       └── AuthController.php
│       │   └── Request/
│       │       ├── ImportResultsRequest.php
│       │       └── UploadPhotoRequest.php
│       ├── Persistence/
│       │   ├── Doctrine/
│       │   │   ├── Repository/
│       │   │   │   ├── DoctrineRaceEditionRepository.php
│       │   │   │   ├── DoctrineResultRepository.php
│       │   │   │   └── DoctrineSponsorRepository.php
│       │   │   └── Mapping/            ← XML o atributos Doctrine
│       │   └── Migration/
│       ├── Storage/
│       │   ├── LocalStorageAdapter.php
│       │   └── S3StorageAdapter.php
│       └── Mail/
│           └── SymfonyMailerAdapter.php
│
└── tests/
    ├── Unit/
    │   └── Domain/
    │       ├── FinishTimeTest.php
    │       ├── PositionCalculatorTest.php
    │       └── BibNumberTest.php
    ├── Integration/
    │   └── Infrastructure/
    │       └── DoctrineResultRepositoryTest.php
    └── Functional/
        └── Api/
            ├── GetClassificationTest.php
            └── ImportResultsTest.php
```

### 8.3 Frontend (Vue 3)

```
frontend/
├── Dockerfile
├── package.json
├── vite.config.ts
├── tailwind.config.ts
├── tsconfig.json
└── src/
    ├── main.ts
    ├── router/
    │   └── index.ts
    ├── stores/
    │   ├── auth.store.ts       ← JWT en memoria, NO localStorage
    │   ├── race.store.ts
    │   ├── results.store.ts
    │   └── gallery.store.ts
    ├── services/
    │   ├── api.service.ts      ← Axios base + interceptores JWT
    │   ├── race.service.ts
    │   ├── results.service.ts
    │   └── media.service.ts
    ├── composables/
    │   ├── useCountdown.ts
    │   ├── useResults.ts
    │   └── useAuth.ts
    ├── components/
    │   ├── ui/
    │   ├── race/
    │   ├── results/
    │   ├── gallery/
    │   └── layout/
    ├── views/
    │   ├── HomeView.vue
    │   ├── RaceView.vue
    │   ├── EditionsView.vue
    │   ├── GalleryView.vue
    │   ├── BlogView.vue
    │   └── admin/
    │       ├── AdminLoginView.vue
    │       ├── AdminDashboardView.vue
    │       ├── AdminResultsImport.vue
    │       └── AdminPhotosView.vue
    └── types/
        ├── race.types.ts
        ├── result.types.ts
        └── media.types.ts
```

---

## 9. Testing

La arquitectura hexagonal facilita enormemente el testing porque el dominio no tiene dependencias externas y se puede testear de forma completamente aislada.

### 9.1 Estrategia por capa

| Capa | Tipo de test | Herramienta | Dependencias |
|---|---|---|---|
| **Domain** | Unitario | PHPUnit / Pest | Ninguna (puro PHP) |
| **Application** | Unitario con mocks | PHPUnit / Pest | Repositorios mockeados |
| **Infrastructure** | Integración | PHPUnit + Doctrine | BD de test real |
| **HTTP / API** | Funcional | Symfony WebTestCase | App completa en memoria |
| **Frontend** | Unitario + componentes | Vitest + Vue Test Utils | DOM virtual |
| **E2E** | End-to-end (futuro) | Playwright | App completa levantada |

### 9.2 Configuración de entorno de test

```yaml
# docker-compose.override.yml — base de datos exclusiva para tests
postgres-test:
  image: postgres:16-alpine
  ports:
    - "5433:5432"
  environment:
    POSTGRES_DB: cokalba_test
    POSTGRES_USER: cokalba
    POSTGRES_PASSWORD: secret
  tmpfs:
    - /var/lib/postgresql/data   # en memoria, más rápido y sin estado
  networks:
    - cokalba-net
```

```bash
# .env.test
DATABASE_URL=postgresql://cokalba:secret@postgres-test:5432/cokalba_test
APP_ENV=test
```

### 9.3 Ejemplos de tests por capa

```php
// tests/Unit/Domain/FinishTimeTest.php
class FinishTimeTest extends TestCase
{
    public function test_finish_time_formats_correctly(): void
    {
        $time = FinishTime::fromSeconds(2052); // 34 minutos 12 segundos
        $this->assertEquals('00:34:12', $time->format());
    }

    public function test_finish_time_rejects_negative_values(): void
    {
        $this->expectException(InvalidFinishTimeException::class);
        FinishTime::fromSeconds(-1);
    }
}

// tests/Unit/Domain/PositionCalculatorTest.php
class PositionCalculatorTest extends TestCase
{
    public function test_calculates_positions_correctly(): void
    {
        $results = [
            ResultStub::withTime(FinishTime::fromSeconds(2052)),
            ResultStub::withTime(FinishTime::fromSeconds(2108)),
            ResultStub::withTime(FinishTime::fromSeconds(2147)),
        ];
        $ranked = (new PositionCalculator())->rank($results);
        $this->assertEquals(1, $ranked[0]->position()->value());
    }
}
```

```php
// tests/Functional/Api/GetClassificationTest.php
class GetClassificationTest extends WebTestCase
{
    public function test_returns_classification_for_edition(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/editions/2025/results');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStructure($client->getResponse(), [
            'data' => [['position', 'runner', 'finish_time', 'category']]
        ]);
    }
}
```

```typescript
// frontend/src/components/results/__tests__/ResultsTable.test.ts
import { mount } from '@vue/test-utils'
import ResultsTable from '../ResultsTable.vue'

describe('ResultsTable', () => {
  it('renders correct number of rows', () => {
    const results = [/* fixture data */]
    const wrapper = mount(ResultsTable, { props: { results } })
    expect(wrapper.findAll('tbody tr')).toHaveLength(results.length)
  })
})
```

### 9.4 Comandos de test

```bash
# Backend
docker compose exec backend php bin/phpunit
docker compose exec backend php bin/phpunit --coverage-html coverage/
docker compose exec backend php bin/phpunit tests/Unit           # solo unitarios
docker compose exec backend php bin/phpunit tests/Functional     # solo funcionales

# Frontend
docker compose exec frontend npm run test
docker compose exec frontend npm run test:coverage
```

---

## 10. API REST — Referencia de Endpoints

### Endpoints Públicos (GET)

```
GET /api/v1/editions                     → Lista de todas las ediciones
GET /api/v1/editions/active              → Edición activa (la actual)
GET /api/v1/editions/{year}              → Detalle de una edición
GET /api/v1/editions/{year}/categories   → Categorías de la edición
GET /api/v1/editions/{year}/results      → Clasificación general
GET /api/v1/editions/{year}/results?category={slug}  → Por categoría
GET /api/v1/editions/{year}/results?gender={m|f}     → Por género
GET /api/v1/editions/{year}/photos       → Galería de fotos
GET /api/v1/photos/featured              → Fotos destacadas (portada)
GET /api/v1/runners/{id}/results         → Historial de un corredor
GET /api/v1/sponsors                     → Patrocinadores activos
GET /api/v1/editions/{year}/sponsors     → Patrocinadores de una edición
GET /api/v1/posts                        → Posts publicados
GET /api/v1/posts/{slug}                 → Post individual
```

### Endpoints de Admin (requieren JWT)

```
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
POST   /api/v1/auth/logout

POST   /api/v1/admin/editions
PUT    /api/v1/admin/editions/{id}
POST   /api/v1/admin/editions/{id}/results/import   ← CSV async
POST   /api/v1/admin/editions/{id}/poster           ← Upload cartel

POST   /api/v1/admin/photos
PUT    /api/v1/admin/photos/{id}
DELETE /api/v1/admin/photos/{id}

POST   /api/v1/admin/posts
PUT    /api/v1/admin/posts/{id}
DELETE /api/v1/admin/posts/{id}

POST   /api/v1/admin/sponsors
PUT    /api/v1/admin/sponsors/{id}
DELETE /api/v1/admin/sponsors/{id}
```

---

## 11. Checklist de Arranque del Proyecto

### Fase 1 — Infraestructura y base (Semana 1)
- [ ] Crear repositorio Git con estructura de ramas (`main`, `develop`, `feature/*`)
- [ ] Crear `docker-compose.yml` base y `override` de desarrollo
- [ ] Verificar conexión con `shared-network` y `postgres-infra` en entorno de prueba
- [ ] Crear proyecto Symfony (`symfony new backend --webapp`)
- [ ] Crear proyecto Vue 3 + Vite (`npm create vue@latest frontend`)
- [ ] Configurar Nginx de desarrollo
- [ ] Generar claves JWT (`php bin/console lexik:jwt:generate-keypair`)
- [ ] Levantar entorno completo: `docker compose up -d`

### Fase 2 — Backend: Dominio y Persistencia (Semana 2)
- [ ] Definir entidades de dominio y Value Objects
- [ ] Escribir tests unitarios del dominio
- [ ] Crear migraciones Doctrine y aplicarlas
- [ ] Implementar repositorios con Doctrine
- [ ] Configurar Symfony Messenger con Redis
- [ ] Implementar autenticación JWT

### Fase 3 — Backend: API y Casos de Uso (Semana 3)
- [ ] Implementar endpoints GET públicos (ediciones, resultados, galería)
- [ ] Implementar `ImportResultsCommand` y handler asíncrono
- [ ] Implementar `UploadPhotoCommand` con `LocalStorageAdapter`
- [ ] Tests funcionales de API
- [ ] Documentar API (NelmioApiDocBundle / OpenAPI)

### Fase 4 — Frontend (Semana 4)
- [ ] Layout base: NavBar, Footer, router
- [ ] Pinia auth store con JWT en memoria
- [ ] Axios interceptores + refresh token
- [ ] Páginas públicas: Home, Ediciones, Resultados, Galería, Blog
- [ ] Panel admin: Login, Dashboard, Importar resultados, Fotos
- [ ] Tests de componentes con Vitest

### Fase 5 — Producción (Semana 5)
- [ ] `Dockerfile` de producción (multi-stage build)
- [ ] `docker-compose.prod.yml` con `shared-network`
- [ ] Configurar vhost en `nginx-infra` del servidor
- [ ] Variables de entorno de producción (secretos seguros)
- [ ] Despliegue inicial y verificación
- [ ] Migrar datos históricos de ediciones anteriores

---

## 12. Variables de Entorno de Referencia

```bash
# .env.example — copia a .env y rellena los valores

# Symfony
APP_ENV=dev
APP_SECRET=cambia_esto_por_un_valor_seguro

# Base de datos
DATABASE_URL=postgresql://cokalba:secret@postgres:5432/cokalba_running

# Redis / Messenger
REDIS_URL=redis://redis:6379
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=cambia_esto
JWT_TTL=3600           # 1 hora
JWT_REFRESH_TTL=86400  # 24 horas

# Almacenamiento
STORAGE_DRIVER=local   # local | s3 | shared_volume
STORAGE_LOCAL_PATH=/var/www/backend/public/uploads
STORAGE_PUBLIC_URL=http://localhost/uploads

# Email (configurar en producción)
# MAILER_DSN=smtp://user:pass@smtp.provider.com:587  # prod

# Frontend
VITE_API_URL=http://localhost/api/v1
```

---

*Documento técnico del proyecto Cokalba Running · Club de Atletismo Coca de Alba*
*Última actualización: Mayo 2026*
