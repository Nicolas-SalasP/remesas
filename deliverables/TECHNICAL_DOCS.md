### 5. 🛠️ TECHNICAL_DOCS.md
**Documentación Técnica Operativa**

```markdown
# Documentación Técnica

## Base de Datos (MySQL)
El esquema consta de tablas relacionales clave.

### Tablas Principales
* `usuarios`: Almacena credenciales, datos KYC y estado de bloqueo.
    * `VerificacionEstadoID`: FK a `estados_verificacion`.
* `transacciones`: Núcleo del sistema.
    * `ComprobanteURL`: Ruta al archivo subido por el usuario.
    * `ComprobanteEnvioURL`: Ruta al archivo subido por el admin.
    * `ComprobanteHash`: SHA256 del archivo para evitar duplicados.
* `tasas`: Configuración de precios.
    * Clave compuesta lógica: `PaisOrigenID` + `PaisDestinoID` + Rangos de montos.
* `contabilidad_movimientos`: Registro de doble entrada simplificado para saldos internos.

## API Endpoints
El sistema usa un router central en `public_html/api/index.php` que despacha a controladores.

| Acción (`?accion=`) | Método | Controlador | Descripción |
|---------------------|--------|-------------|-------------|
| `loginUser` | POST | AuthController | Autentica usuario, retorna sesión o requerimiento 2FA. |
| `getTasa` | GET | ClientController | Obtiene tasa actual para par origen/destino. |
| `createTransaccion` | POST | ClientController | Crea orden, genera PDF y notifica. |
| `uploadReceipt` | POST | ClientController | Sube imagen, valida hash, mueve a carpeta segura. |
| `getDashboardStats` | GET | AdminController | KPIs para el dashboard de admin. |

## Instalación Local (Requisitos)
* PHP 8.1+ (Extensiones: mysqli, gd, curl, intl, mbstring, zip).
* MySQL 5.7 / 8.0 / MariaDB.
* Composer.

### Pasos
1.  Clonar repositorio.
2.  Ejecutar `composer install` en `remesas_private/`.
3.  Importar `jcenvios1_remesas_bd (2).sql` en MySQL.
4.  Configurar `remesas_private/config.php` (Crear basado en variables del entorno).
    * Definir `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `BASE_URL`.
    * Definir `APP_ENCRYPTION_KEY` (32 chars).
5.  Configurar servidor web (Apache/Nginx) apuntando a `public_html/`.

## Seguridad de Archivos
Los archivos sensibles (IDs, comprobantes) se guardan fuera de `public_html` (en `uploads/`).
Se sirven mediante `public_html/admin/view_secure_file.php` que valida:
1.  Sesión de usuario activa.
2.  Rol Admin O propiedad del archivo (el usuario solo ve sus archivos).
3.  Validación de Path Traversal con `realpath()`.