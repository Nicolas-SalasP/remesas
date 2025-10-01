<?php
namespace App\Services;

use Exception;

class FileHandlerService
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../../public_html/';
    }

    public function handleUpload(array $fileData, string $subDirectory, string $fileNamePrefix): string
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error en la subida del archivo.", 400);
        }

        // Validar tipo MIME y extensión
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fileData['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];

        if (!in_array($mime, $allowedMimes)) {
            throw new Exception("Formato de archivo no permitido.", 400);
        }

        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $safeFileName = $fileNamePrefix . '_' . uniqid() . '.' . $extension;
        $destinationPath = $this->basePath . $subDirectory . $safeFileName;

        if (!move_uploaded_file($fileData['tmp_name'], $destinationPath)) {
            throw new Exception("No se pudo guardar el archivo.", 500);
        }

        return $subDirectory . $safeFileName; 
    }
}