<?php
/**
 * Validación y guardado seguro de imágenes subidas por el usuario
 * (fotos de identificación). Nunca confía en el nombre de archivo ni en
 * el Content-Type que manda el navegador: verifica el contenido real.
 */

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024; // 5 MB

const UPLOAD_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Valida un archivo subido ($_FILES[...]) y, si es una imagen válida,
 * lo mueve a uploads/ con un nombre aleatorio y la extensión correcta
 * según su contenido real (nunca la que mandó el cliente).
 *
 * @param array $archivo Uno de los elementos de $_FILES.
 * @return array{success: bool, path?: string, error?: string}
 */
function guardarImagenSubida(array $archivo): array {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Suba una imagen válida.'];
    }

    if ($archivo['size'] > UPLOAD_MAX_BYTES) {
        return ['success' => false, 'error' => 'La imagen no puede pesar más de 5 MB.'];
    }

    // getimagesize() falla si el archivo no es una imagen real, sin importar
    // la extensión o el Content-Type que haya mandado el navegador.
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false || !isset(UPLOAD_MIME_EXT[$info['mime']])) {
        return ['success' => false, 'error' => 'El archivo debe ser una imagen JPG, PNG o WEBP válida.'];
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = UPLOAD_MIME_EXT[$info['mime']];
    $nombreSeguro = bin2hex(random_bytes(16)) . '.' . $extension;
    $destino = $uploadDir . $nombreSeguro;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        return ['success' => false, 'error' => 'Error al guardar la imagen.'];
    }

    return ['success' => true, 'path' => 'uploads/' . $nombreSeguro];
}
?>
