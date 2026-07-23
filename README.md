````markdown
# MECARIO — Sistema de Inventario y Venta de Autopartes

## Descripción del proyecto

**Mecario** es un sistema web para la gestión de inventario, venta y facturación de autopartes. La aplicación permite administrar vehículos de referencia, tipos de partes, secciones, piezas disponibles, usuarios, roles y permisos. Además, incorpora un catálogo público para clientes, carrito de compras, métodos de pago simulados, opciones de entrega, generación de facturas PDF/A y mecanismos de seguridad y auditoría.

El proyecto está desarrollado en **PHP 8.2+** bajo una arquitectura **Modelo-Vista-Controlador (MVC)**, utiliza **MySQL/MariaDB** como gestor de base de datos y **PDO** para el acceso seguro a los datos. Para la generación de facturas se utiliza **TCPDF 7**, instalado mediante Composer.

---

## Integrantes

- Luisa de Gracia — 8-1023-924
- Joselyn Cención — 8-1024-804
- Andrea Torrento — 20-23-7979
- Franco Prieto — 20-70-7514

---

## Objetivo

Desarrollar un sistema web que permita administrar de forma organizada el inventario y la venta de autopartes, facilitando el control de existencias, la gestión de usuarios y roles, el proceso de compra de los clientes, la generación de facturas y la trazabilidad de operaciones críticas mediante mecanismos de seguridad y auditoría.

---
### Demostración en video

> **URL:** https://utpac-my.sharepoint.com/:v:/g/personal/luisa_degracia_utp_ac_pa/IQB2iz7WU0DqRpXhaK8aVnCGAS6Wvf35_dhU2M7WIFpEHaA?nav=eyJyZWZlcnJhbEluZm8iOnsicmVmZXJyYWxBcHAiOiJPbmVEcml2ZUZvckJ1c2luZXNzIiwicmVmZXJyYWxBcHBQbGF0Zm9ybSI6IldlYiIsInJlZmVycmFsTW9kZSI6InZpZXciLCJyZWZlcnJhbFZpZXciOiJNeUZpbGVzTGlua0NvcHkifX0&e=halNc6

---

## Funcionalidades principales

### Gestión de acceso y usuarios

- Inicio y cierre de sesión.
- Registro público de clientes.
- Administración de usuarios internos.
- Activación, desactivación y desbloqueo de cuentas.
- Control de roles y permisos.
- Cambio de contraseña.
- Bloqueo temporal después de tres intentos fallidos de inicio de sesión.
- Registro de accesos exitosos y fallidos.

### Catálogos e inventario

- Gestión de autos por marca, modelo y año.
- Gestión de tipos de partes.
- Gestión de secciones.
- Registro y modificación de piezas del inventario.
- Control de código, descripción, condición, precio y cantidad disponible.
- Carga de imágenes y miniaturas.
- Búsqueda y filtrado del inventario.
- Activación y desactivación de registros.
- Exportación de información del inventario.

### Catálogo público y compras

- Consulta pública de piezas disponibles.
- Visualización del detalle de cada pieza.
- Carrito de compras para clientes autenticados.
- Actualización de cantidades y eliminación de productos del carrito.
- Métodos de pago simulados: Yappy, Visa y Mastercard.
- Métodos de entrega: retiro en local o delivery.
- Cálculo de ITBMS del 7 %.
- Historial personal de compras.

> Los métodos de pago implementados forman parte del flujo académico del sistema y no realizan validaciones contra pasarelas bancarias reales.

### Ventas y facturación

- Registro de ventas internas.
- Disminución automática de las existencias al completar una venta.
- Consulta del detalle de ventas.
- Estadísticas y reportes de ventas.
- Exportación de reportes.
- Generación y descarga de facturas mediante TCPDF.
- Generación de facturas en formato PDF/A.

### Comentarios

- Los clientes autenticados pueden registrar comentarios.
- Los comentarios pueden ser aprobados, ocultados o deshabilitados por usuarios autorizados.
- Los visitantes pueden consultar únicamente los comentarios publicados y activos.

### Seguridad y auditoría

- Contraseñas almacenadas mediante hash seguro.
- Consultas preparadas con PDO.
- Protección CSRF.
- Validación y sanitización de datos.
- Registro de intentos de inicio de sesión.
- Registro de anomalías.
- Generación de llaves RSA para usuarios internos.
- Firma de operaciones críticas mediante RSA y SHA-256.
- Auditoría de operaciones importantes.
- Rotación de llaves RSA.

---

## Roles del sistema

### Administrador

Posee acceso completo al sistema. Puede gestionar usuarios, roles, permisos, inventario, ventas, seguridad y auditoría.

### Operador

Gestiona catálogos, inventario, ventas, comentarios y otras funciones operativas. No posee acceso completo a la administración de usuarios.

### Cliente

Puede consultar el catálogo, agregar productos al carrito, realizar compras, consultar su historial, descargar facturas y registrar comentarios.

---

## Tecnologías utilizadas

- PHP 8.2+
- MySQL / MariaDB
- PDO
- HTML5
- CSS3
- JavaScript
- Apache
- Composer
- TCPDF 7
- OpenSSL
- Arquitectura MVC

---

## Requisitos del sistema

Para ejecutar Mecario se requiere:

- PHP 8.2 o superior.
- Apache.
- MySQL o MariaDB.
- Composer.
- Extensión `pdo_mysql`.
- OpenSSL.
- Permisos de escritura en las carpetas de almacenamiento.

Se recomienda utilizar **XAMPP** o **WampServer** para la ejecución local.

---

## Instalación

### 1. Copiar el proyecto

Copiar o descomprimir la carpeta del proyecto dentro del directorio público del servidor local.

Ejemplo con XAMPP:

```text
C:\xampp\htdocs\mecario
````

Ejemplo con WampServer:

```text
C:\wamp64\www\mecario
```

### 2. Instalar las dependencias

Abrir una terminal en la carpeta raíz del proyecto y ejecutar:

```bash
composer install
```

### 3. Crear la base de datos

1. Iniciar Apache y MySQL.
2. Abrir phpMyAdmin.
3. Importar el archivo:

```text
database/mecario.sql
```

El script crea la base de datos `mecario`, sus tablas, relaciones y datos iniciales.

### 4. Configurar la conexión

La configuración principal se encuentra en:

```text
config/config.php
```

Configuración local utilizada:

```text
Host: 127.0.0.1
Puerto: 3306
Base de datos: mecario
Usuario: root
Contraseña: vacía
```

### 5. Instalar y configurar OpenSSL

Debe verificarse la configuración de OpenSSL utilizada por el sistema para la generación de llaves y certificados.

### 6. Ejecutar el sistema

Con Apache y MySQL activos, abrir en el navegador:

```text
http://localhost/mecario/
```

---

## Credenciales de prueba

### Administrador

```text
Usuario: admin
Contraseña: root2514
```

### Operador

```text
Usuario: operador
Contraseña: root2514
```

Las credenciales anteriores son utilizadas únicamente para pruebas y demostración académica.

---

## Estructura principal del proyecto

```text
MECARIOAPP-1.0-main/
│
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   ├── Interfaces/
│   ├── Models/
│   ├── Services/
│   └── Views/
│
├── config/
│   └── config.php
│
├── database/
│   └── mecario.sql
│
├── public/
│   ├── assets/
│   ├── .htaccess
│   └── index.php
│
├── routes/
│
├── storage/
│   ├── certificates/
│   ├── facturas/
│   ├── keys/
│   └── logs/
│
├── uploads/
│   ├── grandes/
│   └── thumbnails/
│
├── composer.json
├── composer.lock
└── index.php
```

---

## Base de datos

La base de datos principal se denomina **mecario**.

Entre sus tablas principales se encuentran:

```text
roles
permisos
rol_permiso
usuarios
usuario_rol
login_logs
anomalias
claves_usuario
auditoria_firmada
autos
partes
secciones
inventario_partes
ventas
venta_detalles
facturas
comentarios
```

---

## Flujo básico de uso

### Cliente

1. Accede al catálogo público.
2. Busca una pieza.
3. Consulta el detalle.
4. Se registra o inicia sesión.
5. Agrega piezas al carrito.
6. Modifica las cantidades si es necesario.
7. Selecciona el método de entrega.
8. Selecciona el método de pago.
9. Confirma la compra.
10. El sistema registra la venta y actualiza el inventario.
11. El cliente consulta su historial y descarga su factura.

### Operador

1. Inicia sesión.
2. Gestiona autos, partes y secciones.
3. Registra y modifica piezas.
4. Consulta las existencias.
5. Registra ventas internas.
6. Consulta estadísticas y reportes.
7. Modera comentarios.

### Administrador

El administrador puede realizar las operaciones generales del sistema y adicionalmente gestionar usuarios, roles, permisos, seguridad, auditoría y llaves RSA.

---

## Consideraciones de seguridad

* Las contraseñas son almacenadas mediante hash.
* Se utilizan consultas preparadas con PDO.
* Los formularios críticos utilizan protección CSRF.
* El sistema registra intentos fallidos de inicio de sesión.
* Se implementan mecanismos de auditoría.
* Las operaciones críticas pueden contar con evidencia firmada mediante RSA y SHA-256.
* Las credenciales de prueba deben cambiarse antes de utilizar el sistema fuera de un entorno académico.
* Los métodos de pago utilizados son simulados y no procesan transacciones bancarias reales.

---
### 1.4. Demostración en video

> **URL pendiente:** agregar aquí el enlace público o con permisos de lectura de YouTube o Google Drive.
