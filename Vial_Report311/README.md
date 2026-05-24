# VialReport311 — Guía de instalación y ejecución
## Stack: PHP + MySQL/MariaDB + HTML/CSS/JS vanilla

---

## Requisitos
- PHP 8.0 o superior
- MySQL 8.0 o MariaDB 10.6+
- XAMPP (recomendado, incluye los dos anteriores)

---

## Paso 1 — Instalar XAMPP

1. Descarga desde: https://www.apachefriends.org/es/download.html
2. Instala con todas las opciones por defecto
3. Abre el **Panel de control de XAMPP**
4. Inicia **Apache** y **MySQL** (botón Start en cada uno)

---

## Paso 2 — Crear la base de datos

### Opción A — Desde phpMyAdmin (más fácil)
1. Abre tu navegador y ve a: http://localhost/phpmyadmin
2. Clic en **"SQL"** en la barra superior
3. Pega todo el contenido del archivo `db/init.sql`
4. Clic en **"Continuar"**

### Opción B — Desde la terminal
```bash
mysql -u root -p < db/init.sql
```

---

## Paso 3 — Copiar el proyecto

1. Copia la carpeta `vialreport311-php` completa a:
   ```
   C:\xampp\htdocs\vialreport311-php
   ```

---

## Paso 4 — Configurar la conexión

Abre el archivo `config/database.php` y ajusta si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vialreport311');
define('DB_USER', 'root');
define('DB_PASS', '');   // En XAMPP por defecto no hay contraseña
```

---

## Paso 5 — Abrir la aplicación

En tu navegador ve a:

| Página        | URL                                                    |
|---------------|--------------------------------------------------------|
| Inicio        | http://localhost/vialreport311-php/index.php           |
| Ciudadanos    | http://localhost/vialreport311-php/pages/usuarios.php  |
| Reportes      | http://localhost/vialreport311-php/pages/reportes.php  |

---

## Estructura del proyecto

```
vialreport311-php/
├── index.php                  ← Página de inicio
├── config/
│   └── database.php           ← Conexión PDO a MySQL
├── db/
│   └── init.sql               ← DDL + datos de prueba
├── api/
│   ├── usuarios.php           ← CRUD Ciudadanos (GET/POST/PUT/DELETE)
│   └── reportes.php           ← CRUD Reportes   (GET/POST/PUT/DELETE)
├── pages/
│   ├── navbar.php             ← Barra de navegación compartida
│   ├── usuarios.php           ← Pantalla CRUD Ciudadanos
│   └── reportes.php           ← Pantalla CRUD Reportes
└── assets/
    └── css/
        └── style.css          ← Estilos globales
```

---

## Endpoints de la API

### Ciudadanos (`api/usuarios.php`)
| Método | URL                         | Acción         |
|--------|-----------------------------|----------------|
| GET    | api/usuarios.php            | Listar todos   |
| GET    | api/usuarios.php?id=N       | Buscar por ID  |
| POST   | api/usuarios.php            | Crear nuevo    |
| PUT    | api/usuarios.php?id=N       | Actualizar     |
| DELETE | api/usuarios.php?id=N       | Eliminar       |

### Reportes (`api/reportes.php`)
| Método | URL                         | Acción         |
|--------|-----------------------------|----------------|
| GET    | api/reportes.php            | Listar todos   |
| GET    | api/reportes.php?id=N       | Buscar por ID  |
| POST   | api/reportes.php            | Crear nuevo    |
| PUT    | api/reportes.php?id=N       | Actualizar     |
| DELETE | api/reportes.php?id=N       | Eliminar       |
