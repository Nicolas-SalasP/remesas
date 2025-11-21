# Análisis de Código

## Mapa del Código
* **`public_html/`**: Entry point. Contiene assets (JS, CSS) y archivos PHP que actúan como controladores frontales o vistas directas.
    * `api/index.php`: **Router central de la API**. Mapea acciones (`?accion=...`) a controladores.
* **`remesas_private/src/App/`**: Núcleo de la lógica.
    * `Controllers/`: Reciben la petición, llaman servicios y devuelven JSON.
    * `Services/`: Contiene la lógica de negocio (Cálculos, Validaciones, Emails).
    * `Repositories/`: Capa de acceso a datos (SQL queries).
    * `Database/`: Singleton para conexión MySQL.

## Puntos Críticos y Deuda Técnica

### 1. Supresión de Errores (`@`)
* **Archivo:** `remesas_private/src/App/Database/Database.php`
* **Línea:** `new mysqli(...)` usa `@` para silenciar errores de conexión.
* **Riesgo:** Dificulta la depuración en producción si la BD cae.
* **Solución:** Eliminar `@` y manejar la `mysqli_sql_exception`.

### 2. Librería FPDF "Vendorizada" Manualmente
* **Ubicación:** `remesas_private/src/lib/fpdf/`
* **Problema:** Código fuente de una librería mezclado con el código del proyecto. Dificulta actualizaciones.
* **Solución:** Instalar via composer: `composer require setasign/fpdf`.

### 3. Hardcoding de Credenciales/Configuración
* **Archivo:** `remesas_private/src/App/Services/NotificationService.php`
* **Problema:** `PROVEEDOR_WHATSAPP_NUMBER = '+56912345678'` y emails harcoded.
* **Solución:** Mover a variables de entorno (`.env`) o `config.php`.

## Análisis de Funciones Clave (Endpoints API)

### `createTransaccion` (ClientController)
* **Ruta:** `POST /api/?accion=createTransaccion`
* **Servicio:** `TransactionService::createTransaction`
* **Propósito:** Inicia una orden de remesa.
* **Flujo:**
    1.  Verifica login y estado de verificación (KYC) "Verificado".
    2.  Valida input (montos, ids).
    3.  Recupera datos del beneficiario.
    4.  Calcula tasa (aunque confía en la tasa enviada en el payload `tasaID`, lo cual es un riesgo si la tasa cambió en el interim).
    5.  Inserta en DB.
    6.  Genera PDF temporal.
    7.  Envía notificaciones (WhatsApp/Email).
* **Mejora:** Validar que la `tasaID` siga vigente y que el valor coincida con la DB antes de insertar.

### `upsertRate` (AdminController)
* **Ruta:** `POST /api/?accion=updateRate`
* **Servicio:** `PricingService::adminUpsertRate`
* **Propósito:** Crear o actualizar una tasa de cambio.
* **Efectos Secundarios:** Inserta registro en `tasas_historico` para auditoría.
* **Validación:** Verifica que país origen/destino existan y montos sean positivos.

## Propuesta de Tests (PHPUnit)

```php
// Ejemplo para TransactionService
public function testCreateTransactionFailIfUserNotVerified() {
    $userRepoMock = $this->createMock(UserRepository::class);
    $userRepoMock->method('findUserById')->willReturn(['VerificacionEstado' => 'Pendiente']);
    
    $service = new TransactionService($this->txRepo, $userRepoMock, ...);
    
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Tu cuenta debe estar verificada");
    
    $service->createTransaction(['userID' => 1, ...]);
}
