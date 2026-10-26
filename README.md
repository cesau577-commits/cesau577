# INAC – Sistema Institucional de Control de Visitas

Proyecto del **Módulo 3.8 – Proyecto Innovador de Desarrollo de Software**
Instituto Nacional Albert Camus (INAC) · Sección 3-1 DS · Docente: Iris Ivett Cantor

Sistema web que registra la entrada y la salida de los visitantes de la institución, avisa a la
persona visitada (notificación interna y correo electrónico) y genera reportes del historial de visitas.

## Integrantes

- Uriel Esaú Carpio Alvarado
- Rodrigo Alexander Mendoza Diaz
- Krissia Beatriz Melendez Portillo
- Michael Alexander Ruiz Campos

## Sitio publicado

https://carpiods.free.je/index.php



## Contenido del repositorio

| Carpeta | Contenido |
|---|---|
| `src/` | Código del sistema en PHP (login, panel, visitas, salidas, personal, usuarios, reportes, notificaciones, correo) y `estilo.css`. Incluye PHPMailer. |
| `database/` | `estructura_base_de_datos.sql` (tablas y relaciones) y `carga_masiva_datos.sql` (datos ficticios, mínimo 20 registros por tabla). |
| `docs/` | Documento del proyecto en Word y las imágenes: flujogramas, modelo conceptual y lógico, mapa de navegación, maquetas, mockups y guía de estilo (`docs/img/`). |

## Tecnologías

PHP 8 · MySQL / MariaDB (mysqli) · Bootstrap 5.3 · PHPMailer · HTML y CSS

## Roles del sistema

| Rol | Puede hacer |
|---|---|
| Administrador | Todo: visitas, salidas, personal, usuarios y reportes |
| Recepcionista | Registrar entradas y salidas, ver notificaciones |
| Docente | Ver las visitas dirigidas a él y sus notificaciones |

## Instalación

1. Cree una base de datos en MySQL e importe `database/estructura_base_de_datos.sql`.
2. Para probar con datos de ejemplo, importe después `database/carga_masiva_datos.sql`
   (usuarios `usuario01` a `usuario20`, contraseña de prueba `Clave2026`).
3. Para crear su propio administrador, genere un hash con `password_hash('SU_CLAVE', PASSWORD_DEFAULT)`
   e insértelo en `cv_usuarios` con rol `administrador`.
4. Copie la carpeta `src/` al servidor y edite `src/conexion.php` con los datos de su base de datos.
5. Para el envío de correos, edite `src/correo_config.php` con su cuenta de Gmail y su
   **clave de aplicación** (no la contraseña normal).
6. Abra `login.php` en el navegador.

## Seguridad

Este repositorio es público, por eso `conexion.php` y `correo_config.php` solo contienen marcadores
(`TU_HOST_MYSQL`, `TU_CLAVE_DE_APLICACION_GMAIL`, etc.). **No suba nunca contraseñas reales.**
Las contraseñas de los usuarios se guardan cifradas con `password_hash`.
