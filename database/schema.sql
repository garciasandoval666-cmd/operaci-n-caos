-- ============================================================
--  ConectaEventos · esquema de base de datos
--  MySQL 8.0+ / MariaDB 10.4+
--  Operación Caos Digital · módulos A, B, C y D integrados
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-06:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_cotizacion_saldo;
DROP VIEW IF EXISTS v_disponibilidad_insumo;
DROP VIEW IF EXISTS v_ocupacion_espacio;
DROP VIEW IF EXISTS v_resumen_evento;

DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS politicas_cancelacion;
DROP TABLE IF EXISTS historial_precios;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS cotizacion_detalle;
DROP TABLE IF EXISTS cotizaciones;
DROP TABLE IF EXISTS movimientos_insumo;
DROP TABLE IF EXISTS reservas_insumo;
DROP TABLE IF EXISTS insumos_menu;
DROP TABLE IF EXISTS reservas_recurso;
DROP TABLE IF EXISTS reserva_espacio_dias;
DROP TABLE IF EXISTS reservas_espacio;
DROP TABLE IF EXISTS recursos;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS espacios;
DROP TABLE IF EXISTS historial_cambios;
DROP TABLE IF EXISTS invitados;
DROP TABLE IF EXISTS evento_menus;
DROP TABLE IF EXISTS menu_platillos;
DROP TABLE IF EXISTS menus;
DROP TABLE IF EXISTS platillo_alergenos;
DROP TABLE IF EXISTS platillos;
DROP TABLE IF EXISTS alergenos;
DROP TABLE IF EXISTS mesas;
DROP TABLE IF EXISTS evento_programa;
DROP TABLE IF EXISTS evento_servicios;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS tipos_evento;
DROP TABLE IF EXISTS bitacora;
DROP TABLE IF EXISTS configuracion;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- D · Seguridad, usuarios y auditoría
-- ------------------------------------------------------------
CREATE TABLE roles (
  id_rol      INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(40) NOT NULL UNIQUE,
  descripcion VARCHAR(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
  id_rol        INT NOT NULL,
  nombre        VARCHAR(120) NOT NULL,
  correo        VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  telefono      VARCHAR(25),
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acceso DATETIME NULL,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracion (
  clave       VARCHAR(60) PRIMARY KEY,
  valor       VARCHAR(255) NOT NULL,
  descripcion VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bitacora (
  id_bitacora INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario  INT NULL,
  accion      VARCHAR(80) NOT NULL,
  entidad     VARCHAR(60) NOT NULL DEFAULT '',
  id_registro INT NULL,
  detalle     VARCHAR(2000) NOT NULL DEFAULT '',
  ip          VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bitacora_fecha (fecha),
  INDEX idx_bitacora_entidad (entidad, id_registro),
  INDEX idx_bitacora_accion_fecha (accion, fecha),
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- A · Clientes y eventos
-- ------------------------------------------------------------
CREATE TABLE tipos_evento (
  id_tipo_evento      INT AUTO_INCREMENT PRIMARY KEY,
  nombre              VARCHAR(60) NOT NULL UNIQUE,
  descripcion         VARCHAR(255),
  requiere_mesas      TINYINT(1) NOT NULL DEFAULT 0,
  requiere_menu       TINYINT(1) NOT NULL DEFAULT 0,
  requiere_rsvp       TINYINT(1) NOT NULL DEFAULT 1,
  acompanantes_default TINYINT NOT NULL DEFAULT 0,
  icono               VARCHAR(40) NOT NULL DEFAULT 'calendario',
  activo              TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id_cliente   INT AUTO_INCREMENT PRIMARY KEY,
  tipo_cliente ENUM('persona','empresa') NOT NULL DEFAULT 'persona',
  nombre       VARCHAR(120) NOT NULL,
  razon_social VARCHAR(150) NULL,
  correo       VARCHAR(120) NOT NULL UNIQUE,
  telefono     VARCHAR(25),
  direccion    VARCHAR(220),
  notas        VARCHAR(500),
  estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clientes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicios (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  categoria   ENUM('banquete','audio_video','decoracion','mobiliario','entretenimiento','seguridad','limpieza','otro') NOT NULL,
  nombre      VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255),
  precio_base DECIMAL(10,2) NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_servicio_nombre (nombre),
  CHECK (precio_base >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos (
  id_evento          INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente         INT NOT NULL,
  id_tipo_evento     INT NOT NULL,
  nombre             VARCHAR(140) NOT NULL,
  descripcion        VARCHAR(500),
  fecha_inicio       DATETIME NOT NULL,
  fecha_fin          DATETIME NOT NULL,
  aforo_estimado     INT NOT NULL DEFAULT 1,
  aforo_maximo       INT NOT NULL DEFAULT 1,
  estado             ENUM('prospecto','cotizado','confirmado','en_preparacion','en_curso','realizado','cerrado','cancelado') NOT NULL DEFAULT 'prospecto',
  motivo_cancelacion VARCHAR(220) NULL,
  notas              TEXT,
  id_usuario_creador INT NULL,
  creado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CHECK (fecha_fin > fecha_inicio),
  CHECK (aforo_estimado >= 1),
  CHECK (aforo_maximo >= aforo_estimado),
  INDEX idx_eventos_fecha (fecha_inicio, fecha_fin),
  INDEX idx_eventos_estado (estado),
  CONSTRAINT fk_evento_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
  CONSTRAINT fk_evento_tipo FOREIGN KEY (id_tipo_evento) REFERENCES tipos_evento(id_tipo_evento),
  CONSTRAINT fk_evento_usuario FOREIGN KEY (id_usuario_creador) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento_servicios (
  id_evento   INT NOT NULL,
  id_servicio INT NOT NULL,
  cantidad    INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  notas       VARCHAR(200),
  PRIMARY KEY (id_evento, id_servicio),
  CHECK (cantidad > 0),
  CONSTRAINT fk_es_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_es_servicio FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento_programa (
  id_programa INT AUTO_INCREMENT PRIMARY KEY,
  id_evento   INT NOT NULL,
  inicio      DATETIME NOT NULL,
  fin         DATETIME NULL,
  actividad   VARCHAR(150) NOT NULL,
  responsable VARCHAR(100),
  INDEX idx_programa_evento (id_evento, inicio),
  CONSTRAINT fk_programa_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mesas (
  id_mesa   INT AUTO_INCREMENT PRIMARY KEY,
  id_evento INT NOT NULL,
  numero    VARCHAR(12) NOT NULL,
  capacidad TINYINT NOT NULL,
  zona      VARCHAR(40),
  UNIQUE KEY uq_mesa_evento (id_evento, numero),
  CHECK (capacidad > 0),
  CONSTRAINT fk_mesa_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alergenos (
  id_alergeno INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platillos (
  id_platillo     INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  tipo            ENUM('entrada','sopa','plato_fuerte','postre','bebida','snack') NOT NULL,
  descripcion     VARCHAR(255),
  es_vegetariano  TINYINT(1) NOT NULL DEFAULT 0,
  es_vegano       TINYINT(1) NOT NULL DEFAULT 0,
  activo          TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platillo_alergenos (
  id_platillo INT NOT NULL,
  id_alergeno INT NOT NULL,
  PRIMARY KEY (id_platillo, id_alergeno),
  CONSTRAINT fk_pa_platillo FOREIGN KEY (id_platillo) REFERENCES platillos(id_platillo) ON DELETE CASCADE,
  CONSTRAINT fk_pa_alergeno FOREIGN KEY (id_alergeno) REFERENCES alergenos(id_alergeno) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menus (
  id_menu       INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  tipo_servicio ENUM('plato_servido','buffet','coctel','coffee_break','barra_libre') NOT NULL,
  descripcion   VARCHAR(255),
  precio_persona DECIMAL(10,2) NOT NULL,
  minimo_personas INT NOT NULL DEFAULT 1,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  CHECK (precio_persona >= 0),
  CHECK (minimo_personas >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_platillos (
  id_menu     INT NOT NULL,
  id_platillo INT NOT NULL,
  PRIMARY KEY (id_menu, id_platillo),
  CONSTRAINT fk_mp_menu FOREIGN KEY (id_menu) REFERENCES menus(id_menu) ON DELETE CASCADE,
  CONSTRAINT fk_mp_platillo FOREIGN KEY (id_platillo) REFERENCES platillos(id_platillo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento_menus (
  id_evento_menu INT AUTO_INCREMENT PRIMARY KEY,
  id_evento      INT NOT NULL,
  id_menu        INT NOT NULL,
  personas       INT NOT NULL,
  notas          VARCHAR(200),
  UNIQUE KEY uq_evento_menu (id_evento, id_menu),
  CHECK (personas > 0),
  CONSTRAINT fk_em_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_em_menu FOREIGN KEY (id_menu) REFERENCES menus(id_menu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invitados (
  id_invitado   INT AUTO_INCREMENT PRIMARY KEY,
  id_evento     INT NOT NULL,
  id_titular    INT NULL,
  nombre        VARCHAR(120) NOT NULL,
  correo        VARCHAR(120) NULL,
  telefono      VARCHAR(25) NULL,
  categoria     ENUM('general','vip','familia','proveedor','prensa','staff') NOT NULL DEFAULT 'general',
  estado_rsvp   ENUM('pendiente','confirmado','declinado','lista_espera') NOT NULL DEFAULT 'pendiente',
  token_acceso  CHAR(32) NOT NULL UNIQUE,
  id_mesa       INT NULL,
  id_evento_menu INT NULL,
  restriccion_alimenticia ENUM('ninguna','vegetariano','vegano','sin_gluten','otra') NOT NULL DEFAULT 'ninguna',
  alergias      VARCHAR(220) NULL,
  checkin_en    DATETIME NULL,
  checkin_por   INT NULL,
  notas         VARCHAR(255),
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invitado_correo (id_evento, correo),
  INDEX idx_invitados_evento (id_evento, estado_rsvp),
  CONSTRAINT fk_invitado_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_invitado_titular FOREIGN KEY (id_titular) REFERENCES invitados(id_invitado) ON DELETE SET NULL,
  CONSTRAINT fk_invitado_mesa FOREIGN KEY (id_mesa) REFERENCES mesas(id_mesa) ON DELETE SET NULL,
  CONSTRAINT fk_invitado_menu FOREIGN KEY (id_evento_menu) REFERENCES evento_menus(id_evento_menu) ON DELETE SET NULL,
  CONSTRAINT fk_invitado_checkin FOREIGN KEY (checkin_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_cambios (
  id_historial   INT AUTO_INCREMENT PRIMARY KEY,
  entidad        VARCHAR(60) NOT NULL,
  id_registro    INT NOT NULL,
  campo          VARCHAR(60) NOT NULL,
  valor_anterior VARCHAR(255) NULL,
  valor_nuevo    VARCHAR(255) NULL,
  motivo         VARCHAR(220) NOT NULL DEFAULT '',
  id_usuario     INT NULL,
  fecha_cambio   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_historial_entidad (entidad, id_registro),
  INDEX idx_historial_fecha (fecha_cambio),
  CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- B · Espacios, recursos e insumos
-- ------------------------------------------------------------
CREATE TABLE espacios (
  id_espacio   INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(100) NOT NULL UNIQUE,
  tipo         ENUM('salon_fiesta','salon_gala','sala_exhibicion','terraza','jardin') NOT NULL,
  capacidad    INT NOT NULL,
  costo_referencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  descripcion  VARCHAR(255),
  estado       ENUM('activo','mantenimiento','inactivo') NOT NULL DEFAULT 'activo',
  CHECK (capacidad >= 1),
  CHECK (costo_referencia >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE proveedores (
  id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(120) NOT NULL UNIQUE,
  contacto     VARCHAR(120),
  telefono     VARCHAR(25),
  correo       VARCHAR(120),
  estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recursos (
  id_recurso    INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor  INT NULL,
  categoria     ENUM('mobiliario','personal','audio_video','decoracion','transporte','otro') NOT NULL,
  nombre        VARCHAR(100) NOT NULL,
  unidad        VARCHAR(20) NOT NULL DEFAULT 'pieza',
  existencias_total INT NOT NULL,
  costo_referencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado        ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  UNIQUE KEY uq_recurso_proveedor (id_proveedor, nombre),
  CHECK (existencias_total >= 0),
  CHECK (costo_referencia >= 0),
  CONSTRAINT fk_recurso_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas_espacio (
  id_reserva_espacio INT AUTO_INCREMENT PRIMARY KEY,
  id_evento          INT NOT NULL,
  id_espacio         INT NOT NULL,
  fecha_inicio       DATETIME NOT NULL,
  fecha_fin          DATETIME NOT NULL,
  estado             ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
  activa             TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'confirmada' THEN 1 ELSE NULL END) STORED,
  motivo_cancelacion VARCHAR(220) NULL,
  id_usuario         INT NOT NULL,
  creado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelado_en       DATETIME NULL,
  UNIQUE KEY uq_reserva_espacio_evento (id_evento, id_espacio, activa),
  CHECK (fecha_fin > fecha_inicio),
  CONSTRAINT fk_re_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_re_espacio FOREIGN KEY (id_espacio) REFERENCES espacios(id_espacio),
  CONSTRAINT fk_re_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un renglón por cada día ocupado. El UNIQUE impide empalmes.
CREATE TABLE reserva_espacio_dias (
  id_dia             INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva_espacio INT NOT NULL,
  id_espacio         INT NOT NULL,
  fecha              DATE NOT NULL,
  activa             TINYINT NULL DEFAULT 1,
  UNIQUE KEY uq_dia_espacio (id_espacio, fecha, activa),
  INDEX idx_dias_reserva (id_reserva_espacio),
  CONSTRAINT fk_dia_reserva FOREIGN KEY (id_reserva_espacio) REFERENCES reservas_espacio(id_reserva_espacio) ON DELETE CASCADE,
  CONSTRAINT fk_dia_espacio FOREIGN KEY (id_espacio) REFERENCES espacios(id_espacio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas_recurso (
  id_reserva_recurso INT AUTO_INCREMENT PRIMARY KEY,
  id_evento          INT NOT NULL,
  id_recurso         INT NOT NULL,
  fecha_inicio       DATETIME NOT NULL,
  fecha_fin          DATETIME NOT NULL,
  cantidad           INT NOT NULL,
  estado             ENUM('confirmada','cancelada') NOT NULL DEFAULT 'confirmada',
  activa             TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'confirmada' THEN 1 ELSE NULL END) STORED,
  motivo_cancelacion VARCHAR(220) NULL,
  id_usuario         INT NOT NULL,
  creado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelado_en       DATETIME NULL,
  UNIQUE KEY uq_reserva_recurso_evento (id_evento, id_recurso, activa),
  CHECK (cantidad > 0),
  CHECK (fecha_fin > fecha_inicio),
  INDEX idx_rr_disponibilidad (id_recurso, estado, fecha_inicio, fecha_fin),
  CONSTRAINT fk_rr_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_rr_recurso FOREIGN KEY (id_recurso) REFERENCES recursos(id_recurso),
  CONSTRAINT fk_rr_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE insumos_menu (
  id_insumo     INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(120) NOT NULL UNIQUE,
  categoria     ENUM('entrada','plato_fuerte','postre','bebida','abarrote','otro') NOT NULL,
  unidad        VARCHAR(20) NOT NULL DEFAULT 'porcion',
  stock_fisico  DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_comprometido DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_minimo  DECIMAL(10,2) NOT NULL DEFAULT 0,
  costo_referencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado        ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  CHECK (stock_fisico >= 0),
  CHECK (stock_comprometido >= 0),
  CHECK (stock_comprometido <= stock_fisico),
  CHECK (stock_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas_insumo (
  id_reserva_insumo INT AUTO_INCREMENT PRIMARY KEY,
  id_evento         INT NOT NULL,
  id_insumo         INT NOT NULL,
  cantidad          DECIMAL(10,2) NOT NULL,
  estado            ENUM('confirmada','consumida','cancelada') NOT NULL DEFAULT 'confirmada',
  activa            TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'confirmada' THEN 1 ELSE NULL END) STORED,
  id_usuario        INT NOT NULL,
  creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reserva_insumo_evento (id_evento, id_insumo, activa),
  CHECK (cantidad > 0),
  CONSTRAINT fk_ri_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_ri_insumo FOREIGN KEY (id_insumo) REFERENCES insumos_menu(id_insumo),
  CONSTRAINT fk_ri_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimientos_insumo (
  id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
  id_insumo     INT NOT NULL,
  id_evento     INT NULL,
  tipo          ENUM('entrada','reserva','liberacion','consumo','ajuste') NOT NULL,
  cantidad      DECIMAL(10,2) NOT NULL,
  stock_fisico_resultante DECIMAL(10,2) NOT NULL,
  stock_comprometido_resultante DECIMAL(10,2) NOT NULL,
  motivo        VARCHAR(220) NOT NULL,
  id_usuario    INT NOT NULL,
  fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mov_insumo (id_insumo, fecha_movimiento),
  CONSTRAINT fk_mov_insumo FOREIGN KEY (id_insumo) REFERENCES insumos_menu(id_insumo),
  CONSTRAINT fk_mov_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE SET NULL,
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- C · Cotizaciones y pagos
-- ------------------------------------------------------------
CREATE TABLE cotizaciones (
  id_cotizacion   INT AUTO_INCREMENT PRIMARY KEY,
  id_evento       INT NOT NULL,
  folio           VARCHAR(24) NOT NULL UNIQUE,
  version         INT NOT NULL DEFAULT 1,
  subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0,
  descuento       DECIMAL(12,2) NOT NULL DEFAULT 0,
  impuestos       DECIMAL(12,2) NOT NULL DEFAULT 0,
  total           DECIMAL(12,2) NOT NULL DEFAULT 0,
  anticipo_requerido DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado          ENUM('borrador','enviada','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'borrador',
  vigencia_hasta  DATE NULL,
  notas           VARCHAR(500),
  penalizacion    DECIMAL(12,2) NOT NULL DEFAULT 0,
  congelada_en    DATETIME NULL,
  aceptada_en     DATETIME NULL,
  cancelada_en    DATETIME NULL,
  motivo_cancelacion VARCHAR(220) NULL,
  id_usuario      INT NOT NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CHECK (subtotal >= 0),
  CHECK (total >= 0),
  INDEX idx_cotizacion_evento (id_evento),
  CONSTRAINT fk_cot_evento FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
  CONSTRAINT fk_cot_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cotizacion_detalle (
  id_detalle     INT AUTO_INCREMENT PRIMARY KEY,
  id_cotizacion  INT NOT NULL,
  tipo_concepto  ENUM('espacio','menu','servicio','recurso','otro') NOT NULL,
  id_referencia  INT NULL,
  descripcion    VARCHAR(200) NOT NULL,
  cantidad       DECIMAL(10,2) NOT NULL,
  precio_catalogo DECIMAL(10,2) NOT NULL DEFAULT 0,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal       DECIMAL(12,2) NOT NULL,
  CHECK (cantidad > 0),
  CHECK (precio_unitario >= 0),
  INDEX idx_detalle_cotizacion (id_cotizacion),
  CONSTRAINT fk_detalle_cotizacion FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos (
  id_pago        INT AUTO_INCREMENT PRIMARY KEY,
  id_cotizacion  INT NOT NULL,
  tipo           ENUM('anticipo','abono','saldo','devolucion','penalizacion') NOT NULL DEFAULT 'abono',
  monto          DECIMAL(12,2) NOT NULL,
  metodo         ENUM('efectivo','transferencia','tarjeta','cheque') NOT NULL DEFAULT 'transferencia',
  referencia     VARCHAR(80),
  estado         ENUM('registrado','aplicado','revertido') NOT NULL DEFAULT 'aplicado',
  fecha_pago     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notas          VARCHAR(300),
  id_usuario     INT NOT NULL,
  CHECK (monto > 0),
  INDEX idx_pagos_cotizacion (id_cotizacion),
  CONSTRAINT fk_pago_cotizacion FOREIGN KEY (id_cotizacion) REFERENCES cotizaciones(id_cotizacion) ON DELETE CASCADE,
  CONSTRAINT fk_pago_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_precios (
  id_historial_precio INT AUTO_INCREMENT PRIMARY KEY,
  entidad        VARCHAR(40) NOT NULL,
  id_registro    INT NOT NULL,
  precio_anterior DECIMAL(12,2) NOT NULL,
  precio_nuevo   DECIMAL(12,2) NOT NULL,
  motivo         VARCHAR(220) NOT NULL DEFAULT '',
  id_usuario     INT NULL,
  fecha          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_precio_entidad (entidad, id_registro),
  CONSTRAINT fk_precio_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE politicas_cancelacion (
  id_politica INT AUTO_INCREMENT PRIMARY KEY,
  dias_antes  INT NOT NULL,
  porcentaje  DECIMAL(5,2) NOT NULL,
  descripcion VARCHAR(200) NOT NULL,
  UNIQUE KEY uq_politica_dias (dias_antes),
  CHECK (dias_antes >= 0),
  CHECK (porcentaje BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Sitio público
-- ------------------------------------------------------------
CREATE TABLE leads (
  id_lead      INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(120) NOT NULL,
  correo       VARCHAR(120) NOT NULL,
  telefono     VARCHAR(25),
  tipo_evento  VARCHAR(60),
  fecha_estimada DATE NULL,
  aforo        INT NULL,
  mensaje      VARCHAR(1000),
  origen       ENUM('corporativo','bodas','contacto') NOT NULL DEFAULT 'contacto',
  estado       ENUM('nuevo','contactado','convertido','descartado') NOT NULL DEFAULT 'nuevo',
  creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leads_estado (estado, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Vistas de reporte (D)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW v_resumen_evento AS
SELECT e.id_evento,
       e.nombre,
       e.estado,
       e.fecha_inicio,
       e.fecha_fin,
       e.aforo_maximo,
       c.nombre AS cliente,
       t.nombre AS tipo_evento,
       (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento AND i.estado_rsvp = 'confirmado') AS confirmados,
       (SELECT COUNT(*) FROM invitados i WHERE i.id_evento = e.id_evento AND i.checkin_en IS NOT NULL) AS asistentes,
       (SELECT COALESCE(SUM(c.total), 0) FROM cotizaciones c WHERE c.id_evento = e.id_evento AND c.estado IN ('aceptada','enviada')) AS total_cotizado,
       (SELECT COALESCE(SUM(p.monto), 0) FROM pagos p
          JOIN cotizaciones c2 ON c2.id_cotizacion = p.id_cotizacion
         WHERE c2.id_evento = e.id_evento AND p.estado <> 'revertido' AND p.tipo <> 'devolucion') AS total_pagado
FROM eventos e
JOIN clientes c ON c.id_cliente = e.id_cliente
JOIN tipos_evento t ON t.id_tipo_evento = e.id_tipo_evento;

CREATE OR REPLACE VIEW v_cotizacion_saldo AS
SELECT c.id_cotizacion,
       c.id_evento,
       c.folio,
       c.total,
       c.estado,
       COALESCE(SUM(CASE WHEN p.tipo = 'devolucion' THEN -p.monto ELSE p.monto END), 0) AS pagado,
       c.total - COALESCE(SUM(CASE WHEN p.tipo = 'devolucion' THEN -p.monto ELSE p.monto END), 0) AS saldo
FROM cotizaciones c
LEFT JOIN pagos p ON p.id_cotizacion = c.id_cotizacion AND p.estado <> 'revertido'
GROUP BY c.id_cotizacion, c.id_evento, c.folio, c.total, c.estado;

CREATE OR REPLACE VIEW v_ocupacion_espacio AS
SELECT re.id_espacio,
       DATE_FORMAT(d.fecha, '%Y-%m') AS periodo,
       COUNT(*) AS dias_ocupados
FROM reserva_espacio_dias d
JOIN reservas_espacio re ON re.id_reserva_espacio = d.id_reserva_espacio
WHERE d.activa = 1
GROUP BY re.id_espacio, DATE_FORMAT(d.fecha, '%Y-%m');

CREATE OR REPLACE VIEW v_disponibilidad_insumo AS
SELECT id_insumo,
       nombre,
       unidad,
       stock_fisico,
       stock_comprometido,
       stock_fisico - stock_comprometido AS disponible,
       stock_minimo,
       (stock_fisico - stock_comprometido) <= stock_minimo AS bajo_minimo
FROM insumos_menu;
