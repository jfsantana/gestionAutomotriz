<?php

namespace Database\Seeders;

use App\Domain\Users\Models\PlatformPolicy;
use Illuminate\Database\Seeder;

class PlatformPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policyText = <<<'MARKDOWN'
### 1. Objeto y alcance de la plataforma

Esta plataforma es un sistema web de gestión vehicular y registro administrativo de mantenimientos, dirigido a talleres, concesionarios y usuarios autorizados. Su finalidad es **informativa, administrativa y operativa interna**, y no constituye por sí sola un registro público oficial, certificación estatal, peritaje mecánico, garantía comercial, ni documento legal de transferencia de propiedad.

### 2. Entorno cerrado por empresa

La plataforma opera bajo un esquema de **acceso restringido por empresa y por usuario autorizado**.

Cada taller o concesionario gestiona su propia información dentro de su entorno autorizado, sin acceso a la información operativa de otros talleres, concesionarios o clientes ajenos.

### 3. No compartición de información entre talleres, concesionarios y clientes

La plataforma **no comparte información operativa, administrativa o histórica entre talleres, concesionarios y clientes distintos**.

En consecuencia:

- un taller no puede visualizar información cargada por otro taller;
- un concesionario no puede visualizar información operativa de otro concesionario;
- un cliente no puede visualizar vehículos ni registros que no estén vinculados a su propia cuenta;
- no existe consulta cruzada entre empresas o terceros por simple coincidencia de placa.

Solo podrá existir acceso o entrega de información en los siguientes casos:

- autorización expresa del titular o responsable legitimado;
- requerimiento de autoridad competente;
- necesidad técnica mínima indispensable para la operación de la plataforma mediante proveedores tecnológicos.

### 4. Consulta limitada al propietario vinculado

El usuario cliente únicamente podrá consultar los vehículos que se encuentren **directamente vinculados a su propia cuenta** dentro del sistema.

La plataforma no permite que un usuario consulte información de vehículos ajenos, aunque conozca la placa, serial, marca, modelo u otros datos de identificación.

### 5. No consulta de historial por placa

La placa del vehículo se utiliza únicamente como identificador interno y operativo para registro y validación de existencia dentro de la plataforma.

**No se permite consultar historial de mantenimiento únicamente por placa**, ni se mostrarán datos de mantenimientos, costos, fotografías, observaciones, propietarios anteriores o datos de terceros por coincidencia de placa.

### 6. No transferencia de historial entre propietarios

Para esta versión del sistema, **el historial de mantenimiento no se transfiere entre propietarios**.

Cuando un vehículo sea dado de baja y posteriormente registrado nuevamente por otro usuario o propietario, el nuevo registro se considerará **independiente**, sin arrastre automático de historial previo, costos, fotografías, observaciones, vencimientos ni registros anteriores.

### 7. Baja de vehículo y nuevo registro

La baja de un vehículo dentro de la plataforma tiene como efecto la finalización de su vínculo con el usuario o propietario registrado en ese momento, de acuerdo con las reglas internas del sistema.

Si ese vehículo posteriormente es registrado por otro usuario, dicho registro iniciará como un **nuevo alta**, sin continuidad automática del historial anterior.

### 8. Acceso restringido y uso por roles

El acceso a la información se encuentra sujeto a **roles, permisos y controles de acceso**, aplicando el criterio de mínimo acceso necesario para cada función.

Cada usuario solo debe acceder a la información estrictamente necesaria para las tareas que le correspondan. Este enfoque es consistente con el principio de **least privilege** recomendado por NIST.

### 9. Responsabilidad del usuario sobre sus credenciales

El usuario es responsable de:

- mantener la confidencialidad de su usuario y contraseña;
- no compartir credenciales con terceros;
- cerrar sesión en dispositivos compartidos o de terceros;
- informar de inmediato cualquier acceso sospechoso, uso no autorizado o incidente de seguridad relacionado con su cuenta.

Toda acción realizada desde una cuenta autenticada podrá considerarse realizada por su titular, salvo prueba en contrario.

### 10. Veracidad y legitimidad de la información registrada

El usuario declara que la información que registra en la plataforma es verdadera, actualizada y que cuenta con legitimidad o autorización suficiente para cargarla.

Queda prohibido:

- registrar vehículos sin autorización;
- atribuirse propiedad, posesión o representación sin sustento;
- cargar datos falsos, inexactos o engañosos;
- intentar acceder a información de terceros sin autorización;
- utilizar la plataforma para fines distintos a los permitidos por estas condiciones.

### 11. Finalidad informativa y límites funcionales

La información mostrada en la plataforma tiene fines de apoyo administrativo, control interno y consulta informativa para usuarios autorizados.

La plataforma no garantiza que la información cargada por terceros sea exacta, completa o actualizada en todo momento, ya que parte del contenido depende del registro manual realizado por usuarios autorizados.

### 12. Seguridad de la información

La plataforma adopta medidas razonables de seguridad técnicas y organizativas para proteger la información almacenada y restringir accesos no autorizados.

Sin embargo, ningún sistema puede garantizar invulnerabilidad absoluta frente a eventos como:

- ataques informáticos;
- robo, filtración o reutilización indebida de credenciales;
- malware o compromiso del equipo del usuario;
- accesos ilícitos de terceros;
- fallas de internet o conectividad;
- incidentes en servicios de hosting, correo o infraestructura externa;
- eventos de fuerza mayor.

### 13. Limitación de responsabilidad

En la máxima medida permitida por la ley aplicable, el titular u operador de la plataforma no será responsable por:

- accesos no autorizados derivados de robo de credenciales, negligencia del usuario o ataques de terceros;
- interrupciones temporales del servicio;
- pérdida de datos ocasionada por terceros, internet, proveedores tecnológicos o eventos de fuerza mayor;
- decisiones comerciales, técnicas, administrativas o legales tomadas por los usuarios con base en la información visible en la plataforma;
- daños indirectos, incidentales, consecuenciales, lucro cesante o pérdida de oportunidad.

Nada de lo anterior limitará aquellas responsabilidades que legalmente no puedan excluirse.

### 14. Suspensión, restricción o cancelación de cuentas

La plataforma podrá suspender, restringir o cancelar cuentas cuando detecte o sospeche razonablemente:

- uso indebido del sistema;
- intento de acceso no autorizado;
- suplantación de identidad;
- registro fraudulento de vehículos;
- carga de información falsa;
- incumplimiento de estas condiciones;
- conductas que comprometan la seguridad, integridad o disponibilidad de la plataforma.

### 15. Conservación y tratamiento básico de datos

La plataforma tratará únicamente los datos necesarios para:

- autenticar usuarios;
- operar las funcionalidades habilitadas;
- registrar mantenimientos y relaciones permitidas por el sistema;
- mantener trazabilidad interna;
- evidenciar la aceptación de estas políticas.

### 16. Cambios en estas políticas

El operador de la plataforma podrá actualizar estas políticas en cualquier momento.

Cuando exista una nueva versión vigente, el usuario deberá aceptarla nuevamente antes de continuar utilizando la aplicación.

### 17. Aceptación

Al marcar la casilla de aceptación y continuar, el usuario declara que:

- ha leído estas políticas;
- entiende su alcance y limitaciones;
- acepta sus condiciones;
- se compromete a utilizar la plataforma de forma lícita, responsable y autorizada.

### 18. Jurisdicción y ley aplicable

Estas condiciones se regirán por las leyes aplicables de la República Dominicana, y cualquier controversia será sometida a la jurisdicción de los tribunales correspondientes, salvo disposición legal imperativa en contrario.

### 19. Aviso importante

Este documento constituye una base operativa y contractual inicial y debe ser revisado por asesoría legal antes de su puesta en producción definitiva.
MARKDOWN;

        PlatformPolicy::updateOrCreate(
            ['version' => '1.0'],
            [
                'title' => 'Políticas de Uso y Condiciones de la Plataforma',
                'content' => $policyText,
                'effective_date' => now()->toDateString(),
                'is_active' => true,
            ]
        );
    }
}
