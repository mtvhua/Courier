-- =====================================================================
--  Envíos Expresso — Courier
--  Base de datos PostgreSQL
--  Ejecutar conectado a la base cc6:   psql -U postgres -d cc6 -f database.sql
-- =====================================================================

DROP TABLE IF EXISTS Paquete CASCADE;
DROP TABLE IF EXISTS Ruta CASCADE;
DROP TABLE IF EXISTS Ciudad CASCADE;
DROP TABLE IF EXISTS Tienda CASCADE;
DROP TABLE IF EXISTS Usuario CASCADE;
DROP TYPE  IF EXISTS estado_paquete;

-- ---------------------------------------------------------------------
-- Ciudades (destino = código de 5 caracteres según el enunciado)
-- ---------------------------------------------------------------------
CREATE TABLE Ciudad (
    codigo_postal   VARCHAR(5)    PRIMARY KEY CHECK (length(codigo_postal) = 5),
    nombre          VARCHAR(100)  NOT NULL
);

-- ---------------------------------------------------------------------
-- Ruta: costo de manejo y envío desde el origen hacia cada destino
-- ---------------------------------------------------------------------
CREATE TABLE Ruta (
    id_ruta         SERIAL        PRIMARY KEY,
    codigo_origen   VARCHAR(5)    NOT NULL REFERENCES Ciudad(codigo_postal),
    codigo_destino  VARCHAR(5)    NOT NULL REFERENCES Ciudad(codigo_postal) ON DELETE CASCADE,
    precio          NUMERIC(10,2) NOT NULL CHECK (precio >= 0),
    UNIQUE (codigo_origen, codigo_destino)
);

-- ---------------------------------------------------------------------
-- Usuarios del sitio (clientes y administradores)
-- ---------------------------------------------------------------------
CREATE TABLE Usuario (
    id_usuario      SERIAL        PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL UNIQUE,
    contrasena      VARCHAR(255)  NOT NULL,          -- hash bcrypt (password_hash)
    es_admin        BOOLEAN       NOT NULL DEFAULT FALSE
);

-- ---------------------------------------------------------------------
-- Tiendas virtuales autorizadas a usar el WebService
-- ---------------------------------------------------------------------
CREATE TABLE Tienda (
    id_tienda       VARCHAR(15)   PRIMARY KEY,       -- 15 caracteres según enunciado
    nombre          VARCHAR(100)  NOT NULL,
    host            VARCHAR(255)  NOT NULL
);

-- ---------------------------------------------------------------------
-- Estados del envío (1..5)
-- ---------------------------------------------------------------------
CREATE TYPE estado_paquete AS ENUM (
    'orden nueva',
    'surtiendose',
    'empacandose',
    'en ruta',
    'entregada'
);

-- ---------------------------------------------------------------------
-- Paquete / envío contratado
--   * Lo crea una tienda vía WebService (id_tienda + numero_orden)
--   * o un usuario desde el sitio (id_usuario)
-- ---------------------------------------------------------------------
CREATE TABLE Paquete (
    id_paquete      SERIAL          PRIMARY KEY,
    tracking_id     VARCHAR(20)     NOT NULL UNIQUE,
    numero_orden    VARCHAR(50),
    destinatario    VARCHAR(150)    NOT NULL,
    direccion       VARCHAR(255)    NOT NULL,
    estado          estado_paquete  NOT NULL DEFAULT 'orden nueva',
    peso            NUMERIC(6,2)    CHECK (peso > 0),   -- opcional (las tiendas no lo envían)
    tamano          VARCHAR(20),

    id_ruta         INTEGER         NOT NULL REFERENCES Ruta(id_ruta),
    id_usuario      INTEGER         REFERENCES Usuario(id_usuario) ON DELETE SET NULL,
    id_tienda       VARCHAR(15)     REFERENCES Tienda(id_tienda),

    UNIQUE (numero_orden, id_tienda)      -- una tienda no repite número de orden
);

CREATE INDEX idx_paquete_usuario   ON Paquete(id_usuario);
CREATE INDEX idx_paquete_estado    ON Paquete(estado);
CREATE INDEX idx_ruta_destino      ON Ruta(codigo_destino);

-- =====================================================================
--  Datos iniciales
-- =====================================================================
INSERT INTO Ciudad (codigo_postal, nombre) VALUES
    ('01001', 'Guatemala'),
    ('02001', 'El Progreso'),
    ('03001', 'Sacatepéquez'),
    ('04001', 'Chimaltenango'),
    ('05001', 'Escuintla'),
    ('06001', 'Santa Rosa'),
    ('07001', 'Sololá'),
    ('08001', 'Totonicapán'),
    ('09001', 'Quetzaltenango'),
    ('10001', 'Suchitepéquez'),
    ('11001', 'Retalhuleu'),
    ('12001', 'San Marcos'),
    ('13001', 'Huehuetenango'),
    ('14001', 'Quiché'),
    ('15001', 'Baja Verapaz'),
    ('16001', 'Alta Verapaz'),
    ('17001', 'Petén'),
    ('18001', 'Izabal'),
    ('19001', 'Zacapa'),
    ('20001', 'Chiquimula'),
    ('21001', 'Jalapa'),
    ('22001', 'Jutiapa');

INSERT INTO Ruta (codigo_origen, codigo_destino, precio) VALUES
    ('01001', '02001', 20.00),
    ('01001', '03001', 15.00),
    ('01001', '04001', 20.00),
    ('01001', '05001', 20.00),
    ('01001', '06001', 25.00),
    ('01001', '07001', 30.00),
    ('01001', '08001', 35.00),
    ('01001', '09001', 35.00),
    ('01001', '10001', 30.00),
    ('01001', '11001', 35.00),
    ('01001', '12001', 45.00),
    ('01001', '13001', 45.00),
    ('01001', '14001', 40.00),
    ('01001', '15001', 30.00),
    ('01001', '16001', 40.00),
    ('01001', '17001', 65.00),
    ('01001', '18001', 50.00),
    ('01001', '19001', 35.00),
    ('01001', '20001', 35.00),
    ('01001', '21001', 25.00),
    ('01001', '22001', 30.00);

-- Administrador inicial:  usuario PerrY  /  contraseña PerrY
INSERT INTO Usuario (nombre, contrasena, es_admin) VALUES
    ('PerrY', '$2y$10$sxOPafNeItz22B7JZjJDmu.YEGhgko9UtJVdOF.m84PiH3SA7D2Wy', TRUE);

-- Tienda de prueba (cámbiala por las de los otros grupos)
INSERT INTO Tienda (id_tienda, nombre, host) VALUES
    ('TIENDAPRUEBA001', 'Tienda de Prueba', 'http://localhost');
