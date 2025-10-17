# Sistema de Gestión de la Casa del Profesor Universitario (CPU-UNET)

Este repositorio contiene el backend de la aplicación para la gestión administrativa de la Casa del Profesor Universitario de la UNET, desarrollado en Laravel.

## 1. Requisitos Previos

Antes de comenzar, asegúrate de tener instalado lo siguiente:

- **PHP >= 8.2**
- **Composer** (gestor de dependencias de PHP)
- Un servidor de base de datos **MySQL**
- **Node.js** y **npm** (opcional, para desarrollo de assets)

## 2. Guía de Instalación Local

Sigue estos pasos para configurar el proyecto en tu entorno de desarrollo:

1.  **Clonar el repositorio:**
    ```bash
    git clone <url-del-repositorio>
    cd cpu-backend
    ```

2.  **Instalar dependencias de PHP:**
    ```bash
    composer install
    ```

3.  **Configurar el entorno:**
    Copia el archivo de ejemplo para las variables de entorno y genera la clave de la aplicación.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Configurar las variables de entorno:**
    Abre el archivo `.env` y configura las variables, especialmente las de la base de datos (`DB_*`), SendPulse (`SENDPULSE_*`) y Cloudflare R2 (`R2_*`).

5.  **Ejecutar las migraciones y los seeders:**
    Esto creará la estructura de la base de datos y la llenará con datos iniciales (áreas, academias y un usuario administrador).
    ```bash
    php artisan migrate --seed
    ```
    > **Nota:** Las credenciales del administrador inicial se definen en las variables `INITIAL_ADMIN_EMAIL` y `INITIAL_ADMIN_PASSWORD` de tu archivo `.env`.

6.  **Iniciar el servidor de desarrollo:**
    ```bash
    php artisan serve
    ```

¡Listo! La API estará disponible en `http://localhost:8000` (o la URL que hayas configurado).

## 3. Estructura del Proyecto

El proyecto sigue una arquitectura por capas para mantener el código organizado y escalable:

- `app/Http/Controllers`
  Controladores delgados que gestionan las solicitudes y respuestas HTTP.

- `app/Http/Requests`
  Clases `FormRequest` que contienen la lógica de validación y autorización de las solicitudes.

- `app/Services`
  Clases que encapsulan la lógica de negocio principal de la aplicación (ej. crear una reserva, aprobar una invitación).

- `app/Models`
  Modelos de Eloquent que definen las entidades de la base de datos y sus relaciones.

- `app/Enums`
  Enumeraciones de PHP para estandarizar valores fijos como roles y estados, mejorando la legibilidad y robustez del código.

- `app/Support`
  Clases de utilidad, como `R2Storage`, que centraliza la interacción con el servicio de almacenamiento de Cloudflare.

- `routes/api.php`
  Archivo donde se definen todos los endpoints de la API v1.

- `specs/` y `docs/`
  Contienen la documentación funcional y de negocio del proyecto en formato Markdown.