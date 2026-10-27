# Manual de usuario — AutoBitacora

**Sistema de Gestión de Mantenimiento Automotriz**  
**Edición:** 26 de octubre de 2026  
**Destinatarios:** superadministradores, administradores de taller, mecánicos y clientes.

Este manual describe las pantallas y operaciones implementadas. Los módulos visibles dependen del perfil, la empresa, el estado de la cuenta y la suscripción.

Para instalación, configuración de correo y mantenimiento técnico, consulta el [README del proyecto](../README.md).

## Índice

1. [Conceptos y perfiles](#1-conceptos-y-perfiles)
2. [Acceso y políticas](#2-acceso-y-políticas)
3. [Navegación y uso móvil](#3-navegación-y-uso-móvil)
4. [Puesta en marcha de un taller](#4-puesta-en-marcha-de-un-taller)
5. [Clientes y propietarios](#5-clientes-y-propietarios)
6. [Vehículos y sus fichas](#6-vehículos-y-sus-fichas)
7. [Catálogo de servicios](#7-catálogo-de-servicios)
8. [Órdenes de mantenimiento](#8-órdenes-de-mantenimiento)
9. [Recordatorios de mantenimiento](#9-recordatorios-de-mantenimiento)
10. [Personal operativo](#10-personal-operativo)
11. [Identidad del taller](#11-identidad-del-taller)
12. [Empresas y administradores](#12-empresas-y-administradores)
13. [Planes y suscripciones](#13-planes-y-suscripciones)
14. [Portal del cliente](#14-portal-del-cliente)
15. [Rutina recomendada](#15-rutina-recomendada)
16. [Problemas frecuentes](#16-problemas-frecuentes)
17. [Precauciones y glosario](#17-precauciones-y-glosario)

## 1. Conceptos y perfiles

### Organización de la información

Una **empresa** representa al taller o concesionario. A ella pertenecen sus clientes; cada cliente puede tener varios vehículos, y cada vehículo tiene sus mantenimientos.

**Empresa → Cliente → Uno o varios vehículos → Historial**

No selecciones clientes, vehículos, mecánicos o servicios de empresas distintas para una misma operación. El sistema comprueba esas relaciones.

### Qué puede hacer cada perfil

| Función | Superadministrador | Administrador de empresa | Mecánico | Cliente |
|---|---|---|---|---|
| Empresas y administradores | Gestión global | No | No | No |
| Planes y suscripciones SaaS | Gestión global | Consulta de su estado de cuenta | Sin gestión comercial | No |
| Clientes, vehículos y servicios | Gestión global | Su empresa | Sin módulos de gestión directa | Sus vehículos e historial en el portal |
| Personal operativo | Gestión global | Su empresa | No | No |
| Órdenes de mantenimiento | Globales | Su empresa | Su empresa | Consulta y registros propios en el portal |
| Consulta de recordatorios | Global | Su empresa | Su empresa | Alertas de sus vehículos en el portal |
| Envío manual de recordatorios | No | Sí, si está activo y autorizado | No | No |
| Identidad y logo del taller | Gestión de empresas por su módulo | Perfil de su taller | No | No |

Las restricciones de vigencia y de aceptación de políticas siguen aplicándose. Un botón ausente puede ser consecuencia de los permisos, no un fallo de la interfaz.

## 2. Acceso y políticas

### Iniciar sesión

1. Abre la dirección proporcionada por el responsable del sistema.
2. En **Usuario**, escribe tu nombre de usuario o correo electrónico.
3. Introduce tu contraseña.
4. Completa la verificación de seguridad si aparece.
5. Usa **Recordar mi sesión** únicamente en un dispositivo personal y confiable.
6. Pulsa **Iniciar Sesión**.

El administrador de empresa entra al panel operativo del taller. El cliente entra a su portal. Los demás perfiles acceden al panel que les corresponda.

Una cuenta invitada al portal debe activarse antes de iniciar sesión. Las cuentas desactivadas o bloqueadas no pueden acceder.

### Aceptar las políticas

Si hay una política vigente pendiente:

1. Lee el documento mostrado.
2. Desplázate hasta el final.
3. Marca la aceptación cuando se habilite.
4. Confirma para continuar.

Una nueva versión puede requerir otra aceptación. En el panel administrativo, **Políticas y Condiciones**, al pie del menú, permite volver a consultar el documento y los datos de aceptación disponibles.

### Cerrar sesión

En el panel del taller, abre el menú de usuario de la barra superior y elige **Cerrar Sesión**. En el portal del cliente hay un botón de cierre en la barra superior.

No basta con cerrar la pestaña si el equipo es compartido.

## 3. Navegación y uso móvil

### Identificación visual

Los encabezados utilizan el mismo icono de la sección del menú:

| Icono | Sección |
|---|---|
| 📊 | Panel de control / resumen |
| 👥 | Clientes y propietarios |
| 🚗 | Vehículos |
| 🔧 | Catálogo de servicios |
| 📝 | Órdenes e historial de mantenimiento |
| 🔔 | Recordatorios |
| 🎨 | Identidad y logo |
| 👨‍🔧 | Personal operativo |
| 🏢 | Empresas y talleres |
| 👤 | Administradores de empresa / cuenta del cliente |
| 💼 | Planes de suscripción |
| 💳 | Suscripciones y estado de cuenta |
| 📜 | Políticas y condiciones |
| ➕ | Registro de servicio propio en el portal |

El icono se mantiene en las pantallas de consulta, creación o edición de cada sección. Su aspecto puede variar ligeramente según el dispositivo.

### Menú y barra superior

- La barra superior oscura identifica la aplicación y, cuando corresponde a la configuración, la empresa y su logo.
- En escritorio, el botón de menú permite minimizar o expandir el panel lateral.
- En móvil, abre el menú con ese botón. Toca el fondo exterior para cerrarlo.
- El menú activo ayuda a reconocer la sección actual.

### Listas en tarjetas

En pantallas estrechas —menos de 768 píxeles— los listados administrativos adaptados aparecen como tarjetas en lugar de tablas. Cada tarjeta reúne los datos y sus botones; no necesitas buscar las acciones desplazándote horizontalmente.

Esto incluye clientes, inventario de vehículos, servicios, órdenes, recordatorios, empresas, administradores, personal, planes y suscripciones, además de vehículos registrados en la ficha del cliente e historial en la ficha administrativa del vehículo.

En escritorio se mantienen las tablas. La paginación sigue estando al final de la lista. Esta adaptación no implica que todas las tablas del portal del cliente tengan el mismo formato.

### Formularios y mensajes

- Los campos con asterisco deben completarse.
- Si un campo es rechazado, lee el mensaje junto a él, corrige el valor y vuelve a guardar.
- **Ver** o **Ver Ficha** abre la información; **Editar** permite modificarla.
- Una advertencia de correo no siempre significa que el registro falló. Comprueba la ficha antes de repetir una operación.
- No te guíes solo por los colores: lee también el texto del estado.

## 4. Puesta en marcha de un taller

### Responsable de plataforma

1. Crea la empresa en **Empresas y Talleres**.
2. Define un plan en **Planes de Suscripción**.
3. Asigna su vigencia en **Suscripciones por Empresa**.
4. Crea el usuario de **Administradores de Empresa** y vincúlalo al taller.
5. Entrega la dirección y las credenciales de acceso por un canal privado.

### Administrador del taller

1. Inicia sesión y acepta las políticas.
2. Revisa **Identidad y Logo** y los datos de contacto.
3. Registra los mecánicos en **Personal Operativo**.
4. Crea los servicios habituales en **Catálogo de Servicios**.
5. Registra clientes y vehículos.
6. Comienza a registrar órdenes.

**Ejemplo de recorrido:** registrar a un propietario → añadir su vehículo → registrar un cambio de aceite → completar la orden → fijar próximo vencimiento → revisar Recordatorios.

Los datos del ejemplo deben introducirse como prueba; no uses correos de personas reales para ensayar notificaciones.

## 5. Clientes y propietarios

### Registrar un cliente

1. Entra en **Clientes**.
2. Pulsa **+ Nuevo Cliente**.
3. Completa nombre o razón social y tipo de cliente:
   - **Particular (Individual)**.
   - **Propietario de Flota**.
4. Añade correo, teléfono, documento y observaciones cuando estén disponibles.
5. Si eres superadministrador, selecciona la empresa correspondiente.
6. Guarda. Se abrirá la ficha del cliente para continuar con sus vehículos.

El administrador de empresa trabaja siempre con su propio taller.

### Ficha del cliente

En **Ver Ficha** puedes consultar:

- Datos personales, de contacto y documento.
- Estado de acceso al portal.
- Empresa asociada y notas.
- Vehículos registrados y alertas disponibles.

Usa **Editar Cliente** para corregir datos y **+ Agregar Vehículo** para incorporar otra unidad del mismo propietario. En móvil, los vehículos de la ficha aparecen como tarjetas con **Ver** y **Editar**.

### Invitar al portal

1. Comprueba que el cliente tenga un correo válido y propio.
2. En su ficha, busca el bloque de acceso al portal.
3. Pulsa **Habilitar y Enviar Invitación**.
4. Si aparece **Invitación Pendiente**, puedes usar **Reenviar Invitación** cuando sea necesario.
5. Pide al cliente que revise también correo no deseado.

El formulario de creación también permite solicitar acceso al portal. La invitación tiene una vigencia de **48 horas** y el cliente define su propia contraseña.

Una cuenta ya asociada a otra empresa o cliente no debe reutilizarse. Si aparece un conflicto de correo, solicita revisión; no cambies datos para eludirlo.

### Trasladar un cliente a otra empresa

**Solo superadministración.** Cambiar la empresa en la edición traslada conjuntamente al cliente, sus vehículos, historial y vínculos del portal.

No es un cambio aislado del nombre del taller. Antes de guardar:

- Confirma la autorización del traslado.
- Revisa la empresa destino.
- Solicita respaldo y revisión técnica si hay datos históricos inconsistentes.

Se conservan los datos históricos, pero se desvinculan referencias a personal y servicios de la empresa anterior. Un conflicto de placas o una asociación ambigua puede impedir la operación completa.

## 6. Vehículos y sus fichas

### Registrar un vehículo

El recorrido recomendado es **Clientes → Ver Ficha → + Agregar Vehículo**.

1. Verifica al propietario mostrado.
2. Completa placa, marca y modelo.
3. Añade VIN/chasis, año, color, kilometraje y combustible.
4. Adjunta una fotografía si corresponde.
5. Registra notas útiles y guarda.

Un cliente puede tener varios vehículos. Si una placa ya está registrada, no alteres la matrícula para forzar el alta: consulta al responsable del taller y verifica si corresponde una baja previa.

### Inventario y ficha

En **Vehículos** puedes consultar el inventario, entrar al propietario asociado y usar **Ver** o **Editar**.

La ficha del vehículo reúne:

- Especificaciones y fotografía.
- Propietario.
- Control de próximo mantenimiento, cuando corresponde.
- Historial de mantenimientos.
- Datos de auditoría.

Desde **Registrar Mantenimiento** o **Registrar Servicio** puedes iniciar una orden contextual. En móvil, el historial administrativo se presenta en tarjetas.

### Baja: operación sensible

> **Dar de Baja (Liberar Placa) en la ficha del taller elimina definitivamente el vehículo y sus órdenes de mantenimiento. No lo utilices para corregir un error de escritura, ocultar temporalmente una unidad o trasladar un cliente.**

La acción permite un nuevo registro limpio sin transferir el historial privado anterior. Requiere confirmación y no dispone de recuperación desde la interfaz.

**Eliminar Vehículo (Soft Delete)** es una baja lógica distinta. No equivale a la eliminación definitiva anterior.

Antes de cualquiera de estas operaciones, confirma la autorización del propietario y la política de conservación del taller. La baja realizada por el cliente en su portal tiene un procedimiento diferente, descrito en la sección 14.

## 7. Catálogo de servicios

1. Abre **Servicios** y pulsa **+ Nuevo Servicio**.
2. Introduce el nombre y una descripción, si corresponde.
3. Selecciona mantenimiento **Preventivo** o **Correctivo**.
4. Define intervalos sugeridos por días y/o kilometraje, cuando apliquen.
5. Guarda y verifica el servicio en el catálogo.

En el listado puedes **Editar**, **Activar** o **Desactivar**.

Los intervalos son referencias para planificar controles; revisa siempre el próximo vencimiento de cada orden. Si no hay intervalo configurado, se muestra **A criterio de uso**.

Desactivar un servicio no borra los textos históricos de las órdenes donde se utilizó.

## 8. Órdenes de mantenimiento

### Registrar una orden

1. Entra en **Mantenimientos → + Nueva Orden**, o comienza desde la ficha de un vehículo.
2. Selecciona propietario y vehículo, comprobando que correspondan.
3. Elige un servicio del catálogo o registra un concepto personalizado.
4. Selecciona tipo de mantenimiento y mecánico responsable, si aplica.
5. Completa fecha del servicio y kilometraje de entrada.
6. Registra el costo si está disponible.
7. Selecciona el estado.
8. Define el próximo vencimiento por fecha y/o kilometraje si quieres darle seguimiento.
9. Separa las observaciones técnicas internas de las observaciones destinadas al cliente.
10. Adjunta evidencias fotográficas cuando sean necesarias y guarda.

Las fotografías de órdenes admiten hasta **5 MB por imagen** según la validación actual. El servidor puede imponer límites adicionales a la carga total.

El costo se muestra con el símbolo `$`; no implica emisión de factura ni cobro en línea.

### Estados

| Estado | Uso operativo |
|---|---|
| Abierta | Orden registrada pendiente de ejecución. |
| En Progreso | Trabajo en curso. |
| Completada | Trabajo terminado; puede evaluarse para próximos controles si tiene programación. |
| Cancelada | Orden que no continúa como trabajo activo. |

Para actualizar, entra en **Editar**, cambia los datos necesarios y guarda. Confirma que el estado final refleje lo sucedido.

Registrar un kilometraje mayor al actual del vehículo actualiza el odómetro almacenado. No inventes un valor para activar o desactivar alertas.

### Origen del mantenimiento

- **Realizado en Taller:** registro del entorno operativo.
- **Cliente (Fuera de taller):** registro personal del propietario; aparece como **No verificado por taller**.

No interpretes un registro personal como una certificación del taller.

### Notificaciones

Al crear una orden del taller se intenta enviar un aviso al cliente. Un cambio real de estado genera otro aviso; editar únicamente notas u otros datos no genera ese correo de cambio de estado.

Se copia a los administradores activos de la misma empresa con correo válido. Los registros personales del portal quedan fuera de estos avisos.

Si aparece una advertencia de envío, verifica primero si la orden quedó guardada. No dupliques el mantenimiento para intentar reenviar el correo. Informa al soporte si falta el destinatario o falla la entrega.

## 9. Recordatorios de mantenimiento

### Qué representan

Son controles asociados a órdenes completadas con próximo vencimiento. Se evalúan con la fecha y el kilometraje registrados; no existe lectura automática del odómetro.

| Estado | Criterio |
|---|---|
| Vencido | La fecha ya pasó o el kilometraje actual alcanzó/superó el objetivo. |
| Por Vencer | Faltan de 0 a 30 días o entre 1 y 1.000 km. La fecha de hoy aparece como **Vence hoy**. |
| Próximo | Está fuera de los umbrales anteriores. |
| Sin Programación | La orden completada no tiene próximo vencimiento. |

Si fecha y kilometraje indican situaciones diferentes, se toma la más urgente. Una orden sin programación no aparece como un recordatorio con control activo.

### Consultar y filtrar

1. Abre **Recordatorios**.
2. Revisa los resúmenes de vencidos, próximos y total con control.
3. Filtra por estado, placa o nombre del cliente.
4. Pulsa **Filtrar**; usa **Limpiar** para volver al conjunto sin esos filtros.
5. Revisa el último servicio, su fecha, el kilometraje y los vencimientos.

Acciones disponibles:

- **+ Servicio:** iniciar un nuevo mantenimiento del vehículo.
- **Vehículo:** consultar su ficha e historial.
- **Orden:** abrir el mantenimiento original.

### Enviar un recordatorio manual

Disponible únicamente para un **administrador activo de la empresa propietaria**:

1. Verifica el correo y los datos del cliente.
2. Pulsa **Enviar recordatorio**.
3. Comprueba el destinatario en la confirmación.
4. Confirma y espera el resultado.

El botón puede estar deshabilitado por falta de correo válido o porque ya se envió un aviso ese día. Se muestra el motivo y, cuando existe, el último envío en hora de Caracas.

El límite es **un correo por mantenimiento y día**, compartido con el proceso automático. No se elimina cambiando la fecha de vencimiento.

### Envío automático

Con el servicio de correo y el programador configurados:

- Se ejecuta a las **08:00, hora de Caracas**.
- Notifica exactamente **dos días calendario antes** del vencimiento por fecha.
- Exige una orden elegible del taller, empresa operativa y correo válido.
- Los vencimientos solo por kilometraje pueden notificarse manualmente, pero no permiten calcular el aviso automático de dos días.

Este correo es informativo: **no reserva ni confirma una cita**.

## 10. Personal operativo

Disponible para superadministradores y administradores de empresa.

1. Abre **Personal Operativo**.
2. Pulsa **+ Registrar Mecánico**.
3. Completa nombre, usuario, correo, contraseña y estado según el formulario.
4. Si eres superadministrador, verifica la empresa asignada.
5. Guarda y entrega las credenciales por un canal privado.

En el listado, **Editar** modifica los datos; **Desactivar** impide el acceso y **Activar** lo habilita nuevamente, sujeto a las demás condiciones de cuenta y empresa.

El administrador del taller no puede gestionar personal de otra empresa.

## 11. Identidad del taller

Como administrador de empresa:

1. Abre **Identidad y Logo**.
2. Revisa nombre comercial, correo, teléfono, WhatsApp, dirección y ubicación.
3. Carga el logo, si corresponde.
4. Selecciona una de las paletas disponibles.
5. Pulsa **Guardar Cambios de Identidad**.

Utiliza una imagen admitida por el formulario; el límite actual del logo es **4 MB**. Si un formato es rechazado, utiliza PNG o JPG.

El identificador de URL —slug— y el RNC/Tax ID están bloqueados en esta pantalla. Solicita su revisión al responsable de plataforma.

El correo y teléfono del taller deben estar actualizados porque forman parte de la información de contacto utilizada por el sistema.

## 12. Empresas y administradores

### Empresas y Talleres

**Solo superadministración.**

1. Abre **Empresas y Talleres → + Nueva Empresa**.
2. Registra nombre, identificador, datos fiscales, contacto y ubicación.
3. Revisa el estado y guarda.
4. Usa **Ver** para consultar la ficha y **Editar** para actualizarla.

La acción **Eliminar** solicita confirmación. No la utilices como sustituto de suspender temporalmente el servicio; para controlar el acceso comercial, gestiona la suscripción.

### Administradores de Empresa

1. Abre **Usuarios de Empresa / Administradores de Empresa**.
2. Pulsa **+ Nuevo Administrador**.
3. Completa nombre, usuario, correo, contraseña, empresa y estado.
4. Guarda y confirma la empresa asignada.

En el listado puedes **Ver** y **Editar**, y abrir la empresa asociada. Si aparece **Sin empresa**, revisa la asignación antes de entregar el acceso.

Cada persona debe tener una cuenta individual. No compartas una cuenta de superadministración para las operaciones diarias del taller.

## 13. Planes y suscripciones

### Planes de Suscripción SaaS

**Solo superadministración.**

1. Entra en **Planes de Suscripción → + Nuevo Plan**.
2. Define nombre, identificador, precios y tipo comercial o de prueba.
3. Indica duración de prueba cuando corresponda.
4. Define los límites sugeridos de mecánicos y órdenes mensuales; un valor vacío se representa como **Ilimitado**.
5. Guarda.

El listado permite **Editar**, **Activar** y **Desactivar**. También muestra la cantidad de suscripciones.

**Importante:** los límites se presentan como referencias comerciales; no hay un control automático por cuota implementado en estos flujos. Registrar precios no configura pagos ni facturación automática.

### Suscripciones por Empresa

1. Abre **Suscripciones Activas / Suscripciones por Empresa**.
2. Usa **+ Asignar Suscripción** o **Asignar Plan** en una empresa sin registro.
3. Selecciona empresa, plan, estado, ciclo y fecha de inicio.
4. Define la fecha final, si corresponde, y las notas.
5. Guarda y revisa **Estado**, **Vigencia** y **Acceso**.

| Estado | Interpretación |
|---|---|
| Activo | Permite operar si la vigencia no terminó. |
| Prueba (Trial) | Período de prueba, sujeto a vigencia. |
| Vencido | Requiere revisar y renovar la vigencia. |
| Suspendido | Acceso operativo suspendido. |
| Cancelado | Suscripción cancelada. |
| Sin registro | No existe una suscripción asociada visible. |

Existe una regla de compatibilidad: una empresa sin suscripción explícita puede operar si su estado empresarial es activo o de prueba. No uses esa excepción como procedimiento de alta; asigna una suscripción y revisa la situación con soporte si la etiqueta del listado no coincide con el acceso observado.

### Editar, suspender y renovar

- **Editar:** permite cambiar plan, estado, ciclo, fechas y notas, y consultar auditoría.
- **Suspender:** bloquea el acceso operativo.
- **Reactivar:** cambia el estado, pero no amplía una fecha ya vencida.
- **Renovar +1 Mes:** desde la edición, activa la suscripción y establece un período de un mes **desde el momento de la renovación**, no desde la fecha final anterior.

Antes de renovar una suscripción que todavía tiene días disponibles, verifica si corresponde ajustar las fechas manualmente en lugar de reemplazar el período con la acción rápida.

### Mi Suscripción

El administrador del taller puede consultar su estado de cuenta. Si el sistema redirige allí por bloqueo o vencimiento, contacta al responsable de plataforma; no crees cuentas nuevas para eludir la restricción.

No hay un cobro con tarjeta ni una renovación pagada automática documentados en la aplicación actual.

### Pagos de clientes Propietarios de Flota

Este plan es independiente de la suscripción del taller y lo administra AutoBitácora. El cliente recibe dos meses gratuitos desde su primer inicio de sesión exitoso; durante ese periodo puede consultar hasta tres vehículos. Al finalizar, los vehículos, sus historiales y el registro de servicios quedan ocultos hasta que se confirme una suscripción. La sección **Mis Vehículos** permanece disponible para registrar la solicitud de pago.

Al enviar una solicitud, se crea una fila con estado **Solicitud** en **Suscripciones SaaS → Pagos de Clientes** y se envía un correo al cliente y a cada superadministrador activo con email. El superadministrador contacta al cliente para coordinar el pago y solo confirma la solicitud en el panel cuando el pago se haya recibido. La solicitud por sí sola no cobra ni desbloquea vehículos.

En **Pagos de Clientes**, la acción **Ocultar** quita del reporte las solicitudes pendientes o reemplazadas mediante borrado lógico; el registro permanece en la base de datos. Una solicitud reemplazada también se puede volver a revisar: solo se confirma si los vehículos, sus tipos y el monto todavía coinciden. Los pagos confirmados no se pueden ocultar para conservar el historial financiero.

Un superadministrador puede **Revertir pago** en un pago confirmado. Esto registra quién y cuándo lo revirtió y retira el acceso de flota si ese pago corresponde a la suscripción vigente; revertir un pago antiguo no afecta una suscripción posterior. La acción no realiza un reembolso bancario automático.

Para que los correos lleguen fuera del entorno local, configura un proveedor de correo válido en las variables `MAIL_MAILER` y `MAIL_*` del entorno. Si no se configura, Laravel puede registrar los mensajes en el log en vez de enviarlos.

La tarifa mensual por vehículo depende del tipo de cliente y vehículo:

| Tipo de vehículo | Particular | Propietario de Flota |
|---|---:|---:|
| Motocicleta | USD 3 | USD 3 |
| Carro o camioneta | USD 3 | USD 4 |
| Van o autobús | USD 4 | USD 5 |
| Camión, chuto o maquinaria pesada | USD 5 | USD 5 |

El pago anual equivale a once mensualidades y cubre doce meses. Al vencer el periodo pagado, el acceso a los vehículos y servicios vuelve a depender de la prueba gratuita; si esta también venció, ningún vehículo queda visible. Los vehículos no se eliminan y el formulario de solicitud permanece disponible.

## 14. Portal del cliente

### Activación

1. Solicita al taller que registre tu correo y envíe la invitación.
2. Abre el enlace dentro de las **48 horas** siguientes.
3. Define y confirma tu contraseña.
4. Acepta las políticas si se solicitan.
5. En futuras visitas, inicia sesión con tu correo o usuario y contraseña.

El enlace es de un solo uso. Si caducó o ya fue utilizado, contacta al taller. No reenvíes la invitación a otra persona.

### Secciones

| Menú | Uso |
|---|---|
| Inicio / Resumen | Resumen de vehículos, mantenimientos y alertas disponibles. |
| Mis Vehículos | Consultar vehículos propios, abrir sus fichas e historial. |
| Historial de Servicios | Consultar mantenimientos y distinguir registros del taller y personales. |
| Registrar Servicio Propio | Registrar trabajos realizados fuera del taller. |
| Mi Cuenta y Clave | Datos personales, contraseña y baja de cuenta. |

El portal no permite acceder a los módulos administrativos del taller ni a datos de otros propietarios.

Si hay vehículos ocultos, **Desbloquear Todos los vehículos** abre la página del representante de AutoBitácora. El cliente también puede registrar una solicitud mensual o anual desde **Mis Vehículos**. El plan Propietario de Flota requiere confirmación manual de pago; el precio depende del tipo de cliente y vehículo.

### Registrar un servicio propio

1. Pulsa **Registrar Servicio Propio**.
2. Selecciona uno de tus vehículos.
3. Indica concepto, tipo de mantenimiento, fecha y kilometraje.
4. Añade costo y observaciones si corresponde.
5. Guarda y consulta el historial.

El registro queda como mantenimiento personal completado, **no verificado por el taller**. Si el kilometraje es mayor al almacenado, actualiza el odómetro del vehículo. No genera los avisos de órdenes del taller.

### Datos y contraseña

En **Mi Cuenta y Clave**:

- Puedes modificar nombre y teléfono.
- Para cambiar la contraseña necesitas la actual, la nueva —al menos ocho caracteres— y su confirmación.
- El cambio de correo no está disponible en este formulario; solicita ayuda al taller.

No se documenta un autoservicio de recuperación de contraseña para todos los perfiles. Si no recuerdas la actual, contacta al responsable.

### Dar de baja un vehículo propio

Esta acción lo retira de tu perfil, marca su estado de baja y aplica baja lógica al vehículo y a sus órdenes. No entrega tu historial al siguiente propietario.

Consulta al taller antes de confirmar. No asumas que es equivalente al borrado definitivo administrativo o que todas las copias de datos y archivos desaparecen.

### Dar de baja la cuenta

La baja voluntaria desactiva el acceso y cierra tu sesión. No es una solicitud automática de eliminación completa de registros históricos. Para consultas sobre conservación o reactivación, contacta al responsable del servicio.

## 15. Rutina recomendada

### Inicio de jornada

- Revisa que estás en la empresa correcta.
- Consulta el panel y los recordatorios vencidos o próximos.
- Revisa órdenes abiertas o en progreso.
- Coordina con el equipo las tareas del día.

### Recepción del vehículo

- Busca primero al propietario y al vehículo para evitar duplicados.
- Confirma teléfono, correo, placa y kilometraje.
- Registra el servicio solicitado y el estado real.
- Separa notas internas de observaciones para el cliente.

### Entrega

- Revisa el trabajo y las evidencias.
- Actualiza el estado y costo.
- Define los controles futuros, cuando correspondan.
- Verifica el resultado del aviso por correo.
- Confirma con el cliente la información de seguimiento.

### Cierre

- Revisa registros pendientes y advertencias.
- No borres órdenes para corregir su estado; utiliza la edición cuando sea lo apropiado.
- Cierra la sesión, especialmente en dispositivos compartidos.

## 16. Problemas frecuentes

| Situación | Qué hacer |
|---|---|
| Credenciales no válidas | Verifica usuario/correo, contraseña y mayúsculas. Solicita ayuda sin compartir tu contraseña. |
| Cuenta invitada sin activar | Revisa la invitación y su vigencia; pide reenvío al taller si corresponde. |
| Cuenta inactiva | Contacta al administrador responsable. |
| Verificación de seguridad falla | Reintenta con conexión estable; si persiste, informa a soporte para revisar la configuración de Turnstile. |
| Menú o botón no disponible | Confirma tu perfil y la empresa. No todos los usuarios tienen las mismas acciones. |
| Redirección al estado de suscripción | Solicita revisión de estado y fechas al responsable de plataforma. |
| No aparece un cliente o vehículo | Comprueba empresa, filtros y paginación. Puede estar dado de baja o tener asociaciones que requieren revisión técnica. |
| Placa existente | Verifica si ya hay registro o si corresponde una baja autorizada. No cambies la placa para evitar la validación. |
| No llega una invitación o aviso | Revisa correo del destinatario, spam y advertencias. Soporte debe verificar transporte y registros de envío. |
| Recordatorio deshabilitado | Lee el motivo: correo no válido, envío ya realizado u otra condición de elegibilidad. |
| No se envió el recordatorio automático | Verifica que falten exactamente dos días y que el programador esté operativo; consulta a soporte. |
| Guardado con advertencia de correo | Revisa si el registro ya existe antes de repetirlo. |
| Foto rechazada | Comprueba formato, tamaño y límites del servidor. |
| Alertas de kilometraje incorrectas | Revisa el odómetro y el objetivo registrados; no hay lectura automática del vehículo. |
| Cambios visuales no aparecen | Recarga con **Ctrl + F5** en escritorio o actualiza la página en móvil. |
| Una pantalla móvil sigue desbordándose | Reporta sección, ancho/dispositivo y pasos. Adjunta una captura sin datos sensibles. |
| Sesión caducada | Inicia sesión otra vez y verifica si la operación anterior quedó guardada. |

Al pedir ayuda, informa sección, acción, fecha/hora y mensaje mostrado. Usa identificadores internos cuando sea suficiente; oculta documentos, correos, teléfonos y matrículas si no son necesarios.

**Nunca envíes contraseñas, tokens de activación ni archivos de configuración en una captura o issue de GitHub.**

## 17. Precauciones y glosario

### Operaciones que requieren especial cuidado

| Operación | Precaución |
|---|---|
| Trasladar cliente de empresa | Incluye vehículos, historial y vínculos de acceso; requiere autorización y revisión. |
| Dar de baja y liberar placa desde el taller | Eliminación definitiva del vehículo y sus órdenes; no hay deshacer desde la interfaz. |
| Desactivar usuario | Impide su acceso; confirma a quién afecta. |
| Suspender suscripción | Bloquea operaciones de la empresa. |
| Renovar +1 Mes | Reinicia el período desde la renovación; no suma al vencimiento anterior. |
| Enviar correos | Verifica destinatario y evita pruebas con clientes reales. |
| Dar de baja cuenta del cliente | Cierra sesión e impide acceso; no borra automáticamente todos los datos históricos. |

### Glosario

- **SaaS:** servicio de software utilizado por varias empresas.
- **Empresa / taller:** organización a la que pertenecen los datos operativos.
- **Propietario de flota:** cliente asociado a un conjunto de vehículos.
- **VIN:** número de identificación o chasis del vehículo.
- **Odómetro:** kilometraje acumulado del vehículo.
- **Preventivo:** mantenimiento planificado para reducir fallas.
- **Correctivo:** intervención para corregir una falla.
- **Orden:** registro de un trabajo de mantenimiento.
- **Recordatorio:** evaluación de un próximo control por fecha o kilometraje.
- **Slug:** identificador textual de una empresa o plan.
- **Baja lógica:** retiro del uso normal sin eliminación física inmediata del registro.
- **No verificado por taller:** registro personal que no debe considerarse validado por el establecimiento.

---

**Fin del manual.** Para procedimientos técnicos y colaboración en el repositorio, vuelve al [README](../README.md).
