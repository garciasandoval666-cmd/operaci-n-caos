# Solera

Sistema web integral para la operación de un recinto y productora de eventos.
Resuelve el escenario **«Operación Caos Digital»** unificando en una sola base de
datos y una sola aplicación los cuatro módulos del caso:

| Módulo | Alcance | Historias que cubre |
| --- | --- | --- |
| **A · Clientes y eventos** | Expediente de clientes, eventos, ciclo de vida, invitados, mesas, RSVP y check-in | Alta de clientes, control de aforo, confirmaciones, lista de espera |
| **B · Recursos y reservas** | Espacios, mobiliario, equipos, proveedores, insumos, kardex y disponibilidad | Cero dobles reservas, control de inventario, reprogramaciones |
| **C · Cotizaciones y pagos** | Cotizaciones por concepto, precios congelados, anticipos, abonos, devoluciones y saldos | Propuesta → aceptación → cobranza, sin sobrepagos |
| **D · Auditoría y reportes** | Bitácora inmutable, historial de cambios, usuarios/roles, reportes y consistencia | Trazabilidad total, reportes de ocupación, producción y cobranza |

La aplicación incluye además un sitio público con una sola identidad —marfil,
arena y menta sobre tinta, con Mona Sans e Inter— que cubre todos los tipos de
evento del catálogo, no solo bodas:

- **`/`** · portada del recinto: tipos de evento, espacios, calendario de
  disponibilidad en vivo, proceso y contacto.
- **`/boda`** · *Historias del recinto*: eventos ya celebrados, de cualquier
  tipo. La ruta conserva el nombre porque hay enlaces publicados que apuntan ahí.
- **`/rsvp`** · invitación personal por token: confirmación de asistencia, menú y
  restricciones alimenticias.

## 0. El flujo, de principio a fin

El sistema cubre el camino completo de un evento, sin saltos ni capturas
duplicadas:

```
Sitio público                     Panel
─────────────                     ─────
Visitante rellena                 Solicitudes          ← bandeja con filtros
el formulario      ──────────▶    (nuevo → contactado)
     │                                  │
     │ ve si su fecha                   │ «Convertir en evento»
     │ está libre, en vivo              ▼
     │                            Cliente + Evento + Reserva   ← una transacción
     │                                  │
     ▼                                  ▼
  RSVP por token     ◀────────    Cotización → Pagos → Producción → Cierre
```

Las dos puntas se tocan: la disponibilidad que ve el visitante en el formulario
es la misma que el equipo ve en el panel, porque sale de la misma tabla de
reservas. Lo que se aparta para un evento deja de estar disponible para otro.

---

## 1. Stack

- **PHP 8.2** con `declare(strict_types=1)` en todo el código.
- **MySQL 8.0+ / MariaDB 10.4+** con InnoDB, llaves foráneas y `CHECK`.
- **PDO** con consultas preparadas reales (`ATTR_EMULATE_PREPARES = false`).
- **HTML5 + CSS3 + JavaScript vanilla**. Sin frameworks, sin Composer, sin build.
- Arquitectura **front controller** con router propio y autoload PSR-4 (`App\` → `src/App/`).

## 2. Requisitos

- PHP 8.1 o superior con extensiones `pdo_mysql`, `mbstring`, `json` y `session`.
- MySQL 8 o MariaDB 10.4 en ejecución.
- Apache con `mod_rewrite` **o** el servidor embebido de PHP.

## 3. Instalación

```bash
# 1. Crear la base de datos
mysql -u root -p -e "CREATE DATABASE conectaeventos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importar esquema y datos demo (el esquema recrea las tablas)
mysql -u root -p conectaeventos < database/schema.sql
mysql -u root -p conectaeventos < database/seed.sql

# 3. Configurar el entorno
cp .env.example .env
# edita DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD
```

`.env` **no se versiona** (está en `.gitignore`). `.env.example` sirve como plantilla
y no contiene credenciales reales.

> La base se sigue llamando `conectaeventos` y la URL local sigue siendo
> `/conectaeventos/`, aunque el recinto pase a llamarse **Solera**. Es
> deliberado: renombrar una base en marcha obliga a tocar el `.env` de cada
> persona del equipo y la configuración de Apache, y no aporta nada. El nombre
> comercial vive en la tabla `configuracion`; el de la base es solo
> infraestructura.

Comprobado: una base creada así pasa las 31 pruebas de integración sin tocar nada
más (`php tests/pruebas.php`).

### 3.1 Si ya tenías la base importada

`schema.sql` **recrea las tablas**, así que volver a importarlo borra lo que
tengas dentro. Para actualizar una base existente a la versión 1.2.0 sin perder
datos hay un script aparte:

```bash
mysql -u root -p conectaeventos < database/migracion-1.2.0.sql
```

Añade los campos de marca de los proveedores (`categoria`, `descripcion`,
`sitio_web`, `color_marca`, `desde`), la ficha de los espacios (`ideal_para`,
`estilo`), los cinco proveedores de servicio y su inventario. Va dentro de una
transacción y es repetible: si ya se aplicó, no duplica nada.

Sin esta migración, el código de la 1.2.0 falla contra una base antigua, porque
consulta columnas que allí no existen.

## 4. Ejecución

**Opción A · Apache + MySQL de XAMPP (modo actual):**

La aplicación corre en el puerto 80 con el MySQL de XAMPP en el 3306, sin depender
del servidor embebido de PHP:

| Recurso | Valor |
| --- | --- |
| URL principal | `http://localhost/conectaeventos/` |
| URL alternativa | `http://conectaeventos.localhost/` (solo desde el navegador) |
| Ruta en disco | `C:/dev/Liliana/WTC` (fuera de `htdocs`) |
| Base de datos | `127.0.0.1:3306`, base `conectaeventos` |
| Módulos necesarios | `mod_rewrite`, `mod_headers`, `mod_expires` |

Apache sirve el proyecto de dos maneras, ambas declaradas en
`C:\xampp2\apache\conf\extra\httpd-vhosts.conf`:

1. **Alias bajo `localhost`** (`/conectaeventos`). Es la vía recomendada porque
   funciona en todas partes: navegador, `curl`, scripts y pruebas.
2. **VirtualHost `conectaeventos.localhost`**. Más limpio de leer, pero solo los
   navegadores resuelven `*.localhost` a `127.0.0.1`; el resolvedor de Windows no
   lo hace, así que `curl`, PowerShell y cualquier script fallarán con «no se
   puede resolver el nombre remoto» salvo que se use `curl --resolve` o se edite
   el archivo `hosts` (requiere permisos de administrador).

`localhost` conserva su `DocumentRoot` original, de modo que el resto de los
proyectos que viven en `htdocs` siguen funcionando igual.

Doble clic en `iniciar.bat` levanta MySQL y Apache si están detenidos, comprueba
que la base esté importada y que Apache tenga la configuración del proyecto, y
abre la URL. También se puede arrancar todo desde el panel de control de XAMPP.

> Si el navegador rechaza la conexión, casi siempre es porque Apache o MySQL
> están detenidos: arráncalos desde el panel de XAMPP o con `iniciar.bat`.
> `http://localhost/conectaeventos/salud` responde el estado real de la app.

**Opción B · servidor embebido de PHP (sin Apache):**

```bash
php -S localhost:8080 router.php
```

Útil para una revisión rápida, pero sin las cabeceras de seguridad ni las reglas de
reescritura que aplica Apache a través de `.htaccess`.

## 5. Acceso de demostración

El seed crea ocho cuentas, una por rol. **Todas comparten la misma contraseña demo**,
que la pantalla de acceso muestra únicamente cuando `APP_ENV=local`. En producción
esa ayuda no se imprime.

| Correo | Rol | Puede hacer |
| --- | --- | --- |
| `admin@solera.mx` | Administrador | Todo, incluidos usuarios y configuración |
| `comercial@solera.mx` | Comercial | Clientes, eventos, cotizaciones y pagos (lectura) |
| `coordinacion@solera.mx` | Coordinador | Operación del evento, invitados, reservas, cancelaciones |
| `banquetes@solera.mx` | Banquetes | Menús, producción, insumos y kardex |
| `operaciones@solera.mx` | Operaciones | Espacios, recursos, proveedores y reservas |
| `recepcion@solera.mx` | Recepción | Check-in y listas de asistencia |
| `finanzas@solera.mx` | Finanzas | Pagos, saldos, reportes y exportación |
| `auditoria@solera.mx` | Auditor | Bitácora, reportes y consistencia (solo lectura) |

> La contraseña demo se define en el seed como hash bcrypt; no se guarda en texto
> plano. Para cambiarla, edita la cuenta desde **Usuarios** o regenera el hash con
> `password_hash()`. **Las ocho cuentas comparten la misma contraseña demo y la
> pantalla de acceso la imprime cuando `APP_ENV=local`.** Antes de servir la app en
> una red compartida, pon `APP_ENV=production` o cambia las contraseñas: ver
> `docs/BITACORA.md`, sección «Acciones pendientes de configuración».

### Qué puede leer cada rol

La columna anterior resume lo que cada rol **hace**. Esta tabla es el acceso de
**lectura** que aplican las rutas, y es la que verifica la matriz de pruebas HTTP.
Los grupos están definidos en un solo sitio, `roles_grupo()` en
`src/Support/helpers.php`, y los usan tanto las rutas de `index.php` como las
vistas, para que no puedan desincronizarse.

| Módulo | Quién puede leerlo |
| --- | --- |
| Panel, eventos, invitados, menús, espacios, recursos, proveedores, reservas | Los ocho roles: es la agenda operativa del recinto |
| Clientes (datos de contacto) | Administrador, comercial, coordinador, finanzas, auditor |
| Cotizaciones y pagos (importes y saldos) | Administrador, comercial, coordinador, finanzas, auditor |
| Insumos y kardex | Administrador, banquetes, operaciones, coordinador, auditor |
| Check-in | Administrador, coordinador, recepción |
| Reportes | Administrador, coordinador, finanzas, auditor |
| Bitácora | Administrador, auditor |
| Usuarios | Administrador |

Los roles sin acceso financiero (banquetes, operaciones y recepción) tampoco ven
los importes en las pantallas que sí comparten: en el panel y en el expediente del
evento se omiten los bloques de cobranza, y en los listados desaparece la columna
«Saldo». No es solo que el enlace no se pinte: la ruta responde 403.

### Datos demo

- 7 clientes, 7 eventos (septiembre 2026 – mayo 2027) y 30 invitados.
- 6 cotizaciones, 7 pagos, 6 reservas de espacio, 27 de recurso y 8 de insumo.
- El evento **Boda de Aurora y Diego** (14/02/2027) es el caso guía del módulo A.
- El **Aniversario Corporativo 2026** está cerrado y conserva check-ins e historial.
- En `/rsvp`, el entorno local ofrece un enlace de invitación de ejemplo.

## 6. Estructura del proyecto

```
WTC/
├── index.php                  Front controller: 84 rutas y despacho con manejo de errores
├── router.php                 Router para el servidor embebido de PHP
├── iniciar.bat                Arranque en Windows
├── .htaccess                  Rewrite, cabeceras de seguridad y caché
├── .env.example               Plantilla de configuración
├── database/
│   ├── schema.sql             34 tablas + 4 vistas + restricciones e índices
│   └── seed.sql               Catálogos, usuarios, eventos, cotizaciones y pagos demo
├── src/
│   ├── bootstrap.php          Entorno, autoload PSR-4, sesión y CSRF
│   ├── App/
│   │   ├── Core/              Database, Auth, Csrf, Flash, Router, View
│   │   ├── Controllers/       19 controladores (uno por área funcional)
│   │   └── Domain/            Reglas de negocio: Disponibilidad, Eventos,
│   │                          Insumos, Cotizaciones, Invitados
│   └── Support/helpers.php    Escape, URLs, formato, iconos, bitácora, configuración
├── views/                     53 vistas: layouts, módulos, sitio, RSVP y errores
├── assets/
│   ├── css/app.css            Tokens del sistema y componentes del panel
│   ├── css/landing.css        Sitio público (portada e historias)
│   ├── css/boda.css           Pantallas de invitación (RSVP)
│   └── js/                    Interacciones del panel y del sitio
├── tests/pruebas.php          Matriz de 31 pruebas de integración con rollback
├── storage/                   Logs y respaldos (fuera de control de versiones)
└── docs/                      Entregables de documentación
```

## 7. Documentación

| Documento | Contenido |
| --- | --- |
| [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) | Capas, flujo de una petición, decisiones de diseño e integración entre módulos |
| [`docs/BASE_DE_DATOS.md`](docs/BASE_DE_DATOS.md) | Modelo de datos, diccionario, restricciones, concurrencia y diagrama ER |
| [`docs/PRUEBAS.md`](docs/PRUEBAS.md) | Estrategia, matriz de 31 casos, resultados y pruebas HTTP |
| [`docs/MANUAL.md`](docs/MANUAL.md) | Manual de uso por rol y recorridos paso a paso |
| [`docs/BITACORA.md`](docs/BITACORA.md) | Bitácora de construcción y cambios del proyecto |
| [`docs/INCIDENTE.md`](docs/INCIDENTE.md) | Informe de incidentes operativos y técnicos, con causa raíz y prevención |
| [`docs/CONTRIBUCIONES.md`](docs/CONTRIBUCIONES.md) | Plantilla de contribuciones individuales por módulo |

## 8. Pruebas

```bash
php tests/pruebas.php
```

El script abre una transacción, ejecuta **31 casos** con un `SAVEPOINT` por caso y
revierte todo al final. Comprueba conexión, disponibilidad, dobles reservas,
capacidad, inventario, cotizaciones, pagos, sobrepagos, RSVP, aforo, cancelaciones
en cascada, precio de catálogo, saneo de CSV y consistencia de vistas. Resultado
esperado:

```
Resultado: 31 de 31 pruebas aprobadas
Rollback aplicado · datos demo intactos
```

También se verificaron 88 archivos PHP con `php -l` (0 errores), 47 rutas HTTP
(0 errores 500) y 10 flujos POST (CSRF, login, permisos y RSVP).

## 9. Decisiones de seguridad

- Consultas **siempre** preparadas; sin interpolación de datos del usuario.
- Escape de salida con `e()` en todas las vistas.
- **CSRF** obligatorio en cada `POST` (respuesta 419 si falta o no coincide), incluido el cierre de sesión.
- Contraseñas con `password_hash()` (bcrypt) y verificación con `password_verify()`.
- Bloqueo temporal tras 6 intentos fallidos por sesión y **10 por IP** en 10 minutos, con registro en bitácora.
- Sesión con cookie `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS; expira a las 8 horas o tras 30 minutos de inactividad.
- Permisos por rol **en el servidor**, no solo en el menú.
- Secretos solo en `.env`; `.env` y `.env.local` están excluidos del repositorio.
- Bitácora e historial **no se borran**: se consultan y se filtran.
- **Perímetro cerrado**: `.htaccess` por carpeta y `router.php` niegan `database/`, `docs/`, `storage/`, `tests/`, `src/`, `views/`, dotfiles y artefactos `.sql/.log/.md`.
- Cabeceras `CSP`, `X-Frame-Options`, `nosniff`, `Referrer-Policy` y `Permissions-Policy`; sin scripts en línea.
- Exportación CSV con saneo de fórmulas (`=`, `+`, `-`, `@`) para evitar inyección al abrir en Excel.
- El precio de catálogo de una cotización se lee del servidor; el ajuste comercial queda en `historial_precios`.

## 10. Créditos

Proyecto académico desarrollado para el caso **«Operación Caos Digital»**.
Las fotografías de los sitios públicos provienen de Pexels y se cargan por URL;
si no hay conexión, el diseño conserva sus degradados y tipografía.
