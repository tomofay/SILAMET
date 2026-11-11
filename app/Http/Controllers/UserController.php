<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exports\UserTemplateExport;
use App\Imports\UsersImport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Daftar semua user (paginate).
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis_nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Form tambah user baru.
     */
    public function create()
    {
        $roles = UserRole::cases();
        return view('users.create', compact('roles'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'nis_nip'  => 'required|string|max:30|unique:users,nis_nip',
            'email'    => 'nullable|email|max:100|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:32|unique:users,telegram_chat_id',
            'role'     => 'required|in:siswa,teknisi,kabeng,kaprog',
            'password' => 'nullable|string|min:6',
        ]);

        $validated['password'] = $validated['password'] ?? $validated['nis_nip'];

        $user = User::create($validated);

        // Assign Spatie role
        $spatieRole = Role::where('name', $validated['role'])->first();
        if ($spatieRole) {
            $user->assignRole($spatieRole);
        }

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" berhasil ditambahkan.");
    }

    /**
     * Form edit user.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = UserRole::cases();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'nis_nip'  => "required|string|max:30|unique:users,nis_nip,$id",
            'email'    => "nullable|email|max:100|unique:users,email,$id",
            'phone'    => 'nullable|string|max:20',
            'telegram_chat_id' => "nullable|string|max:32|unique:users,telegram_chat_id,$id",
            'role'     => 'required|in:siswa,teknisi,kabeng,kaprog',
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        // Sync Spatie role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus user (soft delete).
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // Jangan hapus diri sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" berhasil dihapus.");
    }

    /**
     * Bulk import user dari file Excel/CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        try {
            $import = new UsersImport();
            Excel::import($import, $request->file('file'));

            $count = $import->getRowCount();
            $errors = $import->getErrors();

            if ($count === 0 && !empty($errors)) {
                return back()->with('error', 'Gagal import: ' . implode(' | ', array_slice($errors, 0, 3)));
            }

            $successMsg = "Berhasil mengimpor {$count} user dari file Excel.";
            if (!empty($errors)) {
                $successMsg .= " (" . count($errors) . " baris dilewati karena format tidak sesuai: " . implode('; ', array_slice($errors, 0, 2)) . ")";
            }

            return back()->with('success', $successMsg);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach (array_slice($failures, 0, 5) as $failure) {
                $messages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return back()->with('error', 'Gagal import: ' . implode(' | ', $messages));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel asli (.xlsx) untuk import user.
     * Tabel otomatis terpisah per kolom di Microsoft Excel, Google Sheets, dll.
     */
    public function downloadTemplate()
    {
        return Excel::download(new UserTemplateExport, 'template_import_user_silamet.xlsx');
    }

    /**
     * Cetak kartu user (halaman print-friendly).
     */
    public function printCards(Request $request)
    {
        $ids = $request->get('ids');

        if ($ids) {
            $users = User::whereIn('id', explode(',', $ids))->get();
        } else {
            // Jika tidak ada id spesifik, cetak semua siswa
            $users = User::where('role', 'siswa')->get();
        }

        return view('users.cards', compact('users'));
    }
}
