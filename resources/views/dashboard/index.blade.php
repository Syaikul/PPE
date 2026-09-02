@extends('layouts.kai')

@section('page_title', 'Dashboard — '.($gudang['namagudang'] ?? 'Gudang'))

@section('content')
<style>
    .dashboard-summary-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .07);
        height: 100%;
    }
    .dashboard-summary-icon {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        font-size: 1.15rem;
        height: 44px;
        justify-content: center;
        width: 44px;
    }
    .dashboard-summary-value {
        color: #1e293b;
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
    }
    .dashboard-alert {
        border: 0;
        border-left: 4px solid;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
        display: block !important;
        position: relative !important;
        text-align: left !important;
        padding: 0.75rem 2.75rem 0.75rem 0.85rem !important;
        margin-bottom: 0.5rem;
    }
    .dashboard-alert .dashboard-alert-body {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        text-align: left !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dashboard-alert .dashboard-alert-row {
        display: grid !important;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: start !important;
        justify-content: stretch !important;
        justify-items: start !important;
        column-gap: 0.5rem;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        text-align: left !important;
    }
    .dashboard-alert .dashboard-alert-row > div {
        width: 100%;
        margin: 0 !important;
        text-align: left !important;
    }
    .dashboard-alert .btn-close {
        position: absolute !important;
        top: 0.7rem !important;
        right: 0.7rem !important;
        left: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dashboard-alert-danger { border-left-color: #ef4444; background: #fef2f2; color: #7f1d1d; }
    .dashboard-alert-warning { border-left-color: #f59e0b; background: #fffbeb; color: #78350f; }
    .dashboard-alert-info { border-left-color: #3b82f6; background: #eff6ff; color: #1e3a8a; }
    .dashboard-alert-success { border-left-color: #10b981; background: #ecfdf5; color: #065f46; }
    .dashboard-section-title { color: #334155; font-size: 1rem; font-weight: 700; }
    .dashboard-empty {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        color: #64748b;
        padding: 1rem;
        text-align: center;
    }
    .dashboard-panel {
        display: flex;
        flex-direction: column;
        max-height: 28rem;
    }
    .dashboard-panel-body {
        min-height: 0;
        overflow-y: auto;
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h3 class="mb-1">Ringkasan {{ $gudang['namagudang'] ?? 'Gudang' }}</h3>
        <p class="text-muted mb-0">Informasi stok dan aktivitas yang memerlukan perhatian.</p>
    </div>
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-warehouse me-1"></i> Ganti Gudang
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <a href="{{ route('gudang.peminjaman-ppe', $idgudang) }}" class="text-decoration-none text-reset">
            <div class="card dashboard-summary-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="dashboard-summary-icon" style="background:#eff6ff;color:#3b82f6">
                        <i class="fas fa-handshake"></i>
                    </span>
                    <div>
                        <div class="dashboard-summary-value">{{ $summary['peminjaman_menunggu'] }}</div>
                        <small class="text-muted">Peminjaman menunggu approval</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card dashboard-summary-card">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="dashboard-summary-icon" style="background:#ecfdf5;color:#10b981">
                    <i class="fas fa-boxes"></i>
                </span>
                <div>
                    <div class="dashboard-summary-value">{{ $summary['total_stok'] }}</div>
                    <small class="text-muted">Total Unit Stok</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card dashboard-summary-card">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="dashboard-summary-icon" style="background:#fff7ed;color:#f97316">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <div>
                    <div class="dashboard-summary-value">{{ $summary['stok_perlu_atensi'] }}</div>
                    <small class="text-muted">Stok Perlu Atensi</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('gudang.personel', $idgudang) }}" class="text-decoration-none text-reset">
            <div class="card dashboard-summary-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="dashboard-summary-icon" style="background:#f5f3ff;color:#8b5cf6">
                        <i class="fas fa-users"></i>
                    </span>
                    <div>
                        <div class="dashboard-summary-value">{{ $summary['personel'] }}</div>
                        <small class="text-muted">Personel</small>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

@if($pengajuanBaru->isNotEmpty() || $hasilPengajuan->isNotEmpty())
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <div class="dashboard-section-title">
                <i class="fas fa-bell me-2 text-primary"></i>Notifikasi Baru
            </div>
            <small class="text-muted">Notifikasi ini tidak muncul lagi setelah ditutup.</small>
        </div>
        <div class="card-body pt-2">
            @foreach($pengajuanBaru as $item)
                <div class="alert dashboard-alert dashboard-alert-info alert-dismissible fade show dashboard-once-notification"
                    data-event-key="{{ $item['event_key'] }}" role="alert">
                    <div class="dashboard-alert-body">
                        <strong>Pengajuan peminjaman baru.</strong>
                        {{ $item['pihak_gudang'] }} mengajukan {{ $item['qty'] }} unit {{ $item['label'] }}.
                        <a href="{{ route('gudang.peminjaman-ppe', $idgudang) }}" class="alert-link ms-1">Periksa pengajuan</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endforeach

            @foreach($hasilPengajuan as $item)
                @if($item['status'] === \App\Models\PeminjamanPpe::STATUS_APPROVED)
                    <div class="alert dashboard-alert dashboard-alert-success alert-dismissible fade show dashboard-once-notification"
                        data-event-key="{{ $item['event_key'] }}" role="alert">
                        <div class="dashboard-alert-body">
                            <strong>Pengajuan disetujui.</strong>
                            {{ $item['qty'] }} unit {{ $item['label'] }} dari {{ $item['pihak_gudang'] }} telah disetujui.
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @else
                    <div class="alert dashboard-alert dashboard-alert-danger alert-dismissible fade show dashboard-once-notification"
                        data-event-key="{{ $item['event_key'] }}" role="alert">
                        <div class="dashboard-alert-body">
                            <strong>Pengajuan ditolak.</strong>
                            {{ $item['qty'] }} unit {{ $item['label'] }} dari {{ $item['pihak_gudang'] }} ditolak.
                            @if($item['catatan_tolak'])
                                Alasan: {{ $item['catatan_tolak'] }}
                            @endif
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 dashboard-panel">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="dashboard-section-title">
                    <i class="fas fa-box-open me-2 text-warning"></i>Stok Perlu Perhatian
                </div>
                <a href="{{ route('gudang.stok', $idgudang) }}" class="btn btn-sm btn-outline-primary">Lihat Stok</a>
            </div>
            <div class="card-body dashboard-panel-body">
                @forelse($stokAlerts as $item)
                    <div class="alert dashboard-alert {{ $item['level'] === \App\Services\StokMinMaxService::LEVEL_RED ? 'dashboard-alert-danger' : 'dashboard-alert-warning' }} alert-dismissible fade show"
                        role="alert">
                        <div class="dashboard-alert-body">
                            <div class="dashboard-alert-row">
                            <span class="badge flex-shrink-0" style="{{ $item['badge']['style'] }}">{{ $item['qty'] }}</span>
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                @if($item['kode'] && $item['kode'] !== '-')
                                    <small class="text-muted">({{ $item['kode'] }})</small>
                                @endif
                                <div class="small">
                                    {{ $item['level'] === \App\Services\StokMinMaxService::LEVEL_RED ? 'Kritis' : 'Menipis' }}
                                    — stok {{ $item['qty'] }}, batas minimum {{ $item['min'] }}.
                                </div>
                            </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @empty
                    <div class="dashboard-empty">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Tidak ada stok kritis atau menipis.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0 dashboard-panel">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div class="dashboard-section-title">
                    <i class="fas fa-handshake me-2 text-primary"></i>Belum Dikembalikan
                </div>
                <a href="{{ route('gudang.peminjaman-ppe', $idgudang) }}" class="btn btn-sm btn-outline-primary">Lihat Peminjaman</a>
            </div>
            <div class="card-body dashboard-panel-body">
                @forelse($belumDikembalikan as $item)
                    <div class="alert dashboard-alert dashboard-alert-warning alert-dismissible fade show" role="alert">
                        <div class="dashboard-alert-body">
                            <strong>{{ $item['qty'] }} unit {{ $item['label'] }}</strong>
                            <div class="small mt-1">
                                @if($item['sebagai'] === 'peminjam')
                                    Dipinjam dari {{ $item['pihak_gudang'] }} dan belum dikembalikan.
                                @else
                                    Dipinjam oleh {{ $item['pihak_gudang'] }} dan belum dikembalikan.
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @empty
                    <div class="dashboard-empty">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Tidak ada peminjaman yang belum dikembalikan.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white">
        <div class="dashboard-section-title">
            <i class="fas fa-clipboard-check me-2 text-secondary"></i>Aktivitas Lain
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-6 col-lg-4">
                <a href="{{ route('gudang.approval-demob', $idgudang) }}" class="text-decoration-none">
                    <div class="border rounded p-3 h-100">
                        <div class="fs-4 fw-bold text-dark">{{ $summary['demob_menunggu'] }}</div>
                        <small class="text-muted">Approval Demob Menunggu</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-4">
                <a href="{{ route('gudang.permintaan', $idgudang) }}" class="text-decoration-none">
                    <div class="border rounded p-3 h-100">
                        <div class="fs-4 fw-bold text-dark">{{ $summary['mr_belum_selesai'] }}</div>
                        <small class="text-muted">MR Belum Selesai</small>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-4">
                <a href="{{ route('gudang.mobilisasi', $idgudang) }}" class="text-decoration-none">
                    <div class="border rounded p-3 h-100">
                        <div class="fs-4 fw-bold text-dark">{{ $summary['mobilisasi_aktif'] }}</div>
                        <small class="text-muted">Mobilisasi Aktif</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.dashboard-once-notification').forEach(function (notification) {
    notification.addEventListener('close.bs.alert', function () {
        fetch(@json(route('dashboard.notifications.dismiss')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token())
            },
            body: JSON.stringify({ event_key: notification.dataset.eventKey })
        }).catch(function () {
            // Jika jaringan gagal, notifikasi aman muncul lagi saat refresh.
        });
    });
});
</script>
@endpush
@endsection
