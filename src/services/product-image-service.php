<?php

class ProductImageService
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_DIMENSION = 6000;

    public static function storeUploaded(array $file): string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Choose a product image before adding the product.');
        }
        if (($file['error'] ?? UPLOAD_ERR_CANT_WRITE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The product image could not be uploaded. Try another file.');
        }
        if ((int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > self::MAX_BYTES) {
            throw new InvalidArgumentException('Product images must be no larger than 5 MB.');
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('The uploaded product image is invalid.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowedTypes[$mime])) {
            throw new InvalidArgumentException('Use a JPEG, PNG, or WebP product image.');
        }

        $dimensions = @getimagesize($temporaryPath);
        if (!$dimensions
            || $dimensions[0] < 1
            || $dimensions[1] < 1
            || $dimensions[0] > self::MAX_DIMENSION
            || $dimensions[1] > self::MAX_DIMENSION
        ) {
            throw new InvalidArgumentException('Use a valid image no larger than 6000 × 6000 pixels.');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The product image directory could not be created.');
        }

        $filename = 'product_' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mime];
        if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
            throw new RuntimeException('The product image could not be saved.');
        }

        return 'uploads/products/' . $filename;
    }

    public static function deleteManaged(?string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', trim((string) $relativePath));
        if (!str_starts_with($relativePath, 'uploads/products/')) {
            return;
        }

        $uploadRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/products');
        $file = realpath(dirname(__DIR__, 2) . '/public/' . $relativePath);
        if (!$uploadRoot || !$file) {
            return;
        }

        $uploadRoot = rtrim(str_replace('\\', '/', $uploadRoot), '/') . '/';
        $file = str_replace('\\', '/', $file);
        if (str_starts_with($file, $uploadRoot) && is_file($file)) {
            unlink($file);
        }
    }
}
