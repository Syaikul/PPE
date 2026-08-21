<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if(($method ?? 'POST') === 'PUT')
                    @method('PUT')
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Google</label>
                            <input type="email" name="email" class="form-control" required placeholder="nama@perusahaan.com">
                            <small class="text-muted">Email ini harus sama dengan akun Google Workspace user.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role</label>
                            <select name="role" class="form-select select-role" required>
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input chk-active" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Akun aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check mb-2">
                                <input class="form-check-input chk-all-gudang" type="checkbox" name="all_gudang" value="1">
                                <label class="form-check-label fw-semibold">Akses seluruh gudang</label>
                            </div>
                            <div class="border rounded p-3 gudang-box">
                                <div class="fw-semibold mb-2">Atau pilih gudang tertentu</div>
                                <div class="row">
                                    @forelse($gudangList as $gudang)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input chk-gudang" type="checkbox"
                                                    name="idgudang[]" value="{{ $gudang['idgudang'] }}">
                                                <label class="form-check-label">{{ $gudang['namagudang'] }}</label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted">Belum ada data gudang. Jalankan Sync Data Master dulu.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
