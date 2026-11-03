-- ============================================================
--  ConectaEventos · datos ficticios de demostración
--  Todos los datos son inventados.
--  La contraseña demo de las ocho cuentas se muestra en la pantalla de acceso
--  cuando APP_ENV=local; nunca se documenta en texto plano en este archivo.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Roles y usuarios
-- ------------------------------------------------------------
INSERT INTO roles (id_rol, nombre, descripcion) VALUES
(1, 'administrador', 'Configura catálogos, usuarios y parámetros del recinto'),
(2, 'comercial',     'Atiende prospectos, cotiza y da seguimiento'),
(3, 'coordinador',   'Opera el evento: programa, invitados, menús y proveedores'),
(4, 'banquetes',     'Producción de alimentos, menús y restricciones'),
(5, 'operaciones',   'Montaje, mobiliario, espacios y reservas'),
(6, 'recepcion',     'Registro de llegada y lista de asistencia'),
(7, 'finanzas',      'Anticipos, saldos, facturación y cancelaciones'),
(8, 'auditor',       'Bitácora y reportes en modo lectura');

INSERT INTO usuarios (id_usuario, id_rol, nombre, correo, password_hash, telefono) VALUES
(1, 1, 'Ana Lucía Ramírez', 'admin@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9060'),
(2, 2, 'Marco Antonio Solís', 'comercial@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9061'),
(3, 3, 'Valeria Ortega', 'coordinacion@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9062'),
(4, 4, 'Diego Fuentes', 'banquetes@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9063'),
(5, 5, 'Sofía Mendoza', 'operaciones@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9064'),
(6, 6, 'Luis Herrera', 'recepcion@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9065'),
(7, 7, 'Paola Castillo', 'finanzas@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9066'),
(8, 8, 'Rodrigo Nava', 'auditoria@conectaeventos.mx', '$2y$10$WOuT1d4NPBbFudS8pPUtKODrVvWKf5sYBZ26VFuSSGls0jS1yCVfO', '+52 55 4821 9067');

INSERT INTO configuracion (clave, valor, descripcion) VALUES
('empresa_nombre', 'ConectaEventos', 'Nombre comercial del recinto'),
('empresa_telefono', '+52 55 4821 9060', 'Teléfono de contacto'),
('empresa_correo', 'hola@conectaeventos.mx', 'Correo de contacto'),
('empresa_direccion', 'Av. Paseo de la Reforma 265, Cuauhtémoc, CDMX', 'Dirección del recinto'),
('dias_limite_menu', '7', 'Días antes del evento en que se cierra el cambio de menú'),
('anticipo_porcentaje', '30', 'Porcentaje de anticipo requerido'),
('iva_porcentaje', '16', 'IVA aplicado a cotizaciones'),
('moneda', 'MXN', 'Moneda del sistema'),
('capacidad_maxima_recinto', '1200', 'Aforo máximo simultáneo del recinto');

-- ------------------------------------------------------------
-- Catálogos del módulo A
-- ------------------------------------------------------------
INSERT INTO tipos_evento (id_tipo_evento, nombre, descripcion, requiere_mesas, requiere_menu, requiere_rsvp, acompanantes_default, icono) VALUES
(1, 'Boda', 'Ceremonia y recepción con menú de varios tiempos', 1, 1, 1, 2, 'corazon'),
(2, 'XV Años', 'Mesa de honor, vals y brindis', 1, 1, 1, 1, 'destello'),
(3, 'Corporativo', 'Cena, premiación o reunión de empresa', 1, 1, 1, 0, 'grafico'),
(4, 'Congreso', 'Sesiones simultáneas, registro y coffee breaks', 0, 0, 1, 0, 'usuarios'),
(5, 'Exposición', 'Stands y montaje de varios días', 0, 0, 0, 0, 'espacios'),
(6, 'Graduación', 'Protocolo, mesas por familia y boletos', 1, 1, 1, 2, 'musica'),
(7, 'Social', 'Cumpleaños, aniversarios y celebraciones privadas', 0, 1, 1, 0, 'casa'),
(8, 'Concierto', 'Acceso general, seguridad reforzada, sin menú', 0, 0, 0, 0, 'musica');

INSERT INTO clientes (id_cliente, tipo_cliente, nombre, razon_social, correo, telefono, direccion, notas, estado) VALUES
(1, 'persona', 'Fernanda Ríos', NULL, 'fernanda.rios@example.com', '+52 55 1200 3401', 'Coyoacán, CDMX', 'Clienta recurrente de eventos familiares.', 'activo'),
(2, 'empresa', 'NovaTech Solutions', 'NovaTech Solutions SA de CV', 'eventos@novatech.example', '+52 55 8800 1120', 'Santa Fe, CDMX', 'Factura a nombre de la empresa; requiere orden de compra.', 'activo'),
(3, 'empresa', 'Colegio San Ángel', 'Colegio San Ángel AC', 'direccion@colegiosanangel.example', '+52 55 5566 7788', 'San Ángel, CDMX', 'Graduaciones y festivales anuales.', 'activo'),
(4, 'persona', 'Aurora Delgado y Diego Salas', NULL, 'aurora.diego@example.com', '+52 55 3311 9090', 'Polanco, CDMX', 'Boda de destino con invitados de Monterrey.', 'activo'),
(5, 'empresa', 'Cámara de Café de Altura', 'Cámara de Café de Altura AC', 'contacto@cafedealtura.example', '+52 55 4040 5050', 'Roma Norte, CDMX', 'Expo anual de productores.', 'activo'),
(6, 'empresa', 'Instituto de Salud Digital', 'Instituto de Salud Digital SC', 'congreso@saluddigital.example', '+52 55 7000 8080', 'Del Valle, CDMX', 'Congreso con dos sedes y transmisión.', 'activo'),
(7, 'persona', 'Ricardo Beltrán', NULL, 'ricardo.beltran@example.com', '+52 55 9090 1010', 'Tlalpan, CDMX', 'Aniversario corporativo 2026.', 'activo');

INSERT INTO servicios (id_servicio, categoria, nombre, descripcion, precio_base) VALUES
(1, 'otro', 'Coordinación integral', 'Coordinación del evento de principio a fin', 18000.00),
(2, 'audio_video', 'Audio e iluminación', 'Sonido, microfonía e iluminación escénica', 12000.00),
(3, 'decoracion', 'Decoración floral', 'Arreglos de mesa y ambientación floral', 9500.00),
(4, 'otro', 'Fotografía y video', 'Cobertura fotográfica y video highlights', 15000.00),
(5, 'seguridad', 'Seguridad privada', 'Personal de seguridad y control de accesos', 8000.00),
(6, 'limpieza', 'Limpieza final', 'Limpieza profunda después del evento', 3500.00),
(7, 'entretenimiento', 'Animación y DJ', 'DJ, pista y animación para invitados', 7000.00),
(8, 'mobiliario', 'Mobiliario premium', 'Montaje de mobiliario y mantelería fina', 6000.00);

-- ------------------------------------------------------------
-- Catálogos del módulo B
-- ------------------------------------------------------------
INSERT INTO espacios (id_espacio, nombre, tipo, capacidad, costo_referencia, descripcion, estado) VALUES
(1, 'Salón Aurora', 'salon_fiesta', 150, 28000.00, 'Salón de fiestas con terraza y pista de baile', 'activo'),
(2, 'Gran Salón Imperial', 'salon_gala', 400, 55000.00, 'Salón de gala con escenario y cabina de audio', 'activo'),
(3, 'Galería Central', 'sala_exhibicion', 300, 38000.00, 'Sala diáfana para exposiciones y congresos', 'activo'),
(4, 'Terraza Mirador', 'terraza', 90, 18000.00, 'Terraza al aire libre con vista a la ciudad', 'activo'),
(5, 'Jardín de los Olivos', 'jardin', 220, 32000.00, 'Jardín con pérgola y área de ceremonia', 'mantenimiento');

INSERT INTO proveedores (id_proveedor, nombre, contacto, telefono, correo, estado) VALUES
(1, 'Sillas y Mesas del Valle', 'Laura Pérez', '+52 55 2211 3344', 'laura@sillasdelvalle.example', 'activo'),
(2, 'Sonido Pro MX', 'Carlos Ruiz', '+52 55 2233 4455', 'carlos@sonidopromx.example', 'activo'),
(3, 'Staff Eventos Elite', 'Ana Torres', '+52 55 2244 5566', 'ana@staffelite.example', 'activo'),
(4, 'Floralia Studio', 'Mónica Vega', '+52 55 2255 6677', 'monica@floralia.example', 'activo');

INSERT INTO recursos (id_recurso, id_proveedor, categoria, nombre, unidad, existencias_total, costo_referencia, estado) VALUES
(1, 1, 'mobiliario', 'Silla Tiffany', 'pieza', 400, 15.00, 'activo'),
(2, 1, 'mobiliario', 'Mesa redonda 10 personas', 'pieza', 40, 120.00, 'activo'),
(3, 1, 'mobiliario', 'Mantelería premium', 'juego', 60, 85.00, 'activo'),
(4, 2, 'audio_video', 'Equipo de sonido completo', 'equipo', 4, 3500.00, 'activo'),
(5, 2, 'audio_video', 'Iluminación arquitectónica', 'equipo', 6, 2200.00, 'activo'),
(6, 3, 'personal', 'Mesero', 'persona', 30, 450.00, 'activo'),
(7, 3, 'personal', 'Edecán', 'persona', 12, 900.00, 'activo'),
(8, 4, 'decoracion', 'Centro de mesa floral', 'pieza', 40, 650.00, 'activo'),
(9, 1, 'mobiliario', 'Pista de baile 6x6', 'pieza', 2, 4500.00, 'activo');

INSERT INTO insumos_menu (id_insumo, nombre, categoria, unidad, stock_fisico, stock_comprometido, stock_minimo, costo_referencia, estado) VALUES
(1, 'Canapés surtidos', 'entrada', 'porcion', 600.00, 340.00, 60.00, 25.00, 'activo'),
(2, 'Filete en salsa de vino', 'plato_fuerte', 'porcion', 500.00, 340.00, 40.00, 120.00, 'activo'),
(3, 'Pastel de tres leches', 'postre', 'porcion', 400.00, 340.00, 50.00, 35.00, 'activo'),
(4, 'Agua de jamaica', 'bebida', 'litro', 500.00, 140.00, 80.00, 18.00, 'activo'),
(5, 'Vino tinto reserva', 'bebida', 'botella', 120.00, 40.00, 24.00, 380.00, 'activo'),
(6, 'Café de altura', 'abarrote', 'kilogramo', 80.00, 0.00, 15.00, 420.00, 'activo'),
(7, 'Vegetales rostizados', 'plato_fuerte', 'porcion', 200.00, 0.00, 30.00, 45.00, 'activo');

-- ------------------------------------------------------------
-- Eventos
-- ------------------------------------------------------------
INSERT INTO eventos (id_evento, id_cliente, id_tipo_evento, nombre, descripcion, fecha_inicio, fecha_fin, aforo_estimado, aforo_maximo, estado, notas, id_usuario_creador) VALUES
(1, 4, 1, 'Boda de Aurora y Diego', 'Ceremonia en jardín y recepción de gala con menú de tres tiempos.', '2027-02-14 18:00:00', '2027-02-15 01:00:00', 200, 220, 'confirmado', 'Solicitan menú vegetariano para 12 personas.', 2),
(2, 2, 3, 'Gala Anual NovaTech', 'Premiación anual con cena de tres tiempos y montaje corporativo.', '2026-12-05 19:00:00', '2026-12-06 01:00:00', 140, 150, 'en_preparacion', 'Requieren pantalla LED y créditos de patrocinadores.', 2),
(3, 6, 4, 'Congreso Salud Digital 2027', 'Congreso de dos días con conferencias, coffee breaks y stands.', '2027-03-18 09:00:00', '2027-03-20 18:00:00', 280, 300, 'cotizado', 'Pendiente de aprobación del comité.', 2),
(4, 1, 2, 'XV Años de Valentina', 'Fiesta de XV años con vals, mesa de honor y pista de baile.', '2027-04-24 17:00:00', '2027-04-25 01:00:00', 110, 120, 'confirmado', 'Mesa de honor para 14 personas.', 2),
(5, 3, 6, 'Graduación Generación 2026', 'Ceremonia de graduación y cena para familias.', '2026-12-18 18:00:00', '2026-12-19 00:00:00', 320, 350, 'confirmado', 'Boletos numerados por familia.', 2),
(6, 5, 5, 'Expo Café de Altura', 'Exposición de productores con catas y stands.', '2027-05-08 10:00:00', '2027-05-10 20:00:00', 250, 300, 'prospecto', 'Solicitan plano de stands y acceso de montaje.', 2),
(7, 7, 7, 'Aniversario Corporativo 2026', 'Celebración de aniversario con cena y reconocimientos.', '2026-09-12 19:00:00', '2026-09-13 01:00:00', 80, 90, 'cerrado', 'Evento realizado y liquidado.', 2);

INSERT INTO evento_servicios (id_evento, id_servicio, cantidad, precio_unitario, notas) VALUES
(1, 1, 1, 18000.00, 'Coordinación de proveedores y protocolo'),
(1, 2, 1, 12000.00, 'Sonido e iluminación de salón'),
(1, 3, 1, 9500.00, 'Centros de mesa y arco floral'),
(2, 1, 1, 18000.00, 'Coordinación corporativa'),
(2, 2, 1, 12000.00, 'Pantalla y sonido'),
(3, 1, 1, 18000.00, 'Coordinación de congreso'),
(3, 2, 3, 7000.00, 'Audio por día'),
(4, 3, 1, 9500.00, 'Decoración de mesa de honor'),
(4, 4, 1, 15000.00, 'Cobertura foto y video'),
(5, 1, 1, 18000.00, 'Protocolo de graduación'),
(5, 5, 1, 8000.00, 'Control de accesos'),
(7, 2, 1, 12000.00, 'Audio y micrófonos');

INSERT INTO evento_programa (id_evento, inicio, fin, actividad, responsable) VALUES
(1, '2027-02-14 17:00:00', '2027-02-14 18:00:00', 'Registro de invitados y bienvenida', 'Recepción'),
(1, '2027-02-14 18:00:00', '2027-02-14 19:00:00', 'Ceremonia civil', 'Coordinación'),
(1, '2027-02-14 19:00:00', '2027-02-14 20:00:00', 'Cóctel y sesión de fotos', 'Fotografía'),
(1, '2027-02-14 20:00:00', '2027-02-14 22:00:00', 'Cena de tres tiempos', 'Banquetes'),
(1, '2027-02-14 22:00:00', '2027-02-15 01:00:00', 'Baile y brindis', 'Animación'),
(2, '2026-12-05 18:30:00', '2026-12-05 19:30:00', 'Registro y cóctel de bienvenida', 'Recepción'),
(2, '2026-12-05 19:30:00', '2026-12-05 21:00:00', 'Premiación anual', 'Coordinación'),
(2, '2026-12-05 21:00:00', '2026-12-05 23:00:00', 'Cena de gala', 'Banquetes'),
(3, '2027-03-18 08:00:00', '2027-03-18 09:00:00', 'Registro y acreditación', 'Recepción'),
(3, '2027-03-18 09:00:00', '2027-03-18 13:00:00', 'Conferencias magistrales', 'Coordinación'),
(3, '2027-03-19 09:00:00', '2027-03-19 18:00:00', 'Talleres simultáneos', 'Coordinación'),
(4, '2027-04-24 17:00:00', '2027-04-24 18:00:00', 'Recepción y fotografías', 'Recepción'),
(4, '2027-04-24 18:00:00', '2027-04-24 19:00:00', 'Vals y brindis', 'Animación'),
(5, '2026-12-18 17:00:00', '2026-12-18 18:00:00', 'Registro de familias', 'Recepción'),
(5, '2026-12-18 18:00:00', '2026-12-18 19:30:00', 'Ceremonia de graduación', 'Dirección escolar'),
(5, '2026-12-18 19:30:00', '2026-12-18 22:00:00', 'Cena y baile', 'Banquetes');

INSERT INTO mesas (id_mesa, id_evento, numero, capacidad, zona) VALUES
(1, 1, 'M1', 8, 'Familia'),
(2, 1, 'M2', 10, 'Amigos'),
(3, 1, 'M3', 8, 'Mesa de honor'),
(4, 1, 'M4', 6, 'Prensa y staff'),
(5, 2, 'A1', 10, 'Dirección'),
(6, 2, 'A2', 10, 'Invitados'),
(7, 2, 'A3', 10, 'Invitados'),
(8, 4, 'V1', 14, 'Mesa de honor'),
(9, 5, 'G1', 10, 'Familia'),
(10, 5, 'G2', 10, 'Familia');

-- ------------------------------------------------------------
-- Menús, platillos y alérgenos
-- ------------------------------------------------------------
INSERT INTO alergenos (id_alergeno, nombre) VALUES
(1, 'Gluten'), (2, 'Lácteos'), (3, 'Nueces'), (4, 'Mariscos'), (5, 'Huevo'), (6, 'Soya');

INSERT INTO platillos (id_platillo, nombre, tipo, descripcion, es_vegetariano, es_vegano) VALUES
(1, 'Volován de hongos', 'entrada', 'Hojaldre relleno de hongos al romero', 1, 0),
(2, 'Crema de calabaza', 'sopa', 'Crema rostizada con aceite de pipián', 1, 0),
(3, 'Filete en salsa de vino', 'plato_fuerte', 'Filete de res con reducción de vino tinto', 0, 0),
(4, 'Salmón al eneldo', 'plato_fuerte', 'Salmón con puré de papa y eneldo', 0, 0),
(5, 'Risotto de setas', 'plato_fuerte', 'Arroz arborio con setas y parmesano', 1, 0),
(6, 'Pastel de tres leches', 'postre', 'Bizcocho bañado en tres leches', 1, 0),
(7, 'Tarta de frutos rojos', 'postre', 'Tarta fría con frutos rojos de temporada', 1, 0),
(8, 'Agua de jamaica', 'bebida', 'Agua fresca de jamaica con hierbabuena', 1, 1),
(9, 'Café de altura', 'bebida', 'Café de especialidad de Chiapas', 1, 1),
(10, 'Canapés surtidos', 'snack', 'Selección de canapés dulces y salados', 1, 0);

INSERT INTO platillo_alergenos (id_platillo, id_alergeno) VALUES
(1, 1), (1, 2),
(2, 2),
(3, 2),
(4, 4), (4, 2),
(5, 2),
(6, 1), (6, 2), (6, 5),
(7, 1), (7, 5),
(10, 1), (10, 2), (10, 3);

INSERT INTO menus (id_menu, nombre, tipo_servicio, descripcion, precio_persona, minimo_personas) VALUES
(1, 'Banquete Imperial', 'plato_servido', 'Entrada, sopa, plato fuerte y postre servidos en mesa', 1250.00, 50),
(2, 'Buffet de Autor', 'buffet', 'Estaciones calientes, ensaladas y postres', 890.00, 80),
(3, 'Cóctel de Gala', 'coctel', 'Canapés, bocadillos y barra de bebidas', 650.00, 60),
(4, 'Coffee Break Premium', 'coffee_break', 'Café, panadería y fruta para pausas de congreso', 320.00, 40),
(5, 'Barra Libre Premium', 'barra_libre', 'Coctelería de autor y bebidas sin alcohol', 480.00, 50);

INSERT INTO menu_platillos (id_menu, id_platillo) VALUES
(1, 1), (1, 2), (1, 3), (1, 6), (1, 8),
(2, 2), (2, 4), (2, 5), (2, 7), (2, 8),
(3, 1), (3, 10), (3, 8),
(4, 9), (4, 10),
(5, 8), (5, 9);

INSERT INTO evento_menus (id_evento_menu, id_evento, id_menu, personas, notas) VALUES
(1, 1, 1, 200, 'Menú principal de la boda'),
(2, 2, 2, 140, 'Buffet para gala corporativa'),
(3, 3, 4, 280, 'Coffee break para los dos días'),
(4, 4, 1, 110, 'Banquete de XV años'),
(5, 5, 2, 320, 'Buffet de graduación'),
(6, 7, 2, 80, 'Cena de aniversario');

-- ------------------------------------------------------------
-- Invitados
-- ------------------------------------------------------------
INSERT INTO invitados (id_invitado, id_evento, id_titular, nombre, correo, telefono, categoria, estado_rsvp, token_acceso, id_mesa, id_evento_menu, restriccion_alimenticia, alergias, notas) VALUES
(1, 1, NULL, 'Ana Sofía Ríos', 'ana.rios@example.com', '+52 55 1000 0001', 'vip', 'confirmado', '7f3a9c2e5b1d8046af27c9e13d5b6a01', 1, 1, 'ninguna', NULL, 'Testigo de la novia'),
(2, 1, 1, 'Carlos Ríos', 'carlos.rios@example.com', '+52 55 1000 0002', 'familia', 'confirmado', '2c8e4a7f9b3d1056ce82f4a7d9b1e305', 1, 1, 'ninguna', NULL, NULL),
(3, 1, 1, 'Mariana Ríos', 'mariana.rios@example.com', '+52 55 1000 0003', 'familia', 'confirmado', '91d4b6f2a8c05e3719bd46c2f8a7e503', 1, 1, 'vegetariano', NULL, 'Menú sin carne'),
(4, 1, NULL, 'Jorge Villanueva', 'jorge.villanueva@example.com', '+52 55 1000 0004', 'general', 'confirmado', '5a7e1c9d3b604f28ad91c7e5b2f8d406', 2, 1, 'ninguna', 'Nueces', 'Alergia severa a nueces'),
(5, 1, 4, 'Paola Villanueva', 'paola.villanueva@example.com', '+52 55 1000 0005', 'general', 'confirmado', 'e3b8f5a1c7d24906be31a8f4c6d2b507', 2, 1, 'sin_gluten', NULL, NULL),
(6, 1, NULL, 'Ricardo Méndez', 'ricardo.mendez@example.com', '+52 55 1000 0006', 'vip', 'confirmado', '4f9d2b6e8a1c5307fd28e4b9a6c3d608', 3, 1, 'ninguna', NULL, NULL),
(7, 1, 6, 'Lucía Méndez', 'lucia.mendez@example.com', '+52 55 1000 0007', 'vip', 'confirmado', 'c6a1e8d4f2b75903ac47d1e9b5f3a709', 3, 1, 'vegano', 'Soya', NULL),
(8, 1, NULL, 'Andrea Solano', 'andrea.solano@example.com', '+52 55 1000 0008', 'general', 'pendiente', '8b2f5d9a1e6c4703bd82f5a1c9e4b80a', NULL, 1, 'ninguna', NULL, NULL),
(9, 1, NULL, 'Héctor Solano', 'hector.solano@example.com', '+52 55 1000 0009', 'general', 'pendiente', 'd7c3a9f6b1e54802ca93e7b4d8f1a90b', NULL, 1, 'ninguna', NULL, NULL),
(10, 1, NULL, 'Fernanda Cruz', 'fernanda.cruz@example.com', '+52 55 1000 0010', 'general', 'declinado', '1e8b4d7a2f9c6305be41d8a7c3f6b00c', NULL, NULL, 'ninguna', NULL, 'No podrá asistir'),
(11, 1, NULL, 'Emilio Navarro', 'emilio.navarro@example.com', '+52 55 1000 0011', 'general', 'lista_espera', '6d2a9e5c1b8f4703ad76c2e9b4f1a80d', NULL, 1, 'ninguna', NULL, 'Aforo completo al confirmar'),
(12, 1, NULL, 'Revista Glam', 'prensa@glam.example', '+52 55 1000 0012', 'prensa', 'confirmado', '3c7f1b8e6d2a9504cf83b1e7a5d9c20e', 4, 3, 'ninguna', NULL, 'Cobertura autorizada'),
(13, 1, NULL, 'Studio Lumen', 'foto@studiolumen.example', '+52 55 1000 0013', 'proveedor', 'confirmado', '9a4e7c2b5d1f8603be92a4d7c1f5b30f', 4, 3, 'ninguna', NULL, NULL),
(14, 1, NULL, 'Staff Conecta', 'staff@conectaeventos.mx', '+52 55 1000 0014', 'staff', 'confirmado', 'b5d8f2a6c9e14703ad61b8e4f2c7a910', 4, 3, 'ninguna', NULL, NULL),
(15, 2, NULL, 'Laura Domínguez', 'laura.dominguez@novatech.example', '+52 55 2000 0001', 'vip', 'confirmado', '0f1e2d3c4b5a69788796a5b4c3d2e1f0', 5, 2, 'ninguna', NULL, 'Dirección general'),
(16, 2, NULL, 'Sergio Ibarra', 'sergio.ibarra@novatech.example', '+52 55 2000 0002', 'general', 'confirmado', '1f2e3d4c5b6a79880a1b2c3d4e5f6071', 6, 2, 'vegetariano', NULL, NULL),
(17, 2, NULL, 'Karla Núñez', 'karla.nunez@novatech.example', '+52 55 2000 0003', 'general', 'confirmado', '2f3e4d5c6b7a89891b2c3d4e5f607182', 6, 2, 'sin_gluten', NULL, NULL),
(18, 2, NULL, 'Emilio Cárdenas', 'emilio.cardenas@novatech.example', '+52 55 2000 0004', 'general', 'pendiente', '3f4e5d6c7b8a999a2c3d4e5f60718293', NULL, 2, 'ninguna', NULL, NULL),
(19, 2, NULL, 'Patricia Luna', 'patricia.luna@novatech.example', '+52 55 2000 0005', 'general', 'confirmado', '4f5e6d7c8b9a0aab3d4e5f60718293a4', 7, 2, 'vegano', 'Soya', NULL),
(20, 2, NULL, 'Andrés Pineda', 'andres.pineda@novatech.example', '+52 55 2000 0006', 'general', 'confirmado', '5f6e7d8c9b0a1bbc4e5f60718293a4b5', 7, 2, 'ninguna', NULL, NULL),
(21, 2, NULL, 'Mónica Salas', 'monica.salas@novatech.example', '+52 55 2000 0007', 'prensa', 'confirmado', '6f7e8d9c0b1a2ccd5f60718293a4b5c6', 5, 2, 'ninguna', NULL, NULL),
(22, 2, NULL, 'Diego Fuentes', 'diego.fuentes@novatech.example', '+52 55 2000 0008', 'staff', 'confirmado', '7f8e9d0c1b2a3dde60718293a4b5c6d7', NULL, 2, 'ninguna', NULL, NULL),
(23, 5, NULL, 'Familia Aguirre', 'familia.aguirre@example.com', '+52 55 3000 0001', 'familia', 'confirmado', '8f9e0d1c2b3a4eef718293a4b5c6d7e8', 9, 5, 'ninguna', NULL, 'Cuatro lugares'),
(24, 5, NULL, 'Familia Becerra', 'familia.becerra@example.com', '+52 55 3000 0002', 'familia', 'confirmado', '9f0e1d2c3b4a5ff08293a4b5c6d7e8f9', 9, 5, 'ninguna', NULL, NULL),
(25, 5, NULL, 'Familia Cárdenas', 'familia.cardenas@example.com', '+52 55 3000 0003', 'familia', 'confirmado', 'a0f1e2d3c4b5a0019384b5c6d7e8f9a0', 10, 5, 'ninguna', NULL, NULL),
(26, 5, NULL, 'Familia Duarte', 'familia.duarte@example.com', '+52 55 3000 0004', 'familia', 'pendiente', 'b1f2e3d4c5a6b102a495c6d7e8f9a0b1', NULL, 5, 'ninguna', NULL, NULL),
(27, 7, NULL, 'Ricardo Beltrán', 'ricardo.beltran@example.com', '+52 55 9090 1010', 'vip', 'confirmado', 'c2f3e4d5c6b7a203b5a6d7e8f9a0b1c2', NULL, 6, 'ninguna', NULL, 'Anfitrión'),
(28, 7, NULL, 'Sofía Beltrán', 'sofia.beltran@example.com', '+52 55 9090 1011', 'familia', 'confirmado', 'd3f4e5d6c7b8a304c6b7e8f9a0b1c2d3', NULL, 6, 'vegetariano', NULL, NULL),
(29, 7, NULL, 'Gerardo Lomelí', 'gerardo.lomeli@example.com', '+52 55 9090 1012', 'general', 'confirmado', 'e4f5e6d7c8b9a405d7c8e9f0a1b2c3d4', NULL, 6, 'ninguna', NULL, NULL),
(30, 7, NULL, 'María José Ponce', 'maria.ponce@example.com', '+52 55 9090 1013', 'general', 'confirmado', 'f5f6e7d8c9b0a506e8d9f0a1b2c3d4e5', NULL, 6, 'sin_gluten', NULL, NULL);

UPDATE invitados SET checkin_en = '2026-09-12 19:05:00', checkin_por = 6 WHERE id_invitado IN (27, 28, 29);
UPDATE invitados SET checkin_en = '2026-09-12 19:22:00', checkin_por = 6 WHERE id_invitado = 30;

-- ------------------------------------------------------------
-- Reservas de espacio, recursos e insumos
-- ------------------------------------------------------------
INSERT INTO reservas_espacio (id_reserva_espacio, id_evento, id_espacio, fecha_inicio, fecha_fin, estado, id_usuario) VALUES
(1, 1, 2, '2027-02-14 08:00:00', '2027-02-14 23:59:00', 'confirmada', 5),
(2, 2, 1, '2026-12-05 08:00:00', '2026-12-05 23:59:00', 'confirmada', 5),
(3, 3, 3, '2027-03-18 07:00:00', '2027-03-20 23:59:00', 'confirmada', 5),
(4, 4, 1, '2027-04-24 08:00:00', '2027-04-24 23:59:00', 'confirmada', 5),
(5, 5, 2, '2026-12-18 08:00:00', '2026-12-18 23:59:00', 'confirmada', 5),
(6, 7, 1, '2026-09-12 08:00:00', '2026-09-12 23:59:00', 'confirmada', 5);

INSERT INTO reserva_espacio_dias (id_reserva_espacio, id_espacio, fecha, activa) VALUES
(1, 2, '2027-02-14', 1),
(2, 1, '2026-12-05', 1),
(3, 3, '2027-03-18', 1), (3, 3, '2027-03-19', 1), (3, 3, '2027-03-20', 1),
(4, 1, '2027-04-24', 1),
(5, 2, '2026-12-18', 1),
(6, 1, '2026-09-12', 1);

INSERT INTO reservas_recurso (id_reserva_recurso, id_evento, id_recurso, fecha_inicio, fecha_fin, cantidad, estado, id_usuario) VALUES
(1, 1, 1, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 220, 'confirmada', 5),
(2, 1, 2, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 22, 'confirmada', 5),
(3, 1, 4, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 1, 'confirmada', 5),
(4, 1, 5, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 1, 'confirmada', 5),
(5, 1, 6, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 18, 'confirmada', 5),
(6, 1, 8, '2027-02-14 18:00:00', '2027-02-15 01:00:00', 22, 'confirmada', 5),
(7, 2, 1, '2026-12-05 19:00:00', '2026-12-06 01:00:00', 140, 'confirmada', 5),
(8, 2, 2, '2026-12-05 19:00:00', '2026-12-06 01:00:00', 14, 'confirmada', 5),
(9, 2, 4, '2026-12-05 19:00:00', '2026-12-06 01:00:00', 1, 'confirmada', 5),
(10, 2, 6, '2026-12-05 19:00:00', '2026-12-06 01:00:00', 12, 'confirmada', 5),
(11, 3, 1, '2027-03-18 09:00:00', '2027-03-20 18:00:00', 300, 'confirmada', 5),
(12, 3, 4, '2027-03-18 09:00:00', '2027-03-20 18:00:00', 2, 'confirmada', 5),
(13, 3, 5, '2027-03-18 09:00:00', '2027-03-20 18:00:00', 2, 'confirmada', 5),
(14, 3, 6, '2027-03-18 09:00:00', '2027-03-20 18:00:00', 8, 'confirmada', 5),
(15, 4, 1, '2027-04-24 17:00:00', '2027-04-25 01:00:00', 120, 'confirmada', 5),
(16, 4, 2, '2027-04-24 17:00:00', '2027-04-25 01:00:00', 12, 'confirmada', 5),
(17, 4, 4, '2027-04-24 17:00:00', '2027-04-25 01:00:00', 1, 'confirmada', 5),
(18, 4, 6, '2027-04-24 17:00:00', '2027-04-25 01:00:00', 12, 'confirmada', 5),
(19, 4, 8, '2027-04-24 17:00:00', '2027-04-25 01:00:00', 12, 'confirmada', 5),
(20, 5, 1, '2026-12-18 18:00:00', '2026-12-19 00:00:00', 320, 'confirmada', 5),
(21, 5, 2, '2026-12-18 18:00:00', '2026-12-19 00:00:00', 32, 'confirmada', 5),
(22, 5, 4, '2026-12-18 18:00:00', '2026-12-19 00:00:00', 2, 'confirmada', 5),
(23, 5, 6, '2026-12-18 18:00:00', '2026-12-19 00:00:00', 20, 'confirmada', 5),
(24, 5, 3, '2026-12-18 18:00:00', '2026-12-19 00:00:00', 32, 'confirmada', 5),
(25, 7, 1, '2026-09-12 19:00:00', '2026-09-13 01:00:00', 80, 'confirmada', 5),
(26, 7, 2, '2026-09-12 19:00:00', '2026-09-13 01:00:00', 8, 'confirmada', 5),
(27, 7, 4, '2026-09-12 19:00:00', '2026-09-13 01:00:00', 1, 'confirmada', 5);

INSERT INTO reservas_insumo (id_reserva_insumo, id_evento, id_insumo, cantidad, estado, id_usuario) VALUES
(1, 1, 1, 200.00, 'confirmada', 4),
(2, 1, 2, 200.00, 'confirmada', 4),
(3, 1, 3, 200.00, 'confirmada', 4),
(4, 1, 5, 40.00, 'confirmada', 4),
(5, 2, 1, 140.00, 'confirmada', 4),
(6, 2, 2, 140.00, 'confirmada', 4),
(7, 2, 3, 140.00, 'confirmada', 4),
(8, 2, 4, 140.00, 'confirmada', 4);

INSERT INTO movimientos_insumo (id_insumo, id_evento, tipo, cantidad, stock_fisico_resultante, stock_comprometido_resultante, motivo, id_usuario, fecha_movimiento) VALUES
(1, NULL, 'entrada', 600.00, 600.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:00:00'),
(2, NULL, 'entrada', 500.00, 500.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:05:00'),
(3, NULL, 'entrada', 400.00, 400.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:10:00'),
(4, NULL, 'entrada', 500.00, 500.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:15:00'),
(5, NULL, 'entrada', 120.00, 120.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:20:00'),
(6, NULL, 'entrada', 80.00, 80.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:25:00'),
(7, NULL, 'entrada', 200.00, 200.00, 0.00, 'Compra inicial de temporada', 4, '2026-09-01 09:30:00'),
(1, 1, 'reserva', 200.00, 600.00, 200.00, 'Apartado menú Banquete Imperial', 4, '2026-10-01 10:00:00'),
(2, 1, 'reserva', 200.00, 500.00, 200.00, 'Apartado menú Banquete Imperial', 4, '2026-10-01 10:01:00'),
(3, 1, 'reserva', 200.00, 400.00, 200.00, 'Apartado menú Banquete Imperial', 4, '2026-10-01 10:02:00'),
(5, 1, 'reserva', 40.00, 120.00, 40.00, 'Vino para banquete', 4, '2026-10-01 10:03:00'),
(1, 2, 'reserva', 140.00, 600.00, 340.00, 'Apartado buffet Gala NovaTech', 4, '2026-10-12 11:00:00'),
(2, 2, 'reserva', 140.00, 500.00, 340.00, 'Apartado buffet Gala NovaTech', 4, '2026-10-12 11:01:00'),
(3, 2, 'reserva', 140.00, 400.00, 340.00, 'Apartado buffet Gala NovaTech', 4, '2026-10-12 11:02:00'),
(4, 2, 'reserva', 140.00, 500.00, 140.00, 'Agua para gala corporativa', 4, '2026-10-12 11:03:00'),
(1, 7, 'consumo', 80.00, 520.00, 0.00, 'Consumo evento realizado', 4, '2026-09-13 02:00:00'),
(2, 7, 'consumo', 80.00, 420.00, 0.00, 'Consumo evento realizado', 4, '2026-09-13 02:01:00'),
(3, 7, 'consumo', 80.00, 320.00, 0.00, 'Consumo evento realizado', 4, '2026-09-13 02:02:00');

-- ------------------------------------------------------------
-- Cotizaciones, pagos e historial de precios
-- ------------------------------------------------------------
INSERT INTO cotizaciones (id_cotizacion, id_evento, folio, version, subtotal, descuento, impuestos, total, anticipo_requerido, estado, vigencia_hasta, notas, congelada_en, aceptada_en, id_usuario) VALUES
(1, 1, 'COT-2026-0001', 2, 344500.00, 10000.00, 53520.00, 388020.00, 116406.00, 'aceptada', '2026-11-15', 'Precio congelado al aceptar. Incluye montaje y desmontaje.', '2026-10-18 12:00:00', '2026-10-18 11:58:00', 2),
(2, 2, 'COT-2026-0002', 1, 182600.00, 0.00, 29216.00, 211816.00, 63544.80, 'aceptada', '2026-10-30', 'Incluye pantalla LED y créditos.', '2026-10-05 09:30:00', '2026-10-05 09:28:00', 2),
(3, 3, 'COT-2026-0003', 1, 242600.00, 0.00, 38816.00, 281416.00, 84424.80, 'enviada', '2026-12-15', 'Pendiente de orden de compra del instituto.', NULL, NULL, 2),
(4, 4, 'COT-2026-0004', 1, 190000.00, 5000.00, 29600.00, 214600.00, 64380.00, 'aceptada', '2026-12-20', 'Mesa de honor y cobertura foto/video.', '2026-10-22 16:00:00', '2026-10-22 15:58:00', 2),
(5, 5, 'COT-2026-0005', 1, 365800.00, 0.00, 58528.00, 424328.00, 127298.40, 'aceptada', '2026-11-30', 'Boletos y control de accesos incluidos.', '2026-10-08 10:00:00', '2026-10-08 09:57:00', 2),
(6, 7, 'COT-2026-0006', 1, 111200.00, 0.00, 17792.00, 128992.00, 38697.60, 'aceptada', '2026-08-30', 'Evento realizado y liquidado.', '2026-08-20 12:00:00', '2026-08-20 11:55:00', 2);

INSERT INTO cotizacion_detalle (id_cotizacion, tipo_concepto, id_referencia, descripcion, cantidad, precio_catalogo, precio_unitario, subtotal) VALUES
(1, 'espacio', 2, 'Gran Salón Imperial · montaje y desmontaje', 1, 55000.00, 55000.00, 55000.00),
(1, 'menu', 1, 'Banquete Imperial · 200 personas', 200, 1250.00, 1250.00, 250000.00),
(1, 'servicio', 1, 'Coordinación integral', 1, 18000.00, 18000.00, 18000.00),
(1, 'servicio', 2, 'Audio e iluminación', 1, 12000.00, 12000.00, 12000.00),
(1, 'servicio', 3, 'Decoración floral', 1, 9500.00, 9500.00, 9500.00),
(2, 'espacio', 1, 'Salón Aurora · montaje corporativo', 1, 28000.00, 28000.00, 28000.00),
(2, 'menu', 2, 'Buffet de Autor · 140 personas', 140, 890.00, 890.00, 124600.00),
(2, 'servicio', 1, 'Coordinación integral', 1, 18000.00, 18000.00, 18000.00),
(2, 'servicio', 2, 'Audio e iluminación', 1, 12000.00, 12000.00, 12000.00),
(3, 'espacio', 3, 'Galería Central · 3 días', 3, 38000.00, 38000.00, 114000.00),
(3, 'menu', 4, 'Coffee Break Premium · 280 personas', 280, 320.00, 320.00, 89600.00),
(3, 'servicio', 2, 'Audio e iluminación por día', 3, 7000.00, 7000.00, 21000.00),
(3, 'servicio', 1, 'Coordinación integral', 1, 18000.00, 18000.00, 18000.00),
(4, 'espacio', 1, 'Salón Aurora · XV años', 1, 28000.00, 28000.00, 28000.00),
(4, 'menu', 1, 'Banquete Imperial · 110 personas', 110, 1250.00, 1250.00, 137500.00),
(4, 'servicio', 3, 'Decoración floral', 1, 9500.00, 9500.00, 9500.00),
(4, 'servicio', 4, 'Fotografía y video', 1, 15000.00, 15000.00, 15000.00),
(5, 'espacio', 2, 'Gran Salón Imperial · graduación', 1, 55000.00, 55000.00, 55000.00),
(5, 'menu', 2, 'Buffet de Autor · 320 personas', 320, 890.00, 890.00, 284800.00),
(5, 'servicio', 1, 'Coordinación integral', 1, 18000.00, 18000.00, 18000.00),
(5, 'servicio', 5, 'Seguridad privada', 1, 8000.00, 8000.00, 8000.00),
(6, 'espacio', 1, 'Salón Aurora · aniversario', 1, 28000.00, 28000.00, 28000.00),
(6, 'menu', 2, 'Buffet de Autor · 80 personas', 80, 890.00, 890.00, 71200.00),
(6, 'servicio', 2, 'Audio e iluminación', 1, 12000.00, 12000.00, 12000.00);

INSERT INTO pagos (id_pago, id_cotizacion, tipo, monto, metodo, referencia, estado, fecha_pago, notas, id_usuario) VALUES
(1, 1, 'anticipo', 116406.00, 'transferencia', 'SPEI-88421', 'aplicado', '2026-10-19 10:15:00', 'Anticipo 30% para bloqueo de fecha', 7),
(2, 1, 'abono', 71614.00, 'transferencia', 'SPEI-90112', 'aplicado', '2026-10-28 17:40:00', 'Abono adicional solicitado por la clienta', 7),
(3, 2, 'anticipo', 63544.80, 'transferencia', 'SPEI-87220', 'aplicado', '2026-10-06 09:20:00', 'Anticipo de gala corporativa', 7),
(4, 4, 'anticipo', 64380.00, 'tarjeta', 'TPV-4412', 'aplicado', '2026-10-23 13:10:00', 'Anticipo XV años', 7),
(5, 5, 'anticipo', 127298.40, 'transferencia', 'SPEI-89990', 'aplicado', '2026-10-09 11:00:00', 'Anticipo graduación', 7),
(6, 6, 'anticipo', 38697.60, 'transferencia', 'SPEI-80112', 'aplicado', '2026-08-21 10:00:00', 'Anticipo aniversario', 7),
(7, 6, 'saldo', 90294.40, 'transferencia', 'SPEI-82001', 'aplicado', '2026-09-10 16:30:00', 'Liquidación previa al evento', 7);

INSERT INTO historial_precios (entidad, id_registro, precio_anterior, precio_nuevo, motivo, id_usuario, fecha) VALUES
('menus', 1, 1150.00, 1250.00, 'Ajuste anual de costos de proveeduría', 4, '2026-09-15 10:00:00'),
('menus', 2, 850.00, 890.00, 'Actualización de temporada', 4, '2026-09-15 10:05:00'),
('espacios', 2, 52000.00, 55000.00, 'Remodelación de escenario', 5, '2026-08-01 09:00:00');

INSERT INTO politicas_cancelacion (dias_antes, porcentaje, descripcion) VALUES
(0, 100.00, 'Menos de 7 días: no reembolsable'),
(7, 50.00, 'Entre 7 y 14 días: 50% de penalización'),
(15, 30.00, 'Entre 15 y 29 días: 30% de penalización'),
(30, 0.00, '30 días o más: sin penalización');

-- ------------------------------------------------------------
-- Bitácora e historial de ejemplo
-- ------------------------------------------------------------
INSERT INTO historial_cambios (entidad, id_registro, campo, valor_anterior, valor_nuevo, motivo, id_usuario, fecha_cambio) VALUES
('eventos', 1, 'fecha_inicio', '2027-02-13 18:00:00', '2027-02-14 18:00:00', 'Ajuste solicitado por la familia de la novia', 2, '2026-10-17 10:30:00'),
('eventos', 1, 'fecha_fin', '2027-02-14 01:00:00', '2027-02-15 01:00:00', 'Ajuste solicitado por la familia de la novia', 2, '2026-10-17 10:30:00'),
('reservas_espacio', 1, 'fecha_inicio', '2027-02-13 08:00:00', '2027-02-14 08:00:00', 'Reprogramación por cambio de fecha del evento', 3, '2026-10-17 10:31:00'),
('cotizaciones', 1, 'total', '402120.00', '388020.00', 'Descuento comercial autorizado', 2, '2026-10-18 11:50:00'),
('menus', 1, 'precio_persona', '1150.00', '1250.00', 'Ajuste anual de costos', 4, '2026-09-15 10:00:00');

INSERT INTO bitacora (id_usuario, accion, entidad, id_registro, detalle, ip, user_agent, fecha) VALUES
(2, 'CREAR_EVENTO', 'eventos', 1, 'Se registró la boda de Aurora y Diego', '127.0.0.1', 'Demo', '2026-09-28 10:00:00'),
(5, 'RESERVAR_ESPACIO', 'reservas_espacio', 1, 'Gran Salón Imperial reservado para 2027-02-14', '127.0.0.1', 'Demo', '2026-09-28 10:20:00'),
(2, 'ACEPTAR_COTIZACION', 'cotizaciones', 1, 'Cotización COT-2026-0001 aceptada con precio congelado', '127.0.0.1', 'Demo', '2026-10-18 11:58:00'),
(7, 'REGISTRAR_PAGO', 'pagos', 1, 'Anticipo de $116,406.00 registrado', '127.0.0.1', 'Demo', '2026-10-19 10:15:00'),
(4, 'APARTAR_INSUMOS', 'reservas_insumo', 1, 'Insumos del Banquete Imperial apartados', '127.0.0.1', 'Demo', '2026-10-01 10:00:00'),
(3, 'CANCELAR_RESERVA', 'reservas_recurso', 99, 'Intento de cancelación rechazado: la reserva ya estaba cancelada', '127.0.0.1', 'Demo', '2026-10-25 12:00:00'),
(8, 'REVISAR_INCONSISTENCIA', 'reportes', NULL, 'Auditoría cruzada de disponibilidad sin hallazgos críticos', '127.0.0.1', 'Demo', '2026-10-27 09:00:00');

-- ------------------------------------------------------------
-- Prospectos del sitio público
-- ------------------------------------------------------------
INSERT INTO leads (nombre, correo, telefono, tipo_evento, fecha_estimada, aforo, mensaje, origen, estado, creado_en) VALUES
('Regina Alcántara', 'regina.alcantara@example.com', '+52 55 6060 7070', 'Boda', '2027-06-12', 180, 'Busco salón para boda con ceremonia al aire libre.', 'bodas', 'nuevo', '2026-10-26 12:00:00'),
('Grupo Altura', 'eventos@grupoaltura.example', '+52 55 7070 8080', 'Corporativo', '2026-12-20', 120, 'Cena de fin de año con premiación.', 'corporativo', 'contactado', '2026-10-24 09:30:00'),
('Mariana Lozano', 'mariana.lozano@example.com', '+52 55 8080 9090', 'XV Años', '2027-07-17', 140, 'Necesito mesa de honor y pista de baile.', 'bodas', 'nuevo', '2026-10-28 18:15:00');

SET FOREIGN_KEY_CHECKS = 1;
