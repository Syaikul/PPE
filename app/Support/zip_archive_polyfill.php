<?php

/**
 * Polyfill ZipArchive untuk Apache/Laragon yang belum load ext-zip.
 * Dipakai PhpSpreadsheet saat membaca template .xlsx.
 */
if (class_exists('ZipArchive', false)) {
    return;
}

class ZipArchive
{
    public const CREATE = 1;

    public const EXCL = 2;

    public const CHECKCONS = 4;

    public const OVERWRITE = 8;

    public const RDONLY = 16;

    public const FL_NOCASE = 1;

    public const FL_NODIR = 2;

    public const ER_OK = 0;

    public const ER_NOZIP = 19;

    public int $numFiles = 0;

    private ?\App\Support\SimpleZipFile $zip = null;

    public function open(string $filename, int $flags = 0): bool|int
    {
        $this->zip = new \App\Support\SimpleZipFile();

        if (! $this->zip->open($filename)) {
            $this->zip = null;

            return self::ER_NOZIP;
        }

        $this->numFiles = $this->zip->count();

        return true;
    }

    public function close(): bool
    {
        $this->zip?->close();
        $this->zip = null;
        $this->numFiles = 0;

        return true;
    }

    public function locateName(string $name, int $flags = 0): int|false
    {
        if ($this->zip === null) {
            return false;
        }

        return $this->zip->locate($name, ($flags & self::FL_NOCASE) === self::FL_NOCASE);
    }

    public function getFromName(string $name, int $len = 0, int $flags = 0): string|false
    {
        if ($this->zip === null) {
            return false;
        }

        $contents = $this->zip->contents($name, ($flags & self::FL_NOCASE) === self::FL_NOCASE);
        if ($contents === false) {
            return false;
        }

        if ($len > 0) {
            return substr($contents, 0, $len);
        }

        return $contents;
    }

    public function getNameIndex(int $index, int $flags = 0): string|false
    {
        return $this->zip?->nameAt($index) ?? false;
    }
}
