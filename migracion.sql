-- Ejecutar una sola vez. No elimina datos.
ALTER TABLE cv_personal ADD COLUMN correo VARCHAR(190) NULL, ADD COLUMN usuario_id INT NULL;
ALTER TABLE cv_personal ADD CONSTRAINT fk_cv_personal_usuario FOREIGN KEY (usuario_id) REFERENCES cv_usuarios(id) ON DELETE SET NULL;
ALTER TABLE cv_usuarios MODIFY COLUMN rol ENUM('administrador','recepcionista','docente') NOT NULL DEFAULT 'recepcionista';
ALTER TABLE cv_visitas ADD COLUMN notificacion_estado ENUM('Pendiente','Enviada','Error','Sin correo') NOT NULL DEFAULT 'Pendiente';
ALTER TABLE cv_notificaciones ADD COLUMN visita_id INT NULL;
ALTER TABLE cv_notificaciones ADD CONSTRAINT fk_cv_notif_visita FOREIGN KEY (visita_id) REFERENCES cv_visitas(id) ON DELETE CASCADE;
CREATE TABLE IF NOT EXISTS cv_correos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 visita_id INT NOT NULL,
 destinatario VARCHAR(190) NOT NULL,
 estado ENUM('Pendiente','Enviada','Error') NOT NULL DEFAULT 'Pendiente',
 intentos INT NOT NULL DEFAULT 0,
 ultimo_error VARCHAR(255) NULL,
 enviado_en DATETIME NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_visita_correo (visita_id),
 FOREIGN KEY (visita_id) REFERENCES cv_visitas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
