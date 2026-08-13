<?php
declare(strict_types=1);

function bit_download_serve_file(string $path, string $fileName): void
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function bit_download_resolve_path(string $relativePath): ?string
{
    return bit_storage_resolve_path($relativePath);
}
