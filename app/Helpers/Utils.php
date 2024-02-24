<?php

if (!function_exists('documents_path')) {
    function documents_path(string $path = null): string {
        $documents_path = base_path('resources/documents');

        return $documents_path . ($path ? '/' . $path : '');
    }
}
