# AutoBitacora — Gestión de Mantenimiento Automotriz

Aplicación web multiempresa para gestionar clientes, vehículos, servicios, órdenes de mantenimiento y próximos vencimientos de talleres y concesionarios. Incluye administración de suscripciones SaaS y un portal independiente para propietarios de vehículos.

**Estado:** aplicación funcional en pruebas. Este documento describe las funcionalidades implementadas; no constituye una certificación de producción.

> **¿Vas a utilizar el sistema?** Consulta el [Manual de usuario](Documentacion/Manual-de-Usuario.md). Este README está dirigido principalmente a quienes instalan, mantienen o colaboran en el repositorio.

## Contenido

- [Funcionalidades](#funcionalidades)
- [Perfiles y alcance](#perfiles-y-alcance)
- [Tecnologías y requisitos](#tecnologías-y-requisitos)
- [Instalación local](#instalación-local)
- [Correo y tareas programadas](#correo-y-tareas-programadas)
- [Integridad y operaciones sensibles](#integridad-y-operaciones-sensibles)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Pruebas y mantenimiento](#pruebas-y-mantenimiento)
- [Colaboración en GitHub](#colaboración-en-github)

## Funcionalidades

| Área | Funciones disponibles |
|---|---|
| Empresas y usuarios | Empresas/talleres, administradores de empresa, personal operativo y activación/desactivación de accesos. |
| Clientes | Particulares y propietarios de flota, datos de contacto, documentos, ficha e invitación al portal. |
| Vehículos | Inventario, relación con el propietario, fotografía, kilometraje e historial de mantenimiento. |
| Servicios | Catálogo preventivo/correctivo, intervalos sugeridos y estado activo/inactivo. |
| Mantenimientos | Órdenes, responsable, estados, costos, observaciones, evidencias fotográficas y próximos controles. |
| Recordatorios | Evaluación por fecha y kilometraje, filtros, resumen operativo y avisos por correo. |
| Suscripciones | Planes, asignación a empresas, vigencia, suspensión, reactivación, renovación manual y auditoría. |
| Portal del cliente | Activación por invitación, consulta de vehículos e historial, servicios propios y gestión de cuenta. |
| Identidad y navegación | Logo y paleta del taller, iconos compartidos entre menú y encabezados, barra superior persistente. |
| Uso móvil | Tarjetas compactas en los listados administrativos adaptados y en los historiales de las fichas del taller; tablas en escritorio. |

### Alcance actual

- Los precios y costos registrados **no representan un cobro electrónico**. No se documenta una pasarela de pagos, facturación fiscal ni conciliación bancaria.
- Los límites de mecánicos y órdenes de los planes se presentan como **límites sugeridos**; no hay un bloqueo automático por cuota implementado en los flujos actuales.
- La renovación desde la interfaz es manual. No debe interpretarse un atributo de renovación como una integración de cobro recurrente.
- Los mantenimientos propios del cliente se distinguen de los realizados en el taller y se muestran como **no verificados por el taller**.
- Un registro aceptado por el transporte de correo no acredita recepción en la bandeja de entrada.

## Perfiles y alcance

| Perfil | Identificador | Alcance principal |
|---|---|---|
| Superadministrador | `super_admin` | Administración global de empresas, usuarios, planes y suscripciones; acceso operativo multiempresa. |
| Administrador de empresa | `company_admin` | Clientes, vehículos, catálogo, personal, órdenes, recordatorios e identidad de su propia empresa. |
| Mecánico | `mechanic` | Órdenes y consulta de recordatorios de su empresa; sin administración de clientes, vehículos, catálogo o personal. |
| Cliente | `customer` | Portal privado con sus vehículos, historial, registros personales y cuenta. |

El acceso depende también del estado del usuario, de la empresa/suscripción y de la aceptación de políticas.

La relación operativa es **Empresa → Cliente → Vehículos → Mantenimientos**. Las consultas operativas comprueban la coherencia de estas asociaciones. Un cliente no accede al panel del taller y el personal sin una empresa válida no obtiene acceso global.

**Excepción importante:** el envío manual de recordatorios por correo corresponde únicamente al administrador activo de la empresa propietaria. No está habilitado para superadministradores, mecánicos ni clientes.

## Tecnologías y requisitos

| Componente | Referencia del proyecto |
|---|---|
| Backend | Laravel 13, PHP y Eloquent. |
| PHP | Entorno de trabajo actual: PHP 8.5. La restricción raíz es `^8.3`; la compatibilidad efectiva también depende de las dependencias bloqueadas. |
| Dependencias PHP | Composer 2; versiones reproducibles en [composer.lock](composer.lock). |
| Base de datos | MySQL/MariaDB para el entorno de trabajo; pruebas configuradas con SQLite en memoria. |
| Interfaz | Blade y Bootstrap 5.3.3 servido por CDN en los layouts operativos. |
| Herramientas frontend | Vite 8 y Tailwind CSS 4 declarados en [package.json](package.json). No sustituyen el estilo Bootstrap de las vistas operativas. |
| Pruebas y formato | PHPUnit 12 y Laravel Pint. |

Necesitas:

- PHP y Composer disponibles en la terminal.
- Un motor de base de datos y una base creada para la aplicación.
- Extensiones PHP compatibles con las dependencias; `pdo_mysql` para MySQL y `pdo_sqlite` para las pruebas.
- Permisos de escritura en [storage](storage/) y [bootstrap/cache](bootstrap/cache/).
- Acceso a los recursos CDN y, según configuración, a SMTP y Cloudflare Turnstile.
- Node.js/npm compatibles con Vite 8 si vas a compilar los recursos frontend.

Verificación inicial:

```powershell
php -v
composer -V
```

## Instalación local

Los ejemplos de terminal utilizan PowerShell. Ajusta las rutas a tu equipo.

### 1. Obtener el código e instalar dependencias

```powershell
git clone https://github.com/jfsantana/gestionAutomotriz.git
Set-Location gestionAutomotriz
composer install
composer check-platform-reqs
```

Usa `composer install`, no `composer update`, para respetar las versiones del repositorio.

### 2. Configurar el entorno

Solo si aún no tienes configuración local:

```powershell
Copy-Item .env.example .env
```

El ejemplo del repositorio utiliza SQLite. Para el entorno MySQL/MariaDB, configura tu archivo local con valores propios:

```dotenv
APP_NAME=AutoBitacora
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_automotriz
DB_USERNAME=usuario_de_aplicacion
DB_PASSWORD=REEMPLAZAR_LOCALMENTE

MAIL_MAILER=log
```

- Crea previamente la base indicada y concede al usuario los permisos necesarios.
- `APP_URL` debe coincidir con la dirección real utilizada por los usuarios; es esencial para los enlaces de invitación y correo.
- Configura tus propias claves `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET_KEY`, con el dominio autorizado. No reutilices claves incluidas en archivos de ejemplo para una instalación compartida.
- No publiques el archivo local de entorno ni sus valores en commits, issues o capturas.
- La zona general de la aplicación está fijada en UTC en [config/app.php](config/app.php). Los recordatorios por email programan expresamente la zona `America/Caracas`; añadir una variable `APP_TIMEZONE` por sí sola no cambia esa configuración.

### 3. Inicializar la instalación

En una base nueva:

```powershell
php artisan key:generate
php artisan migrate
php artisan storage:link
```

**No regeneres la clave de una instalación existente.** Conserva una copia protegida de la clave y de la configuración.

Para preparar una instalación **local de pruebas**:

```powershell
php artisan db:seed
```

El [DatabaseSeeder](database/seeders/DatabaseSeeder.php) crea la cuenta inicial de superadministración y la política de plataforma.

> **Precaución:** el [SuperAdminSeeder](database/seeders/SuperAdminSeeder.php) contiene credenciales predefinidas y utiliza `updateOrCreate`; volver a ejecutarlo puede restablecer los datos de esa cuenta. No lo ejecutes sin revisión en un entorno compartido o de producción. Las credenciales deben entregarse por un canal privado y sustituirse mediante un procedimiento administrativo seguro antes de publicar el servicio. Este README no las reproduce.

### 4. Recursos frontend

Si vas a trabajar con los recursos de Vite:

```powershell
npm install
npm run build
```

Para desarrollo continuo, utiliza `npm run dev` en otra terminal. Las vistas administrativas actuales cargan Bootstrap desde CDN: modificar sus clases Blade no exige recompilar Bootstrap.

### 5. Iniciar el servidor

Alternativa de desarrollo:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Abre la dirección que muestra Artisan. El acceso de usuarios comienza en `/login`.

En WAMP/Apache:

1. Configura el VirtualHost con raíz pública en `C:\wamp64\www\gestionAutomotriz\public`.
2. Habilita la reescritura de URL y permite que se aplique la configuración de [public/.htaccess](public/.htaccess).
3. Configura el nombre local en el archivo `hosts`, si lo necesitas.
4. Ajusta `APP_URL` y reinicia los servicios cuando corresponda.

**No expongas la raíz del repositorio como DocumentRoot.** Solo [public](public/) debe ser accesible como raíz web.

### 6. Primera configuración funcional

Con la cuenta de superadministración:

1. Acepta las políticas vigentes.
2. Registra la empresa/taller.
3. Define el plan y asigna su suscripción.
4. Crea un administrador asociado a esa empresa.
5. Con el administrador del taller, registra personal y servicios.
6. Registra un cliente, su vehículo y una primera orden de prueba.

El procedimiento de usuario está desarrollado en el [manual](Documentacion/Manual-de-Usuario.md).

## Correo y tareas programadas

### Configuración de entrega

Configura un transporte real, por ejemplo SMTP, mediante `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME`, según tu proveedor y [config/mail.php](config/mail.php).

Los transportes `log` y `array` **no entregan mensajes a buzones**. No publiques credenciales SMTP. Después de modificar configuración cacheada, utiliza el procedimiento de limpieza/reconstrucción de caché de tu entorno.

Los envíos actuales de invitaciones, avisos y recordatorios son síncronos; no necesitan un trabajador de colas para esas operaciones.

### Tipos de correo

| Evento | Comportamiento |
|---|---|
| Invitación al portal | Enlace con token de activación, de un solo uso y vigencia de 48 horas. No depende de un proveedor SMTP específico. |
| Creación de orden del taller | Intenta avisar al propietario y copiar a los administradores activos de esa empresa con correo válido. |
| Cambio real de estado | Envía un nuevo aviso. Editar otros datos sin cambiar el estado no genera ese aviso. |
| Recordatorio automático | Dos días calendario antes de la fecha de vencimiento, a las 08:00 de Caracas. |
| Recordatorio manual | Desde la lista de recordatorios, por un administrador activo autorizado; admite vencidos y controles solo por kilometraje. |

Los avisos de órdenes no incluyen los registros personales del portal. Si el correo de una orden no puede enviarse, se conserva el mantenimiento y se informa la advertencia. Los recordatorios no llevan copias ni constituyen una confirmación de cita.

### Programador

La tarea definida en [routes/console.php](routes/console.php) ejecuta `maintenance:send-reminders` a las **08:00 en `America/Caracas`**, evitando solapamientos.

En el Programador de tareas de Windows:

- Programa una ejecución **cada minuto**.
- Programa/script: ruta absoluta al ejecutable de PHP.
- Argumentos: `artisan schedule:run`.
- Directorio de inicio: raíz de esta instalación.
- Usa una cuenta con permisos para ejecutar PHP y escribir en los registros.

Comprobación de la programación:

```powershell
php artisan schedule:list
```

Para desarrollo, `php artisan schedule:work` mantiene el programador en primer plano.

### Comandos operativos

| Comando | Efecto |
|---|---|
| `php artisan maintenance:process-reminders` | Evalúa y muestra los recordatorios; no envía correos. |
| `php artisan maintenance:send-reminders` | Envía los avisos elegibles para vencimientos dentro de dos días. |
| `php artisan maintenance:send-reminders --company=1` | Limita el envío a la empresa indicada. |
| `php artisan subscriptions:process-expired` | Actualiza a vencidas las suscripciones activas o de prueba cuya vigencia terminó. Modifica datos. |

`subscriptions:process-expired` **no está programado automáticamente** en la configuración actual. Si se requiere esa actualización periódica, el responsable técnico debe programarla expresamente. El control de acceso también evalúa la vigencia al utilizar los módulos.

Los envíos manuales y automáticos comparten el máximo de **un correo por mantenimiento y día de Caracas**, incluso si se modifica el vencimiento. El transporte debe aceptar el mensaje antes de guardar `reminder_sent_at`. En un fallo ambiguo de SMTP o una interrupción posterior a la aceptación, consulta al proveedor antes de reintentar: la marca local no garantiza una entrega exactamente una vez.

Los envíos automáticos exigen una orden completada del taller, empresa operativa, asociaciones coherentes, cliente/vehículo disponibles y correo válido. Los controles sin fecha no permiten calcular el aviso de dos días.

Si el servidor no estuvo disponible a la hora programada, el comando puede ejecutarse manualmente ese mismo día; no recupera avisos atrasados de otros días.

## Integridad y operaciones sensibles

### Traslado de clientes

Solo el superadministrador puede cambiar la empresa de un cliente desde su edición. El traslado incluye sus vehículos, órdenes —también con baja lógica—, cuenta e invitaciones del portal, dentro de una transacción.

Se conservan fechas, costos, observaciones, fotografías y textos históricos. Se desvinculan referencias a servicios, mecánicos y registradores de otras empresas, conservando los registradores superadministradores. El traslado no envía correos.

Si hay conflictos de placas en destino o asociaciones ambiguas entre órdenes y propietarios, se rechaza el traslado completo.

Para reconciliar datos históricos con la empresa actual de un cliente, comienza por la simulación:

```powershell
php artisan ownership:reconcile-customer 2
```

Solo después de revisar el alcance y contar con respaldo:

```powershell
php artisan ownership:reconcile-customer 2 --apply
```

Sustituye `2` por el ID real. Sin `--apply` no modifica datos. La aplicación no adivina propietarios ni corrige asociaciones ambiguas; esas situaciones requieren revisión.

### Baja de vehículos y cuentas

- **Dar de Baja (Liberar Placa), en la ficha administrativa:** elimina definitivamente el vehículo y sus órdenes; elimina también los registros de evidencias asociados. No es una simple desactivación.
- **Eliminar Vehículo (Soft Delete):** utiliza baja lógica. No equivale a liberar la placa mediante la acción anterior.
- **Baja desde el portal del cliente:** marca el vehículo como dado de baja y aplica baja lógica al vehículo y a sus órdenes. No transfiere su historial al comprador.
- **Baja de cuenta del cliente:** desactiva su acceso y cierra la sesión; no debe describirse como borrado completo de todos sus datos.

No hay un procedimiento de recuperación desde la interfaz documentado para estas bajas. La eliminación de registros tampoco debe interpretarse como garantía de borrado físico de todos los archivos o copias de respaldo.

## Estructura del proyecto

| Ubicación | Responsabilidad |
|---|---|
| [app/Domain](app/Domain/) | Modelos y organización por compañías, usuarios, vehículos, mantenimiento, recordatorios y suscripciones. |
| [app/Http](app/Http/) | Controladores, validación y middleware de acceso. |
| [app/Services](app/Services/) | Pertenencia de clientes, suscripciones, notificaciones, recordatorios y diagnóstico. |
| [app/Console/Commands](app/Console/Commands/) | Procesos operativos ejecutables con Artisan. |
| [resources/views](resources/views/) | Pantallas Blade administrativas y portal del cliente. |
| [resources/views/components](resources/views/components/) | Menú, barra, mensajes, iconos compartidos y encabezados. |
| [database](database/) | Migraciones, factories y seeders. |
| [routes](routes/) | Rutas web y programación de tareas. |
| [tests](tests/) | Pruebas automatizadas. |
| [Documentacion](Documentacion/) | Manual de usuario. |

## Pruebas y mantenimiento

### Pruebas

[phpunit.xml](phpunit.xml) configura SQLite en memoria y correo de prueba. Verifica que `pdo_sqlite` esté disponible y no utilices una base de producción para ejecutar pruebas.

```powershell
php artisan test --compact
```

Para una cobertura específica:

```powershell
php artisan test --compact tests\Feature\StaffManagementTest.php
php artisan test --compact tests\Feature\SectionHeadingTest.php
```

Herramientas de revisión:

```powershell
php vendor\bin\pint --dirty --format agent
php artisan view:cache
git diff --check
```

La validación visual móvil es complementaria: una prueba de respuesta HTTP no demuestra por sí sola ausencia de desplazamiento horizontal.

### Diagnóstico y soporte

- `/health`: diagnóstico JSON público; devuelve HTTP 200 cuando está correcto y 503 cuando detecta problemas.
- `/up`: comprobación de salud incorporada por Laravel.
- Consulta [storage/logs](storage/logs/) y el canal de registro configurado.
- La tarea automática escribe su salida en `storage\logs\maintenance-reminders.log`.
- Para cambios de vistas que no se reflejen, considera `php artisan view:clear` y una recarga forzada del navegador.
- Para cambios de configuración local no reflejados, utiliza `php artisan config:clear`.

Antes de publicar un incidente, elimina datos personales, credenciales, tokens de invitación y contenidos sensibles de los registros.

### Antes de exponer una instalación

- Configura HTTPS, `APP_DEBUG=false` y una URL pública correcta.
- Revisa las credenciales iniciales y las claves de los servicios externos.
- Restringe el acceso administrativo y evalúa la exposición del diagnóstico público.
- Respalda base de datos, archivos cargados y configuración protegida; prueba la restauración.
- Configura y verifica SMTP y el programador.
- Revisa las migraciones antes de aplicarlas sobre datos existentes.
- Confirma permisos de escritura y raíz pública del servidor.
- Valida los flujos reales de cada perfil con datos de prueba, sin enviar correos a clientes reales por accidente.

## Colaboración en GitHub

1. Abre un issue que describa el problema o alcance funcional.
2. Trabaja en una rama específica y mantén el cambio acotado.
3. No alteres el aislamiento entre empresas ni los permisos como parte de un ajuste visual.
4. Añade pruebas cuando cambie comportamiento; ejecuta las pruebas afectadas y el formato.
5. Para cambios de interfaz, comprueba móvil y escritorio, textos largos, estados vacíos y las acciones existentes.
6. Actualiza este README y el [manual](Documentacion/Manual-de-Usuario.md) cuando cambien procedimientos o pantallas.
7. En la solicitud de cambios, indica qué cambió, cómo se probó y si requiere migraciones o configuración.

**No incluir en GitHub:** archivos de entorno privados, contraseñas, claves, tokens, respaldos, fotos de clientes, datos personales ni registros sin anonimizar.
