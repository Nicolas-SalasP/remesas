# Manual de Operaciones y Administración - JC Envíos

## Descripción del Sistema
La plataforma gestiona el ciclo de vida de una remesa:
1.  **Cliente:** Crea orden -> Transfiere -> Sube comprobante.
2.  **Sistema:** Almacena orden (Estado: *Pendiente de Pago* -> *En Verificación*).
3.  **Administrador/Operador:** Verifica comprobante -> Aprueba (Estado: *En Proceso*).
4.  **Administrador/Operador:** Realiza pago al beneficiario -> Sube comprobante final -> Finaliza (Estado: *Pagado*).

## Procedimientos Operativos

### A. Gestión de Tasas de Cambio
*Caso de uso: El mercado cambia y hay que ajustar el valor del dólar/peso.*

1.  **Ingreso:** Menú lateral -> **Tasas**.
2.  **Editor:** En la parte superior, seleccione:
    * Origen: Chile.
    * Destino: Venezuela.
3.  El sistema cargará la tasa actual. Si no existe, los campos estarán vacíos.
4.  **Actualización:**
    * Ingrese el nuevo `Valor Tasa`.
    * Defina `Monto Mínimo` y `Máximo` si aplica para esa tasa específica (Tiered pricing).
5.  **Guardar:** Clic en "Guardar Tasa".
    * *Efecto:* Se actualiza la tabla `tasas` y se crea un registro en `tasas_historico`. Los usuarios verán el nuevo valor inmediatamente al cotizar.

### B. Verificación de Usuarios (KYC)
1.  **Alerta:** El Dashboard mostrará "Usuarios Pendientes".
2.  Vaya a **Verificaciones**.
3.  Verá la lista de usuarios nuevos. Clic en **"Revisar"**.
4.  Examine las fotos del documento (Frente/Reverso).
5.  **Acción:**
    * **Aprobar:** El usuario puede operar inmediatamente.
    * **Rechazar:** El usuario recibe una notificación y debe volver a subir documentos.

### C. Procesar una Remesa (Ciclo Completo)
1.  Vaya a **Transacciones Pendientes**.
2.  **Paso 1 (Recepción):** Busque órdenes en estado "En Verificación".
    * Clic en "Ver Comprobante" (ojo azul).
    * Verifique que el monto y fecha coincidan con su banco.
    * Clic en **"Confirmar"**. El estado pasa a "En Proceso".
3.  **Paso 2 (Envío):** Realice el pago al beneficiario usando su plataforma bancaria externa.
4.  **Paso 3 (Finalización):**
    * En la fila de la transacción (estado "En Proceso"), clic en **"Subir Envío"** (botón azul).
    * Adjunte el comprobante de su pago al beneficiario.
    * Ingrese la `Comisión Destino` (costo que le cobró el proveedor, para contabilidad).
    * Clic en Confirmar. El estado pasa a "Pagado" y se notifica al cliente por WhatsApp.

### D. Rollback de Tasa Errónea
*Si configuró una tasa incorrecta (ej. 1000 en vez de 100).*
1.  Vaya inmediatamente a **Tasas**.
2.  Seleccione la ruta afectada.
3.  Sobrescriba con el valor correcto y guarde.
4.  **Importante:** Las órdenes creadas *durante* el error tendrán el valor incorrecto.
    * Vaya a **Logs** para identificar las transacciones creadas en ese lapso.
    * Contacte a los usuarios para cancelar/re-hacer la orden o asuma el costo según política interna.

### E. Scripts de Mantenimiento
Si tiene acceso al servidor (SSH), puede ejecutar el bot de conciliación de correos (si está configurado):
```bash
php remesas_private/src/cron/procesar_pagos.php