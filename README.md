# FaceAccess Pro - Sistema de Control de Acceso Biométrico

Este proyecto es un sistema completo de reconocimiento facial utilizando Laravel 11 y YOLOv8.

## Estructura del Proyecto

- `backend/`: Código fuente de Laravel 11 (API + Panel de Administración).
- `face_service/`: Microservicio Python (FastAPI + InsightFace + YOLOv8).
- `docker-compose.yml`: Orquestación de contenedores.

## 🚀 Guía de Instalación Rápida

Sigue estos pasos para poner en marcha el sistema desde cero.

### 1. Prerrequisitos
- Docker & Docker Compose
- Node.js & NPM (opcional, si usas Docker para todo)

### 2. Inicializar el Backend (Laravel)

Como solo hemos generado el código personalizado, necesitas el núcleo de Laravel. Entra a la carpeta `backend` e instala Laravel sobreponiendo los archivos.

```bash
cd backend

# Opción A: Si tienes PHP/Composer localmente
composer create-project laravel/laravel . --force
composer require laravel/sanctum spatie/laravel-permission livewire/livewire predis/predis

# Opción B: Usando Docker (si no tienes PHP local)
# (Ejecuta el docker-compose primero y luego entra al contenedor)
```

**NOTA IMPORTANTE:** Al ejecutar `create-project --force`, se pueden sobrescribir algunos archivos de configuración. Asegúrate de restaurar/verificar:
- `routes/web.php`
- `routes/api.php`
- `app/Models/*` (Tus modelos personalizados ya están ahí)

Es recomendable ejecutar:
```bash
composer require laravel/sanctum spatie/laravel-permission livewire/livewire
php artisan install:api
```

### 3. Configuración de Entorno (.env)

En la carpeta `backend`, copia `.env.example` a `.env` y ajusta:

```ini
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=faceaccess
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
PYTHON_SERVICE_URL=http://face-service:5000
```

### 4. Ejecutar con Docker

Regresa a la raíz del proyecto y levanta los servicios:

```bash
docker-compose up -d --build
```

Esto levantará:
- Backend Laravel en `http://localhost:8000`
- Microservicio Python en `http://localhost:5000`
- MySQL y Redis

### 5. Configurar Base de Datos

Entra al contenedor de Laravel y corre las migraciones:

```bash
docker-compose exec app bash
# Dentro del contenedor:
php artisan migrate
php artisan storage:link
```

### 6. Registrar un Usuario Admin y Probando

Puedes crear un usuario via tinker o registrándote en la UI si habilitas el registro.
Para registrar rostros, ve a `http://localhost:8000/users/create`.

## Arquitectura del Sistema

```mermaid
graph TD
    Cam[Cámara IP/RTSP] -->|Stream| Laravel[Backend Laravel]
    Laravel -->|Frame (HTTP POST)| Py[Python Microservice]
    Py -->|Detect & Embed| Insight[InsightFace Model]
    Py -->|JSON Result| Laravel
    Laravel -->|Match Logic| DB[(MySQL)]
    Laravel -->|Event Broadcast| Redis
    Redis -->|WebSocket| Dashboard[Livewire Dashboard]
```

## Endpoints Clave

- `POST /api/process-frame`: Recibe `frame` y `camera_id`. Procesa la entrada.
- `POST /api/proxy/extract-embedding`: Extrae características faciales de una foto subida.

---
Generado por Antigravity AI Agent.
