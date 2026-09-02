<?php

namespace App\Support;

use RuntimeException;

/**
 * Pembaca ZIP murni PHP (deflate/store) untuk template .xlsx
 * ketika ekstensi ext-zip tidak aktif di Apache/Laragon.
 */
class SimpleZipFile
{
    /** @var array<int, array{name: string, content: string}> */
    private array $entries = [];

    public function open(string $filename): bool
    {
        if (! is_file($filename) || ! is_readable($filename)) {
            return false;
        }

        $data = file_get_contents($filename);
        if ($data === false || strlen($data) < 22) {
            return false;
        }

        $eocd = strrpos($data, "PK\x05\x06");
        if ($eocd === false) {
            return false;
        }

        $diskEntries = unpack('v', substr($data, $eocd + 8, 2));
        $cdOffset = unpack('V', substr($data, $eocd + 16, 4));
        if ($diskEntries === false || $cdOffset === false) {
            return false;
        }

        $count = $diskEntries[1];
        $offset = $cdOffset[1];
        $this->entries = [];

        for ($i = 0; $i < $count; $i++) {
            if (substr($data, $offset, 4) !== "PK\x01\x02") {
                return false;
            }

            $method = unpack('v', substr($data, $offset + 10, 2))[1];
            $compressedSize = unpack('V', substr($data, $offset + 20, 4))[1];
            $nameLength = unpack('v', substr($data, $offset + 28, 2))[1];
            $extraLength = unpack('v', substr($data, $offset + 30, 2))[1];
            $commentLength = unpack('v', substr($data, $offset + 32, 2))[1];
            $localOffset = unpack('V', substr($data, $offset + 42, 4))[1];
            $name = substr($data, $offset + 46, $nameLength);
            $name = str_replace('\\', '/', $name);

            $localNameLength = unpack('v', substr($data, $localOffset + 26, 2))[1];
            $localExtraLength = unpack('v', substr($data, $localOffset + 28, 2))[1];
            $payload = substr(
                $data,
                $localOffset + 30 + $localNameLength + $localExtraLength,
                $compressedSize
            );

            if ($payload === false) {
                $payload = '';
            }

            $content = $this->inflate($method, $payload);
            $this->entries[] = [
                'name' => $name,
                'content' => $content,
            ];

            $offset += 46 + $nameLength + $extraLength + $commentLength;
        }

        return true;
    }

    public function close(): void
    {
        $this->entries = [];
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function nameAt(int $index): string|false
    {
        return $this->entries[$index]['name'] ?? false;
    }

    public function locate(string $name, bool $noCase = false): int|false
    {
        $name = str_replace('\\', '/', $name);

        foreach ($this->entries as $i => $entry) {
            if ($noCase) {
                if (strcasecmp($entry['name'], $name) === 0) {
                    return $i;
                }
            } elseif ($entry['name'] === $name) {
                return $i;
            }
        }

        return false;
    }

    public function contents(string $name, bool $noCase = false): string|false
    {
        $index = $this->locate($name, $noCase);

        if ($index === false) {
            return false;
        }

        return $this->entries[$index]['content'];
    }

    private function inflate(int $method, string $payload): string
    {
        if ($method === 0) {
            return $payload;
        }

        if ($method === 8) {
            if ($payload === '') {
                return '';
            }

            $inflated = @gzinflate($payload);
            if ($inflated === false) {
                throw new RuntimeException('Gagal mengekstrak file Excel (deflate).');
            }

            return $inflated;
        }

        throw new RuntimeException('Metode kompresi ZIP tidak didukung: '.$method);
    }
}
