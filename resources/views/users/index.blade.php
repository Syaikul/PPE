@extends('layouts.kai')

@section('page_title', 'Kelola Akun & Role')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Tambahkan akun dengan email Google Workspace. User login lewat Google, tanpa hafal password.</p>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahAkun">
        Tambah Akun
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-0">Daftar Akun</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelAkun" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Gudang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $akun)
                        @php
                            $gudangIds = $akun->all_gudang ? [] : $akun->gudangAccess->pluck('idgudang')->all();
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $akun->name }}</td>
                            <td>{{ $akun->email }}</td>
                            <td>
                                <span class="badge bg-primary">{{ \App\Services\AccessControl::roleLabel($akun->role) }}</span>
                            </td>
                            <td>{{ $akun->gudangLabel() }}</td>
                            <td>
                                @if($akun->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-warning btn-ubah-akun"
                                    data-id="{{ $akun->id }}"
                                    data-name="{{ $akun->name }}"
                                    data-email="{{ $akun->email }}"
                                    data-role="{{ $akun->role }}"
                                    data-active="{{ $akun->is_active ? 1 : 0 }}"
                                    data-all="{{ $akun->all_gudang ? 1 : 0 }}"
                                    data-gudang="{{ implode(',', $gudangIds) }}"
                                    data-bs-toggle="modal" data-bs-target="#modalUbahAkun">
                                    Ubah
                                </button>
                                @if($akun->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $akun) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus akun {{ $akun->email }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('users._form_modal', [
    'id' => 'modalTambahAkun',
    'title' => 'Tambah Akun',
    'action' => route('users.store'),
    'method' => 'POST',
    'roles' => $roles,
    'gudangList' => $gudangList,
])

@include('users._form_modal', [
    'id' => 'modalUbahAkun',
    'title' => 'Ubah Akun',
    'action' => '',
    'method' => 'PUT',
    'roles' => $roles,
    'gudangList' => $gudangList,
    'isEdit' => true,
])

@push('scripts')
<script>
    $(function () {
        if ($.fn.DataTable) {
            $('#tabelAkun').DataTable({ pageLength: 25, order: [[0, 'asc']] });
        }

        function toggleGudang(modal) {
            var all = modal.find('.chk-all-gudang').is(':checked');
            var role = modal.find('.select-role').val();
            if (role === 'superadmin') {
                modal.find('.chk-all-gudang').prop('checked', true);
                all = true;
            }
            modal.find('.chk-gudang').prop('disabled', all);
            modal.find('.gudang-box').toggleClass('opacity-50', all);
        }

        $('.modal').on('change', '.chk-all-gudang, .select-role', function () {
            toggleGudang($(this).closest('.modal'));
        });

        $('.btn-ubah-akun').on('click', function () {
            var btn = $(this);
            var modal = $('#modalUbahAkun');
            var id = btn.data('id');
            modal.find('form').attr('action', '{{ url('/users') }}/' + id);
            modal.find('[name="name"]').val(btn.data('name'));
            modal.find('[name="email"]').val(btn.data('email'));
            modal.find('.select-role').val(btn.data('role'));
            modal.find('.chk-active').prop('checked', String(btn.data('active')) === '1');
            modal.find('.chk-all-gudang').prop('checked', String(btn.data('all')) === '1');
            modal.find('.chk-gudang').prop('checked', false);
            var ids = String(btn.data('gudang') || '').split(',').filter(Boolean);
            ids.forEach(function (idg) {
                modal.find('.chk-gudang[value="' + idg + '"]').prop('checked', true);
            });
            toggleGudang(modal);
        });

        $('#modalTambahAkun').on('show.bs.modal', function () {
            var modal = $(this);
            modal.find('form')[0].reset();
            modal.find('.chk-active').prop('checked', true);
            toggleGudang(modal);
        });
    });
</script>
@endpush

@endsection
