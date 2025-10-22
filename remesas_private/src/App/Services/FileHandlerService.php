<?php
namespace App\Services;

use Exception;

class FileHandlerService
{
    private string $tempDir;
    private string $publicUrlBase;
    private string $secureVerificationDir;

    public function __construct()
    {
        $this->tempDir = __DIR__ . '/../../../public_html/temp_orders/';
        $this->publicUrlBase = BASE_URL . '/temp_orders/';

        $this->secureVerificationDir = __DIR__ . '/../../../uploads/verifications/';

        $this->ensureDirectoryExistsAndIsWritable($this->tempDir);
        $this->ensureDirectoryExistsAndIsWritable($this->secureVerificationDir);
    }

    private function ensureDirectoryExistsAndIsWritable(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                error_log("Error crítico: No se pudo crear el directorio: $dir");
                throw new Exception("Error interno al preparar el directorio de archivos.", 500);
            }
        }
        if (!is_writable($dir)) {
            error_log("Error de permisos: El directorio ($dir) no tiene permisos de escritura.");
            throw new Exception("Error interno de permisos de archivos.", 500);
        }
    }

    public function savePdfTemporarily(string $pdfContent, int $transactionId): string
    {
        $filename = 'orden_' . $transactionId . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $filePath = $this->tempDir . $filename;

        if (file_put_contents($filePath, $pdfContent) === false) {
            error_log("No se pudo guardar el archivo PDF temporal en: " . $filePath);
            throw new Exception("No se pudo generar el archivo de la orden.", 500);
        }

        return $this->publicUrlBase . $filename;
    }

    public function saveVerificationFile(array $fileData, int $userId, string $prefix): string
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo '{$prefix}'. Código: {$fileData['error']}", 400);
        }
        if ($fileData['size'] > 5000000) {
            throw new Exception("El archivo '{$prefix}' es demasiado grande (máx 5MB).", 400);
        }
        $allowedTypes = ['image/jpeg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $fileData['tmp_name']);
        finfo_close($finfo);

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Formato de archivo no permitido para '{$prefix}'. Solo JPG o PNG.", 400);
        }

        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
            throw new Exception("Extensión de archivo no válida para '{$prefix}'.", 400);
        }
        $newFilename = $prefix . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $this->secureVerificationDir . $newFilename;

        if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
            error_log("Error al mover archivo subido: {$fileData['tmp_name']} a {$destination}");
            throw new Exception("No se pudo guardar el archivo '{$prefix}'. Inténtalo de nuevo.", 500);
        }
        return 'uploads/verifications/' . $newFilename;
    }
}