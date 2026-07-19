<?php

declare(strict_types=1);

final class Upload
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public static function image(array $file): ?string
    {
        if (empty($file['tmp_name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Не удалось загрузить изображение.');
        }

        $maxBytes = (int) config('uploads.max_bytes', 4 * 1024 * 1024);
        if ((int) $file['size'] > $maxBytes) {
            throw new RuntimeException('Файл слишком большой. Максимум 4 МБ.');
        }

        $mime = self::mime((string) $file['tmp_name']);
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            throw new RuntimeException('Поддерживаются только JPG, PNG, WebP и GIF.');
        }

        $dir = (string) config('uploads.dir');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $original = pathinfo((string) ($file['name'] ?? 'image'), PATHINFO_FILENAME);
        $name = slugify($original) . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . self::MIME_EXTENSIONS[$mime];
        $target = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new RuntimeException('Не удалось сохранить изображение.');
        }

        return 'uploads/menu/' . $name;
    }

    private static function mime(string $tmpName): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (is_string($mime)) {
                    return $mime;
                }
            }
        }

        $info = getimagesize($tmpName);
        return is_array($info) && !empty($info['mime']) ? (string) $info['mime'] : '';
    }
}

