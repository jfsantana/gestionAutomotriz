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

### Recordatorios de mantenimiento por email

- Envío automático a las **08:00 de America/Caracas**, exactamente **dos días calendario antes** de `next_due_date`.
- Solo se notifican órdenes del taller completadas, con cliente y vehículo disponibles y una empresa operativa. Los registros personales del cliente y vehículos dados de baja no se notifican.
- En **Recordatorios → Acciones**, únicamente un administrador activo de la empresa propietaria puede usar **Enviar recordatorio**. No está permitido para mecánicos, clientes ni superadministradores.
- La lista, los filtros, los contadores y los recordatorios del dashboard/vehículo validan que la orden, el cliente y el vehículo pertenezcan a la misma empresa y que el vehículo corresponda al cliente. La misma protección se aplica a las órdenes, sus detalles y los historiales. Los registros inconsistentes se excluyen hasta reconciliarse: no se debe resolver el problema únicamente ocultándolos. Los clientes no pueden acceder a los módulos administrativos y el personal sin empresa no obtiene una vista global. Solo el superadministrador conserva la vista global de registros coherentes.
- El envío manual admite vencimientos futuros, vencidos o solo por kilometraje. No se puede calcular un aviso automático de dos días para un vencimiento sin fecha.
- Ambos canales comparten el límite de **un correo por orden y día en Caracas**, aun si cambia la fecha de vencimiento. Se muestra el último envío y se bloquean nuevos envíos ese día.
- Se envía al email del cliente, sin copias. El mensaje contiene los detalles del mantenimiento, contacto del taller y acceso al portal; no confirma una cita.

**Puesta en marcha:**

1. Ejecuta `php artisan migrate` para agregar el registro del último envío y el índice de vencimientos.
2. Configura `APP_URL` con la URL pública y las variables `MAIL_*` con un transporte real (por ejemplo SMTP). El transporte `log` solo escribe en el registro; no entrega correos.
3. En el Programador de tareas de Windows, ejecuta `php artisan schedule:run` **cada minuto**, usando la ruta absoluta de PHP y esta carpeta como directorio de inicio. La aplicación programa internamente las 08:00 de Caracas, independientemente de la zona del servidor.
4. Comprueba la programación con `php artisan schedule:list`. No se requiere un trabajador de colas: el envío es síncrono, como las notificaciones existentes.

Comando de envío (envía correos reales si el transporte está configurado):

```powershell
php artisan maintenance:send-reminders
php artisan maintenance:send-reminders --company=1
```

El comando se puede repetir el mismo día: omite órdenes ya notificadas. Los fallos se muestran por orden y devuelven código de salida no exitoso sin impedir el procesamiento de las demás. La ejecución programada guarda su salida en `storage/logs/maintenance-reminders.log`; los errores de transporte también se reportan al registro de Laravel. Si el servidor está apagado a las 08:00, ejecuta el comando ese mismo día; no envía avisos atrasados de días anteriores.

La marca `reminder_sent_at` se guarda tras aceptar el mensaje el transporte, bajo bloqueo de base de datos para serializar envíos simultáneos. Esto no acredita recepción en la bandeja de entrada. Ante un fallo de conexión ambiguo o una interrupción entre la aceptación SMTP y el guardado, verifica los registros del proveedor antes de reintentar para evitar posibles duplicados. La reversión de la migración elimina las marcas de envío.

### Regla de pertenencia y traslado de clientes

**Empresa → Cliente → N vehículos → Historial de mantenimiento.** La empresa del cliente es la referencia. Crear un vehículo o una orden no permite elegir una empresa distinta ni asociar vehículos de otro cliente. Los servicios y mecánicos deben pertenecer a esa misma empresa.

El administrador de un taller solo consulta y gestiona sus clientes y vehículos. No puede trasladarlos mediante parámetros manipulados. Tampoco es posible apropiarse de una cuenta de otra empresa al invitar a un cliente con el mismo correo.

El **superadministrador** puede cambiar la empresa al editar un cliente. La actualización traslada, en una sola transacción, el cliente, todos sus vehículos, órdenes (incluidos registros con baja lógica), cuenta de cliente e invitaciones del portal. Se conservan fechas, importes, observaciones, textos históricos de servicios y fotografías. Se desvinculan las referencias a catálogo, mecánicos y usuarios registradores de otras empresas; se preservan los registradores superadministradores. No se envían correos por trasladar o reconciliar.

Si una placa ya existe en la empresa destino o hay órdenes cuyo cliente no corresponde al vehículo, se rechaza la operación completa sin aplicar un traslado parcial.

Para datos históricos cuya empresa no coincide con la del cliente:

```powershell
php artisan ownership:reconcile-customer 2
php artisan ownership:reconcile-customer 2 --apply
```

Sin `--apply` solo muestra el alcance. Con `--apply` alinea las asociaciones seguras con la empresa actual del cliente y deja un registro de auditoría en el log de Laravel. No cambia el propietario de un vehículo ni adivina a qué cliente corresponde una orden ambigua; esos casos requieren revisión.

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

---

## 6. Portal del Cliente (Flujo de Invitación y Acceso Exclusivo)

El sistema cuenta con un **Portal del Cliente** completamente aislado del menú administrativo del taller.

### Características del Portal:
1. **Invitación por Correo:** El administrador del taller puede habilitar el acceso al cliente desde el formulario de creación o enviarle la invitación desde la ficha del cliente.
2. **Activación Segura por Token:** El cliente recibe un correo (vía Gmail SMTP configurado) con enlace firmado único válido por 48 horas para definir su contraseña.
3. **Inicio de Sesión:** El cliente puede iniciar sesión ingresando su correo electrónico y su contraseña personal.
4. **Menú Exclusivo del Cliente (`/portal`):**
   - **Panel / Inicio:** Resumen de sus vehículos, alertas de mantenimiento y últimos servicios.
   - **Mis Vehículos:** Fichas de sus vehículos con opción de baja voluntaria sin revelar historial previo a terceros.
   - **Historial de Mantenimiento:** Distinción visual clara entre mantenimientos verificados por el taller y registros propios del cliente.
   - **Registrar Mantenimiento Propio:** Formulario para asentar mantenimientos manuales (`origen: cliente`, `no verificado por taller`) que actualizan el odómetro del vehículo.
   - **Mi Cuenta:** Edición de datos de contacto, cambio de contraseña y baja voluntaria de la cuenta.
5. **Aislamiento Estricto:** Un usuario con rol `customer` no puede acceder a las rutas del taller (`/dashboard`, `/staff`, `/companies`, `/customers`, etc.), recibiendo respuesta `403 Prohibido`.

### Inicio del administrador de empresa

El administrador de taller o concesionario (`company_admin`) inicia sesión directamente en `/company/dashboard`, incluso si había una URL pendiente de la sesión anterior. Las rutas generales `/` y `/dashboard` también lo redirigen a ese panel operativo. La aceptación de políticas sigue siendo obligatoria. El acceso del superadministrador, mecánicos y clientes conserva su comportamiento anterior.

### Avisos de mantenimiento por correo

- Las órdenes del taller/concesionario pueden crearse como **Abierta, En progreso, Completada o Cancelada**. Siempre se intenta notificar al propietario después de guardar la orden y sus evidencias.
- Cada cambio real de estado envía otro correo; editar notas u otros datos sin cambiar el estado no genera avisos duplicados.
- El destinatario es el correo de la ficha del cliente asociado a la orden. Se envía **CC a los administradores activos de esa misma empresa**, sin duplicar direcciones ni copiar a mecánicos o administradores de otras empresas.
- El correo incluye vehículo, placa, servicio, orden, fecha, estado actual y, cuando corresponda, estado anterior. Incluye un botón al historial privado del cliente, orientación para activar su acceso y versiones HTML y texto.
- Los registros personales del portal quedan excluidos de estos avisos y copias.
- Si falta un email válido del cliente, se guarda el mantenimiento y se muestra una advertencia. Si no hay administradores activos con email válido, se envía al cliente y se advierte que no hubo copia.
- El envío es inmediato, mediante el mailer configurado, y se realiza fuera de la transacción de guardado. Los errores de transporte se reportan al registro de errores y se muestran como advertencia sin perder el mantenimiento.
- Para entrega real, configura un mailer de envío (por ejemplo, `MAIL_MAILER=smtp`) y sus credenciales. `log` y `array` no entregan correos a buzones. `APP_URL` debe apuntar a la dirección pública de la aplicación para que el enlace funcione.
