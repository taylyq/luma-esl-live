<?php

declare(strict_types=1);

namespace App\Controllers;

final class UploadController
{
    public function lesson(): void
    {
        $this->serveUpload('lessons');
    }

    public function teacher(): void
    {
        $this->serveUpload('teachers');
    }

    private function serveUpload(string $folder): void
    {
        $filename = $this->safeFilename((string) ($_GET['filename'] ?? ''));
        if ($filename === '') {
            $this->notFound();
        }

        $paths = [
            upload_storage_path($folder) . '/' . $filename,
            dirname(__DIR__, 2) . '/public/uploads/' . $folder . '/' . $filename,
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $this->serve($path);
            }
        }

        $this->notFound();
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', rawurldecode($filename)));
        $filename = trim((string) preg_replace('/[^\w.\- ]+/', '-', $filename));

        return in_array($filename, ['', '.', '..'], true) ? '' : $filename;
    }

    private function serve(string $path): never
    {
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mime, $allowed, true)) {
            $this->notFound();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($path);
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit;
    }
}
