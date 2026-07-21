# Mecario — Sistema de Inventario y Venta de Autopartes

Sistema web académico para administrar autopartes, inventario, clientes, ventas, compras, facturación, roles, permisos y trazabilidad criptográfica. Está desarrollado con PHP, MySQL, PDO, TCPDF y una arquitectura MVC.

## 1. Información general y evidencia práctica

### 1.1. Nombre del proyecto

**Mecario — Sistema de Inventario y Venta de Autopartes**

El sistema permite gestionar autos de origen, tipos de partes, secciones físicas, existencias, imágenes, ventas internas, compras de clientes, entrega, pagos simulados, facturas PDF, reportes y auditoría de operaciones críticas.

### 1.2. Integrantes del equipo


| Nombre completo | Cédula | Rol dentro del desarrollo |
|---|---|---|
| Luisa de Gracia | 8-1023-924 | Análisis del sistema, Base de Datos, Definición de requerimientos |
| Joselyn Cención | 8-1024-804 | Frontend, Diseño visual y Estilos CSS |
| Andrea Torrento | 20-23-7979 | Backend y Lógica del sistema |
| Franco Prieto   | 20-70-7514 | Base de datos y Gestión de la información |

### 1.3. Fecha y versión

- **Versión:** v1.0.0
- **Fecha de esta versión:** 20 de julio de 2026
- **Estado:** versión académica funcional

### 1.4. Demostración en video

> **URL pendiente:** agregar aquí el enlace público o con permisos de lectura de YouTube o Google Drive.

El video debe mostrar como mínimo:

1. Instalación o ejecución local del proyecto.
2. Inicio de sesión con los distintos roles.
3. Creación, búsqueda y modificación de inventario.
4. Flujo de carrito, pago, venta y factura PDF.
5. Validación CSRF, intentos fallidos y bloqueo de cuenta.
6. Auditoría, firmas RSA y rotación de llaves.

## 2. Requisitos de infraestructura

### 2.1. Entorno de ejecución

| Componente | Requisito |
|---|---|
| PHP | 8.2 o superior |
| Base de datos | MySQL o MariaDB |
| Servidor web | Apache con `mod_rewrite` |
| Entorno recomendado | XAMPP o WampServer |
| Dependencias PHP | Composer |
| Extensiones PHP | PDO MySQL, OpenSSL, cURL y mbstring |
| Librería PDF | TCPDF 7, instalada mediante Composer |

La configuración actual de OpenSSL para XAMPP utiliza:

```text
D:/AplicacioneSoftware/Xamp/apache/conf/openssl.cnf
```

Puede cambiarse mediante la opción `security.openssl_config` de [`config/config.php`](config/config.php).

### 2.2. Guía de despliegue rápido

#### 1. Clonar el repositorio

Reemplace la URL de ejemplo por la URL real del repositorio:

```bash
git clone URL_DEL_REPOSITORIO
cd mecario
```

También puede copiar la carpeta directamente dentro de:

```text
D:\AplicacioneSoftware\Xamp\htdocs\mecario
```

#### 2. Instalar las dependencias

```bash
composer install
```

#### 3. Crear la base de datos y los datos semilla

Importe en phpMyAdmin el archivo [`database/mecario.sql`](database/mecario.sql). Este script crea la base de datos `mecario`, sus tablas, relaciones, roles, permisos, usuarios iniciales, catálogos e inventario de prueba.

No existe un `backup.sql` separado: [`database/mecario.sql`](database/mecario.sql) es el respaldo completo de instalación y pruebas rápidas.

#### 4. Configurar las credenciales locales

Edite [`config/config.php`](config/config.php) o defina las variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.

Configuración predeterminada de XAMPP:

```php
'host' => '127.0.0.1',
'port' => '3306',
'name' => 'mecario',
'user' => 'root',
'password' => '',
```

#### 5. Iniciar el sistema

Inicie Apache y MySQL y abra:

```text
http://localhost/mecario/
```

Si las rutas internas muestran un error 404, active `rewrite_module` en Apache y reinicie el servicio.

#### 6. Permisos de almacenamiento

El proceso de Apache necesita escritura en:

```text
storage/keys/       Llaves RSA privadas cifradas de usuarios internos
storage/facturas/   Facturas PDF generadas
storage/logs/       Registros de errores
uploads/            Imágenes del inventario
```

## 3. Matriz de roles y credenciales de prueba

| Rol | Usuario de acceso | Contraseña | Permisos principales |
|---|---|---|---|
| Administrador | `admin` | `root2514` | Control total, usuarios, roles, seguridad, auditoría, rotación de llaves, inventario y ventas. |
| Operador | `operador` | `root2514` | Gestión operativa, inventario, ventas, seguridad y consulta de auditoría; sin administración de usuarios ni rotación de llaves. |
| Cliente | Registro público | La elegida al registrarse | Catálogo, carrito, compra, factura, historial y comentarios. |

Las cuentas iniciales también utilizan los correos `admin@mecario.local` y `operador@mecario.local`, pero el formulario de acceso solicita el nombre de usuario.

> Estas credenciales son exclusivamente para demostración. Deben cambiarse antes de publicar el sistema.

## 4. Directrices técnicas y reglas del backend

### 4.1. Control de acceso seguro

La autenticación está implementada principalmente en [`app/Controllers/AuthController.php`](app/Controllers/AuthController.php), [`app/Models/Usuario.php`](app/Models/Usuario.php), [`app/Services/PasswordHashService.php`](app/Services/PasswordHashService.php) y [`app/Core/Session.php`](app/Core/Session.php).

- Las contraseñas se almacenan con `password_hash()` y se verifican con `password_verify()`.
- El registro exige al menos 8 caracteres. Actualmente el formulario admite hasta 100 caracteres; si la rúbrica exige exactamente de 8 a 12, debe ajustarse el máximo antes de la entrega.
- Después del tercer intento fallido la cuenta se bloquea durante 15 minutos.
- Cada intento conserva usuario, dirección IP, fecha, resultado y detalle en `login_logs`.
- Los accesos anómalos se registran en `anomalias_seguridad`.
- Los roles y permisos se comprueban mediante la sesión y los métodos de autorización de los controladores.
- Las cuentas internas generan una identidad RSA propia; las cuentas con únicamente el rol Cliente no generan llaves RSA.

### 4.2. Mitigación OWASP y principio DRY

La protección CSRF se centraliza en [`app/Helpers/Csrf.php`](app/Helpers/Csrf.php). Los formularios POST incluyen `Csrf::campo()` y los controladores validan el token antes de modificar datos. Una petición externa sin token válido, por ejemplo desde Postman, no puede ejecutar normalmente estas operaciones.

Otras medidas implementadas:

- PDO y parámetros enlazados para reducir el riesgo de inyección SQL.
- Escape de salida HTML mediante [`app/Helpers/Sanitizer.php`](app/Helpers/Sanitizer.php).
- Validaciones del backend independientes de los atributos HTML.
- Cookies de sesión `HttpOnly` y política `SameSite=Lax`.
- Regeneración de sesión y token CSRF después del inicio de sesión.
- Archivos privados almacenados fuera de `public/`.

La separación DRY/MVC se distribuye así:

```text
app/Controllers/   Coordinación de solicitudes y autorización
app/Models/        Consultas y persistencia en MySQL
app/Services/      Seguridad, criptografía, facturación y cálculos
app/Helpers/       CSRF, sanitización, validación, URL e imágenes
app/Core/          Base de datos, router, sesión, vistas y errores
app/Views/         Presentación de las pantallas
routes/            Definición de rutas por módulo
```

### 4.3. Sello de integridad y firma digital

La integridad de operaciones críticas se implementa en:

- [`app/Services/AuditTrailService.php`](app/Services/AuditTrailService.php): construye el contenido canónico de la acción y calcula su SHA-256.
- [`app/Services/KeyManager.php`](app/Services/KeyManager.php): genera un par RSA por usuario interno, cifra la llave privada y conserva la llave pública y su huella.
- [`app/Services/RsaSignatureService.php`](app/Services/RsaSignatureService.php): firma y verifica el contenido mediante RSA con SHA-256.
- [`app/Models/Auditoria.php`](app/Models/Auditoria.php): consulta registros y verifica evidencias históricas.

Flujo simplificado:

1. El backend reúne usuario, módulo, acción, entidad, datos, IP y fecha.
2. Serializa el contenido como JSON.
3. Calcula un hash SHA-256.
4. Firma el contenido con la llave RSA privada cifrada del usuario interno.
5. Guarda JSON, hash, firma Base64, usuario, llave, IP y fecha en `auditoria_firmada`.
6. La pantalla de auditoría vuelve a calcular y verificar la firma con la llave pública registrada.

Las llaves privadas se guardan cifradas en `storage/keys/`; las llaves públicas, huellas y estados se almacenan en `claves_usuario`. Al rotar una llave, la anterior se desactiva pero se conserva para verificar firmas históricas.

Las facturas PDF se generan mediante [`app/Services/InvoicePdfService.php`](app/Services/InvoicePdfService.php), se almacenan en `storage/facturas/` y registran una huella SHA-256. La generación del PDF no depende de certificados OpenSSL; OpenSSL se utiliza en la auditoría RSA de las acciones críticas.

## 5. Manual de usuario operativo

### Video del proyecto

> **URL pendiente:** agregar aquí el mismo enlace indicado en la sección 1.4.

### 5.1. Guía visual y flujo de pantallas

#### A. Iniciar sesión

1. Abra `http://localhost/mecario/`.
2. Seleccione **Iniciar sesión**.
3. Utilice `admin` o `operador` con la contraseña de prueba.
4. El sistema cargará el panel de acuerdo con los permisos del rol.

![Captura pendiente: pantalla de inicio de sesión](docs/screenshots/01-login.png)

#### B. Agregar una pieza

1. Ingrese al módulo **Inventario**.
2. Presione **Nueva pieza**.
3. Seleccione auto, parte y sección.
4. Complete código, precio, cantidad, condición y descripción.
5. Opcionalmente cargue miniatura e imagen grande.
6. Presione **Guardar**.

![Captura pendiente: formulario de inventario](docs/screenshots/02-inventario-crear.png)

#### C. Buscar inventario

1. Abra **Inventario** o el **Catálogo**.
2. Escriba código, pieza, marca o modelo en el campo de búsqueda.
3. Aplique los filtros disponibles.
4. Presione **Buscar** o **Filtrar**.
5. Puede limpiar los criterios para volver al listado completo.

![Captura pendiente: búsqueda y filtros](docs/screenshots/03-inventario-buscar.png)

#### D. Modificar una pieza

1. Localice la pieza en el listado.
2. Presione **Editar**.
3. Modifique existencias, precio, ubicación, datos o imágenes.
4. Presione **Guardar**.
5. También puede activar o desactivar la pieza desde las acciones del listado.

![Captura pendiente: edición de inventario](docs/screenshots/04-inventario-editar.png)

#### E. Realizar una compra como cliente

1. Registre una cuenta desde la pantalla pública o inicie sesión como Cliente.
2. Abra el catálogo y seleccione una pieza.
3. Indique la cantidad y agréguela al carrito.
4. Revise el carrito y continúe al pago.
5. Seleccione retiro o delivery y el método de pago simulado.
6. Confirme la compra y descargue la factura PDF.

![Captura pendiente: carrito y pago](docs/screenshots/05-carrito-pago.png)

#### F. Consultar seguridad y auditoría

1. Inicie sesión como Administrador.
2. Abra **Seguridad** para consultar intentos y anomalías.
3. Abra **Auditoría** para revisar hash, firma, IP, fecha y validez.
4. Si posee el permiso correspondiente, puede rotar una llave RSA sin eliminar la evidencia histórica.

![Captura pendiente: auditoría firmada](docs/screenshots/06-auditoria.png)

## Funcionalidades adicionales

- CRUD de autos, partes, secciones, inventario, usuarios y roles.
- Catálogo público con imágenes y filtros.
- Carrito con múltiples productos y cantidades.
- Retiro en local o delivery.
- Pago académico mediante Yappy, Visa o Mastercard.
- Descuento transaccional de existencias.
- Cálculo centralizado de subtotal, ITBMS del 7 %, entrega y total.
- Historial de compras y ventas.
- Facturas PDF descargables con huella SHA-256.
- Exportación de inventario y ventas a Excel.
- Estadísticas diarias, mensuales, por pago, categoría y producto.
- Moderación de comentarios.

## Estructura principal del repositorio

```text
app/                 Código MVC y servicios
config/              Configuración del sistema
database/            Instalación SQL y datos semilla
public/              Front controller, CSS, JavaScript e imágenes públicas
routes/              Rutas por módulo
storage/             Logs, llaves RSA y facturas
uploads/             Imágenes cargadas del inventario
vendor/              Dependencias instaladas por Composer
composer.json        Dependencias PHP del proyecto
README.md             Documentación general y manual operativo
```

## Notas antes de la entrega

- Completar nombres, cédulas y responsabilidades del equipo.
- Sustituir la URL del repositorio.
- Agregar el enlace del video con permisos de acceso.
- Crear `docs/screenshots/` y añadir las seis capturas referenciadas.
- Si la evaluación exige un máximo de 12 caracteres para contraseñas, actualizar la validación actual y sus formularios.
- No utilizar las credenciales de prueba en producción.
