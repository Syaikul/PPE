<?php

namespace App\Models;

use App\Services\AccessControl;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'all_gudang', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'all_gudang'        => 'boolean',
        ];
    }

    public function gudangAccess(): HasMany
    {
        return $this->hasMany(UserGudang::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === AccessControl::SUPERADMIN;
    }

    public function canView(string $module): bool
    {
        return AccessControl::allows($this, $module, AccessControl::LEVEL_VIEW);
    }

    public function canCrud(string $module): bool
    {
        return AccessControl::allows($this, $module, AccessControl::LEVEL_CRUD);
    }

    public function canAccessGudang(int $idgudang): bool
    {
        if ($this->all_gudang || $this->isSuperAdmin()) {
            return true;
        }

        if ($this->relationLoaded('gudangAccess')) {
            return $this->gudangAccess->contains('idgudang', $idgudang);
        }

        return $this->gudangAccess()->where('idgudang', $idgudang)->exists();
    }

    /** @return array<int, int> */
    public function allowedGudangIds(): array
    {
        if ($this->all_gudang || $this->isSuperAdmin()) {
            return [];
        }

        return $this->gudangAccess()->pluck('idgudang')->map(fn ($id) => (int) $id)->all();
    }

    public function gudangLabel(): string
    {
        if ($this->all_gudang || $this->isSuperAdmin()) {
            return 'Seluruh Gudang';
        }

        $ids = $this->relationLoaded('gudangAccess')
            ? $this->gudangAccess->pluck('idgudang')->all()
            : $this->allowedGudangIds();

        if ($ids === []) {
            return 'Belum ditugaskan';
        }

        $map = collect(\App\Services\MasterApiService::gudang())->keyBy('idgudang');

        return collect($ids)
            ->map(fn ($id) => $map[$id]['namagudang'] ?? 'Gudang #'.$id)
            ->implode(', ');
    }
}
