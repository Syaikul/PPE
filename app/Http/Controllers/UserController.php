<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGudang;
use App\Services\AccessControl;
use App\Services\MasterApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('gudangAccess')->latest()->get();
        $gudangList = MasterApiService::gudang();
        $roles = AccessControl::roles();

        return view('users.index', compact('users', 'gudangList', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name'       => $data['name'],
                'email'      => strtolower($data['email']),
                'role'       => $data['role'],
                'is_active'  => $data['is_active'],
                'all_gudang' => $data['all_gudang'],
                'password'   => Hash::make(Str::random(40)),
            ]);

            $this->syncGudang($user, $data);
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil ditambahkan. User bisa login dengan Google memakai email tersebut.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user->id);

        if ($user->id === auth()->id() && $data['role'] !== AccessControl::SUPERADMIN) {
            return back()->with('error', 'Anda tidak bisa menurunkan role akun Anda sendiri.');
        }

        if ($user->id === auth()->id() && ! $data['is_active']) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
        }

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'name'       => $data['name'],
                'email'      => strtolower($data['email']),
                'role'       => $data['role'],
                'is_active'  => $data['is_active'],
                'all_gudang' => $data['all_gudang'],
            ]);

            $this->syncGudang($user, $data);
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreId)],
            'role'       => ['required', Rule::in(array_keys(AccessControl::roles()))],
            'is_active'  => 'nullable|boolean',
            'all_gudang' => 'nullable|boolean',
            'idgudang'   => 'nullable|array',
            'idgudang.*' => 'integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['all_gudang'] = $request->boolean('all_gudang');
        $validated['idgudang'] = $validated['idgudang'] ?? [];

        if ($validated['role'] === AccessControl::SUPERADMIN) {
            $validated['all_gudang'] = true;
        }

        if (! $validated['all_gudang'] && $validated['idgudang'] === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'idgudang' => 'Pilih minimal satu gudang, atau centang akses seluruh gudang.',
            ]);
        }

        return $validated;
    }

    private function syncGudang(User $user, array $data): void
    {
        UserGudang::where('user_id', $user->id)->delete();

        if ($data['all_gudang']) {
            return;
        }

        foreach (array_unique($data['idgudang']) as $idgudang) {
            UserGudang::create([
                'user_id'  => $user->id,
                'idgudang' => (int) $idgudang,
            ]);
        }
    }
}
