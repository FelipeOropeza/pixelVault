<?php

namespace App\Traits;

use Illuminate\Support\Number;

trait HasFileHelpers
{
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        return Number::fileSize($bytes, $precision);
    }

    public function getIcon(string $mime): string
    {
        if (str_contains($mime, 'image')) {
            return 'photo';
        }
        if (str_contains($mime, 'video')) {
            return 'video-camera';
        }
        if (str_contains($mime, 'pdf')) {
            return 'document-text';
        }
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) {
            return 'archive-box';
        }
        if (str_contains($mime, 'audio')) {
            return 'musical-note';
        }

        return 'document';
    }
}
