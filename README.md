# JC Envíos - Plataforma de Remesas
JC Envíos es una aplicación web completa diseñada para facilitar el envío de remesas de dinero de forma segura, rápida y confiable. La plataforma permite a los usuarios registrados realizar transacciones, gestionar beneficiarios y hacer seguimiento de sus envíos, mientras que los administradores cuentan con un panel de control para gestionar usuarios, tasas de cambio y operaciones.

# ✨ Características Principales
## Para Clientes
* * Registro y Autenticación de Usuarios: Sistema seguro de creación de cuentas e inicio de sesión.

* * Recuperación de Contraseña: Flujo de restablecimiento de contraseña a través de correo electrónico.

* * Verificación de Identidad: Proceso de verificación de cuenta mediante la subida de documentos de identidad para cumplir con los requisitos de seguridad antes de transaccionar.

* * Flujo de Transacción Guiado: Interfaz paso a paso para seleccionar la ruta del envío, el beneficiario y el monto a enviar.

* * Gestión de Beneficiarios: Los usuarios pueden agregar y seleccionar cuentas de beneficiarios para agilizar futuros envíos.

* * Historial de Transacciones: Panel para que los usuarios puedan ver todas sus transacciones, verificar su estado y subir comprobantes de pago.

* * Generación de Comprobantes: Posibilidad de generar un comprobante en formato PDF para cada orden de envío.

## Para Administradores
* * Panel de Administración Centralizado: Dashboard para supervisar la actividad de la plataforma.

* * Gestión de Transacciones: Visualización de todas las transacciones, con opciones para confirmar pagos, procesar envíos y subir comprobantes de envío.

* * Gestión de Usuarios: Panel para ver, bloquear y desbloquear usuarios del sistema.

* * Verificación de Cuentas: Interfaz para revisar y aprobar o rechazar los documentos de verificación de identidad de los usuarios.

* * Gestión de Tasas de Cambio: Funcionalidad para actualizar las tasas de cambio de las diferentes rutas de envío.

* * Gestión de Países: Panel para administrar los países de origen y destino, así como su estado (activo/inactivo).

* * Logs del Sistema: Visualización de un registro de todas las acciones importantes que ocurren en la aplicación para auditoría y depuración.

# 🛠️ Tecnologías Utilizadas
##Backend:

PHP: Lenguaje principal del lado del servidor.

Arquitectura por Capas: El proyecto sigue una estructura organizada en Controladores, Servicios y Repositorios para una clara separación de responsabilidades.

MySQL: Base de datos para almacenar toda la información.

Composer: Gestor de dependencias para PHP (PHPMailer, Twilio SDK).

FPDF: Librería utilizada para la generación de documentos PDF.

PHPMailer: Para el envío de correos electrónicos, como en la recuperación de contraseñas.

## Frontend:

HTML5, CSS3: Estructura y estilos de la aplicación.

JavaScript (Vanilla): Lógica del lado del cliente para interactividad, validaciones y comunicación con la API.

Bootstrap 5: Framework CSS para un diseño responsive y componentes de interfaz modernos.

Chart.js: Para la visualización de gráficos, como el valor del dólar en la página de inicio.

#$API:

API RESTful interna: El frontend se comunica con el backend a través de una API que sigue principios REST, gestionando todas las operaciones de datos de forma centralizada a través de public_html/api/index.php.



# 📁 Estructura del Proyecto
/
├── public_html/            # Archivos públicos (Document Root)
│   ├── admin/              # Vistas y lógica del panel de administración
│   ├── api/                # Punto de entrada de la API
│   ├── assets/             # CSS, JS, imágenes y otros recursos
│   ├── dashboard/          # Vistas y lógica del panel de cliente
│   └── index.php           # Página de inicio
│
├── remesas_private/        # Lógica de negocio y archivos privados
│   ├── src/
│   │   ├── App/            # Núcleo de la aplicación
│   │   │   ├── Controllers/ # Controladores (lógica de petición/respuesta)
│   │   │   ├── Repositories/ # Acceso a datos (consultas a la BD)
│   │   │   └── Services/     # Lógica de negocio
│   │   ├── core/           # Archivos de inicialización y configuración
│   │   └── lib/            # Librerías de terceros (ej. FPDF)
│   ├── vendor/             # Dependencias de Composer
│   └── config.php          # (No versionado) Credenciales y configuración
│
└── .gitignore              # Archivos y carpetas ignorados por Git
