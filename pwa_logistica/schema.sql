-- En hosting compartido (Hostinger y similares) el usuario de MySQL no tiene permiso
-- para crear bases de datos: créala primero desde el panel de hosting (te dará un
-- nombre con prefijo, ej. u916414417_sisLogistica), entra a ELLA en phpMyAdmin y
-- luego importa este archivo sin las líneas CREATE DATABASE / USE.
-- En local (XAMPP) sí puedes descomentar estas dos líneas:
-- CREATE DATABASE IF NOT EXISTS sisLogistica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sisLogistica;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'operador', 'admin') DEFAULT 'cliente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registros_accesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    nombre_operador VARCHAR(120) NOT NULL,
    identificacion_foto VARCHAR(255) NOT NULL,
    placas_unidad VARCHAR(20) NOT NULL,
    tipo_operacion ENUM('dejar', 'retirar', 'ambos') NOT NULL,
    num_contenedor VARCHAR(50) NOT NULL,
    linea_transporte VARCHAR(100) NOT NULL,
    correo_cliente VARCHAR(150) NOT NULL,
    codigo_qr VARCHAR(255) UNIQUE NOT NULL,
    estado ENUM('pendiente', 'completado', 'cancelado') DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Si ya tenías la base de datos creada antes de este cambio, ejecuta esta línea:
-- ALTER TABLE registros_accesos ADD COLUMN correo_cliente VARCHAR(150) NOT NULL AFTER linea_transporte;

CREATE TABLE eventos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento ENUM('creacion_qr', 'lectura_qr', 'operacion_completada', 'mensaje_enviado', 'mensaje_fallido', 'edicion_registro', 'eliminacion_registro') NOT NULL,
    registro_id INT NULL,
    usuario_id INT NULL,
    detalle VARCHAR(500) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registro_id) REFERENCES registros_accesos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Registro de intentos de login fallidos, para frenar fuerza bruta (ver api/login.php).
CREATE TABLE intentos_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(150) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identificador_fecha (identificador, fecha)
);

-- Único usuario administrador inicial.
-- cesarblgbt@gmail.com / GBT040806  (cámbiala luego desde el panel de admin)
INSERT INTO usuarios (id, nombre, email, password, rol) VALUES
    (1, 'Admin', 'cesarblgbt@gmail.com', '$2y$10$JqDEEgtvaLKZIyLp2EJrlObfXP.np0HvI2ZW4pizl0mPwE4qICxxa', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Si ya tenías la base de datos creada antes de este cambio, ejecuta esto en vez de correr todo el archivo:
-- CREATE TABLE eventos_log (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     tipo_evento ENUM('creacion_qr', 'lectura_qr', 'operacion_completada', 'mensaje_enviado', 'mensaje_fallido', 'edicion_registro', 'eliminacion_registro') NOT NULL,
--     registro_id INT NULL,
--     usuario_id INT NULL,
--     detalle VARCHAR(500) NOT NULL,
--     fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (registro_id) REFERENCES registros_accesos(id) ON DELETE SET NULL,
--     FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
-- );
--
-- Si ya tenías eventos_log creada (de un cambio anterior) y solo te falta el ENUM ampliado:
-- ALTER TABLE eventos_log MODIFY tipo_evento ENUM('creacion_qr', 'lectura_qr', 'operacion_completada', 'mensaje_enviado', 'mensaje_fallido', 'edicion_registro', 'eliminacion_registro') NOT NULL;
