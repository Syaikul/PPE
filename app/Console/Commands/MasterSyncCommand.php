<?php

namespace App\Console\Commands;

use App\Services\MasterSyncService;
use Illuminate\Console\Command;

class MasterSyncCommand extends Command
{
    protected $signature = 'master:sync
                            {endpoint? : Sync satu endpoint saja (gudang, personel, posisi, barang-with-varian, posisippe)}
                            {--status : Tampilkan status data master lokal tanpa melakukan sync}';

    protected $description = 'Tarik data master dari API dan simpan ke database lokal (sync manual)';

    public function handle(): int
    {
        if ($this->option('status')) {
            return $this->tampilkanStatus();
        }

        $endpoint = $this->argument('endpoint');

        if ($endpoint && ! array_key_exists($endpoint, MasterSyncService::ENDPOINTS)) {
            $this->error('Endpoint tidak dikenal: '.$endpoint);
            $this->line('Pilihan: '.implode(', ', array_keys(MasterSyncService::ENDPOINTS)));

            return self::FAILURE;
        }

        $this->info('Sumber API: '.MasterSyncService::baseUrl());
        $this->newLine();

        $hasil = $endpoint
            ? [MasterSyncService::syncOne($endpoint)]
            : MasterSyncService::syncAll();

        $this->table(
            ['Data', 'Status', 'Jumlah', 'Keterangan'],
            array_map(fn (array $r) => [
                $r['label'],
                $r['ok'] ? 'OK' : 'GAGAL',
                $r['jumlah'],
                $r['error'] ?? '-',
            ], $hasil)
        );

        $gagal = array_filter($hasil, fn (array $r) => ! $r['ok']);

        if ($gagal !== []) {
            $this->error(count($gagal).' data gagal di-sync. Data lama tetap dipakai.');

            return self::FAILURE;
        }

        $this->info('Semua data master berhasil disimpan ke database lokal.');

        return self::SUCCESS;
    }

    private function tampilkanStatus(): int
    {
        $this->table(
            ['Data', 'Jumlah', 'Terakhir Sync'],
            array_map(fn (array $r) => [
                $r['label'],
                $r['ada'] ? $r['jumlah'] : '-',
                $r['synced_at']?->format('d/m/Y H:i') ?? 'Belum pernah',
            ], MasterSyncService::status())
        );

        return self::SUCCESS;
    }
}
