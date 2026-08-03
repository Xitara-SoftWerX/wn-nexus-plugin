<?php

namespace Xitara\Nexus\Models;

use InvalidArgumentException;
use System\Models\File;

class ScopedFile extends File
{
    private const METADATA_STORAGE_PATH = 'nexus_storage_path';

    public function setStoragePath(?string $path) : static
    {
        $metadata = is_array($this->metadata)
            ? $this->metadata
            : [];

        $path = $this->normalizeStoragePath($path);

        if ($path === null) {
            unset($metadata[self::METADATA_STORAGE_PATH]);
        } else {
            $metadata[self::METADATA_STORAGE_PATH] = $path;
        }

        $this->metadata = $metadata;

        return $this;
    }

    public function getStoragePath() : ?string
    {
        $metadata = is_array($this->metadata)
            ? $this->metadata
            : [];

        return $metadata[self::METADATA_STORAGE_PATH] ?? null;
    }

    public function getStorageDirectory() : string
    {
        return $this->appendStoragePath(
            parent::getStorageDirectory()
        );
    }

    public function getPublicPath() : string
    {
        return $this->appendStoragePath(
            parent::getPublicPath()
        );
    }

    protected function appendStoragePath(string $basePath) : string
    {
        $basePath = rtrim($basePath, '/') . '/';
        $storagePath = $this->getStoragePath();

        if ($storagePath === null) {
            return $basePath;
        }

        return $basePath . $storagePath . '/';
    }

    protected function normalizeStoragePath(?string $path) : ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim(
            str_replace('\\', '/', $path),
            '/'
        );

        if (
            str_contains($path, '..')
            || !preg_match('#^[a-zA-Z0-9/_-]+$#', $path)
        ) {
            throw new InvalidArgumentException(
                sprintf('Invalid attachment storage path: "%s"', $path)
            );
        }

        return $path;
    }
}
