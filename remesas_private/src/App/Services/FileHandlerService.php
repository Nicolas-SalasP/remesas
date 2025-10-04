<?php
namespace App\Services;

use Exception;

class FileHandlerService
{
    private string $tempDir;
    private string $publicUrl;
    private string $secureVerificationDir;

    public function __construct()
    {
        $this->tempDir = __DIR__ . '/../../../public_html/temp_orders/';
        $this->publicUrl = BASE_URL . '/temp_orders/';

        $this->secureVerificationDir = __DIR__ . '/../../../uploads/verifications/';

        $this->ensureDirectoryExistsAndIsWritable($this->tempDir);
        $this->ensureDirectoryExistsAndIsWritable($this->secureVerificationDir);
    }

    private function ensureDirectoryExistsAndIsWritable(string $dir): void
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("Error crítico: No se pudo crear el directorio: $dir", 500);
            }
        }
        if (!is_writable($dir)) {
            throw new Exception("Error de permisos: El directorio ($dir) no tiene permisos de escritura.", 500);
        }
    }

    public function savePdfTemporarily(string $pdfContent, int $transactionId): string
    {
        $filename = 'orden_' . $transactionId . '_' . uniqid() . '.pdf';
        $filePath = $this->tempDir . $filename;

        if (file_put_contents($filePath, $pdfContent) === false) {
            throw new Exception("No se pudo guardar el archivo PDF temporal.", 500);
        }

        return $this->publicUrl . $filename;
    }

    public function saveVerificationFile(array $fileData, int $userId, string $prefix): string
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo '{$prefix}'. Código: {$fileData['error']}", 400);
        }
        if ($fileData['size'] > 5000000) { // Límite de 5 MB
            throw new Exception("El archivo '{$prefix}' es demasiado grande (máx 5MB).", 400);
        }
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($fileData['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Formato de archivo no permitido para '{$prefix}'. Solo JPG o PNG.", 400);
        }

        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $newFilename = $prefix . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $this->secureVerificationDir . $newFilename;

        if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
            throw new Exception("No se pudo mover el archivo '{$prefix}' a su destino final.", 500);
        }

        return 'uploads/verifications/' . $newFilename;
    }
}