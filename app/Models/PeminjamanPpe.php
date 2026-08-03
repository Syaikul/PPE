<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PeminjamanPpe extends Model
{
    protected $table = 'peminjaman_ppe';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'idgudang_peminjam',
        'idgudang_sumber',
        'idsubbarang',
        'idbarangvarian',
        'qty',
        'catatan',
        'catatan_tolak',
        'status',
        'tanggal_pengajuan',
        'tanggal_diterima',
        'tanggal_ditolak',
        'tanggal_dikembalikan',
    ];

    protected $casts = [
        'tanggal_pengajuan'     => 'date',
        'tanggal_diterima'      => 'date',
        'tanggal_ditolak'       => 'date',
        'tanggal_dikembalikan'  => 'date',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function statusLabel(): string
    {
        if ($this->isPending()) {
            return 'Menunggu Approval';
        }

        if ($this->isRejected()) {
            return 'Not Approve';
        }

        return 'approve';
    }

    /** @return array<int, string> */
    public function tanggalDisplayLines(): array
    {
        $parts = [];

        if ($this->tanggal_pengajuan) {
            $parts[] = self::formatTanggalLabel($this->tanggal_pengajuan, 'pengajuan');
        }

        if ($this->tanggal_ditolak) {
            $parts[] = self::formatTanggalLabel($this->tanggal_ditolak, 'Ditolak');
        }

        if ($this->tanggal_diterima) {
            $parts[] = self::formatTanggalLabel($this->tanggal_diterima, 'diterima');
        }

        if ($this->tanggal_dikembalikan) {
            $parts[] = self::formatTanggalLabel($this->tanggal_dikembalikan, 'barang dikembalikan');
        }

        return $parts;
    }

    public function tanggalDisplay(): string
    {
        return implode(', ', $this->tanggalDisplayLines());
    }

    private static function formatTanggalLabel(Carbon $date, string $label): string
    {
        return $date->locale('id')->translatedFormat('j F Y').' ('.$label.')';
    }
}
