# gestionAutomotriz — SaaS de Gestión Automotriz (Fase 0)

Sistema web multiempresa para talleres mecánicos y concesionarios desarrollado con **Laravel**, **PHP 8.3+**, **MySQL/MariaDB** y **Bootstrap 5 (CDN)**.

Esta fase corresponde a la **FASE 0: Preparación de la base técnica y arquitectura**. No contiene aún lógica de negocio funcional ni módulos avanzados de facturación o vehículos, sino los cimientos limpios para construirlos de forma mantenible y escalable.

---

## 1. Requisitos del Entorno Local

- **Servidor:** WAMP Server 3.x (o superior) en Windows.
- **PHP:** Versión `8.3.14` (o PHP 8.3+).
- **Extensiones PHP necesarias:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`.
- **Motor de Base de Datos:** MySQL o MariaDB (incluido en WAMP).
- **Composer:** 2.x.

---

## 2. Configuración en WAMP Server

### 2.1. Configuración de VirtualHost (Importante)
El DocumentRoot y directorio del VirtualHost en Apache **deben apuntar obligatoriamente a la carpeta `public`** del proyecto:

- **Ruta destino:** `C:\wamp64\www\gestionAutomotriz\public`

Ejemplo de configuración en `C:\wamp64\bin\apache\apacheX.Y.Z\conf\extra\httpd-vhosts.conf` o a través del menú de WAMP (*Add a VirtualHost*):

```apache
<VirtualHost *:80>
    ServerName gestionautomotriz.local
    DocumentRoot "C:/wamp64/www/gestionAutomotriz/public"
    <Directory "C:/wamp64/www/gestionAutomotriz/public/">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require local
    </Directory>
</VirtualHost>
```

> **Nota:** Si utilizas `gestionautomotriz.local`, asegúrate de agregar la entrada al archivo `C:\Windows\System32\drivers\etc\hosts`:
> ```text
> 127.0.0.1 gestionautomotriz.local
> ```

---

## 3. Instalación y Configuración del Proyecto

### Paso 1: Configurar variables de entorno (`.env`)
Verifica que el archivo `.env` exista en la raíz del proyecto. Si no existe, créalo copiando el archivo de ejemplo:

```powershell
Copy-Item .env.example .env
```

Configura tus credenciales de MySQL en `.env`:

```env
APP_NAME=gestionAutomotriz
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://gestionautomotriz.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_automotriz
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 2: Generar la clave de aplicación
Ejecuta con el PHP de WAMP:
```powershell
& "C:\wamp64\bin\php\php8.3.14\php.exe" artisan key:generate
```

---

## 4. Estructura Arquitectónica Implementada

```text
app/
├── Domain/                           # Dominios y subdominios del negocio
│   ├── Companies/                    # Dominio de Empresas (Tenants)
│   │   ├── Contracts/
│   │   │   └── CompanyScopedInterface.php   # Contrato para entidades aisladas por empresa
│   │   ├── Traits/
│   │   │   └── BelongsToCompany.php         # Trait para asignar y consultar company_id
│   │   └── Models/                   # Modelos futuros de Company/Tenant
│   ├── Users/                        # Dominio de Usuarios
│   ├── Vehicles/                     # Dominio de Vehículos (Fase 2)
│   ├── Maintenance/                  # Dominio de Órdenes y Servicios (Fase 2)
│   ├── Reminders/                    # Dominio de Recordatorios (Fase 3)
│   └── Billing/                      # Dominio de Facturación y Planes (Fase 4)
├── Http/
│   └── Controllers/
│       ├── DashboardController.php   # Controlador web del Dashboard técnico
│       └── HealthController.php      # Endpoint JSON /health para monitoreo y API móvil
└── Services/
    └── Health/
        └── SystemHealthService.php   # Diagnóstico desacoplado del estado del sistema

resources/
└── views/
    ├── layouts/
    │   └── app.blade.php             # Layout base Blade con Bootstrap 5 (CDN)
    ├── components/
    │   ├── navbar.blade.php          # Barra de navegación superior
    │   ├── sidebar.blade.php         # Menú lateral estructurado por dominios
    │   └── flash-messages.blade.php  # Alertas flash de sesión
    └── dashboard/
        └── index.blade.php           # Vista institucional y de diagnóstico técnico
```

---

## 5. Verificación y Pruebas Locales

### 5.1. Ejecutar las pruebas automatizadas
Para validar que el endpoint técnico y las vistas responden correctamente:

```powershell
& "C:\wamp64\bin\php\php8.3.14\php.exe" artisan test --filter=HealthCheckTest
& "C:\wamp64\bin\php\php8.3.14\php.exe" artisan test --filter=DashboardTest
```

### 5.2. Probar mediante el servidor integrado de desarrollo (Alternativa rápida)
Si deseas validar antes de configurar el VirtualHost de Apache en WAMP:

```powershell
& "C:\wamp64\bin\php\php8.3.14\php.exe" artisan serve --port=8000
```

Y luego abrir en el navegador o mediante `curl`:
- Dashboard principal: http://127.0.0.1:8000 o http://127.0.0.1:8000/dashboard
- Endpoint de salud del sistema: http://127.0.0.1:8000/health

### 5.3. Probar mediante WAMP VirtualHost
Una vez configurado Apache y reiniciados los servicios de WAMP:
- Visita: `http://gestionautomotriz.local`
- Visita: `http://gestionautomotriz.local/health`
