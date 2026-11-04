-- =====================================================================
--  Solera · migración 1.1.0 → 1.2.0
--  ---------------------------------------------------------------------
--  Solo hace falta si YA tienes la base importada y no quieres perder
--  sus datos. En una instalación nueva no se ejecuta: `schema.sql` y
--  `seed.sql` ya traen todo esto.
--
--  Qué hace:
--    1. Da a los proveedores los campos de marca que el sitio necesita.
--    2. Añade los cinco proveedores de servicio con su inventario.
--    3. Da a los espacios su ficha propia (`ideal_para`, `estilo`).
--    4. Renombra la casa: ConectaEventos pasa a llamarse Solera, y los
--       correos del equipo pasan al dominio nuevo.
--
--  Uso:
--     mysql -u root -p conectaeventos < database/migracion-1.2.0.sql
--
--  Ojo con el punto 4: cambia el correo con el que inicias sesión.
--  La contraseña no se toca.
-- =====================================================================

START TRANSACTION;

-- --- 1. Proveedores: de directorio de teléfonos a marcas ---------------
ALTER TABLE proveedores
  ADD COLUMN categoria   ENUM('mobiliario','personal','audio_video','decoracion','transporte','otro')
                         NOT NULL DEFAULT 'otro' AFTER nombre,
  ADD COLUMN descripcion VARCHAR(160) NULL AFTER categoria,
  ADD COLUMN logo        VARCHAR(120) NULL AFTER descripcion,
  ADD COLUMN sitio_web   VARCHAR(160) NULL AFTER correo,
  ADD COLUMN color_marca CHAR(7) NOT NULL DEFAULT '#1f6b4f' AFTER sitio_web,
  ADD COLUMN desde       SMALLINT UNSIGNED NULL AFTER color_marca;

UPDATE proveedores SET categoria='mobiliario',  descripcion='Mobiliario de banquete, mantelería y montaje completo', logo='sillas-y-mesas-del-valle.svg', sitio_web='https://sillasdelvalle.example', color_marca='#8a6512', desde=2019 WHERE id_proveedor=1;
UPDATE proveedores SET categoria='audio_video', descripcion='Audio, iluminación escénica y proyección',              logo='sonido-pro-mx.svg',            sitio_web='https://sonidopromx.example',    color_marca='#2a5280', desde=2020 WHERE id_proveedor=2;
UPDATE proveedores SET categoria='personal',    descripcion='Capitanes, meseros y personal de montaje certificado',  logo='staff-eventos-elite.svg',      sitio_web='https://staffelite.example',     color_marca='#1f6b4f', desde=2018 WHERE id_proveedor=3;
UPDATE proveedores SET categoria='decoracion',  descripcion='Diseño floral, ambientación y arcos de ceremonia',      logo='floralia-studio.svg',          sitio_web='https://floralia.example',       color_marca='#b4403a', desde=2021 WHERE id_proveedor=4;

INSERT INTO proveedores (id_proveedor, nombre, categoria, descripcion, logo, contacto, telefono, correo, sitio_web, color_marca, desde, estado) VALUES
(5, 'Lente Abierto',         'otro',       'Fotografía y video documental de evento',                'lente-abierto.svg',         'Diego Salinas', '+52 55 2266 7788', 'diego@lenteabierto.example',  'https://lenteabierto.example',   '#504f4d', 2021, 'activo'),
(6, 'Ritmo Vivo',            'otro',       'Música en vivo, DJ y producción de pista',               'ritmo-vivo.svg',            'Paulina Cruz',  '+52 55 2277 8899', 'paulina@ritmovivo.example',   'https://ritmovivo.example',      '#6b3f8a', 2022, 'activo'),
(7, 'Imprenta Sierra',       'otro',       'Papelería, señalética y gráfica de evento',              'imprenta-sierra.svg',       'Hugo Sierra',   '+52 55 2288 9900', 'hugo@imprentasierra.example', 'https://imprentasierra.example', '#2b323e', 2020, 'activo'),
(8, 'Traslados Horizonte',   'transporte', 'Transporte de invitados y logística de carga',           'traslados-horizonte.svg',   'Elena Ruiz',    '+52 55 2299 0011', 'elena@horizonte.example',     'https://horizonte.example',      '#1f6b7a', 2023, 'activo'),
(9, 'Hielo y Barra Central', 'otro',       'Barra de coctelería, cristalería y servicio de bebidas', 'hielo-y-barra-central.svg', 'Raúl Ponce',    '+52 55 2210 1122', 'raul@barracentral.example',   'https://barracentral.example',   '#8a5a12', 2022, 'activo');

-- --- 2. Inventario de los aliados de servicio -------------------------
INSERT INTO recursos (id_recurso, id_proveedor, categoria, nombre, unidad, existencias_total, costo_referencia, estado) VALUES
(10, 5, 'otro',        'Cobertura fotográfica',    'servicio', 3,   8500.00, 'activo'),
(11, 5, 'otro',        'Cabina de fotos',          'equipo',   2,   3200.00, 'activo'),
(12, 6, 'audio_video', 'DJ con cabina',            'servicio', 3,   7800.00, 'activo'),
(13, 6, 'otro',        'Grupo versátil 8 músicos', 'servicio', 2,  18500.00, 'activo'),
(14, 7, 'otro',        'Señalética de evento',     'juego',    10,  1800.00, 'activo'),
(15, 7, 'otro',        'Papelería de mesa',        'juego',    25,   650.00, 'activo'),
(16, 8, 'transporte',  'Autobús 40 plazas',        'unidad',   3,   6500.00, 'activo'),
(17, 8, 'transporte',  'Van ejecutiva 12 plazas',  'unidad',   4,   3200.00, 'activo'),
(18, 9, 'otro',        'Barra móvil de coctelería','equipo',   3,   5200.00, 'activo'),
(19, 9, 'otro',        'Cristalería de coctel',    'pieza',    600,    12.00, 'activo');

-- --- 3. Espacios: la ficha que publica el sitio ------------------------
ALTER TABLE espacios
  ADD COLUMN ideal_para VARCHAR(160) NULL AFTER descripcion,
  ADD COLUMN estilo     VARCHAR(160) NULL AFTER ideal_para;

UPDATE espacios SET ideal_para='XV años, cumpleaños y bodas de hasta 150 invitados', estilo='Cálido, pista central y salida directa a terraza'   WHERE id_espacio=1;
UPDATE espacios SET ideal_para='Bodas de gran formato, galas y premiaciones',        estilo='Techo alto, escenario fijo e iluminación escénica'  WHERE id_espacio=2;
UPDATE espacios SET ideal_para='Expos, congresos y montajes de varios días',         estilo='Diáfano, modular y con carga y descarga directa'    WHERE id_espacio=3;
UPDATE espacios SET ideal_para='Cóctel de bienvenida y ceremonias al aire libre',    estilo='Abierto, vista panorámica y toldo retráctil'        WHERE id_espacio=4;
UPDATE espacios SET ideal_para='Ceremonias, sesiones de foto y recepciones',         estilo='Natural, arbolado y con iluminación colgante'       WHERE id_espacio=5;

-- --- 4. La casa pasa a llamarse Solera ---------------------------------
-- El nombre vive en `configuracion`, no escrito en las vistas: cambiarlo
-- es cambiar esta fila. Los correos del equipo siguen al nombre.
UPDATE configuracion SET valor = 'Solera'         WHERE clave = 'empresa_nombre';
UPDATE configuracion SET valor = 'hola@solera.mx' WHERE clave = 'empresa_correo';
UPDATE usuarios SET correo = REPLACE(correo, '@conectaeventos.mx', '@solera.mx');

COMMIT;

SELECT 'Migración 1.2.0 aplicada' AS resultado,
       (SELECT valor FROM configuracion WHERE clave = 'empresa_nombre') AS se_llama,
       (SELECT COUNT(*) FROM proveedores) AS proveedores,
       (SELECT COUNT(*) FROM recursos)    AS recursos;
