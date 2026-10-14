<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $import = new UsersImport();
            Excel::import($import, $request->file('file'));

            $count = $import->getRowCount();
            return back()->with('success', "Berhasil mengimpor {$count} user dari file Excel.");
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach (array_slice($failures, 0, 5) as $failure) {
                $messages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return back()->with('error', 'Gagal import: ' . implode(' | ', $messages));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk import user.
     */
    public function downloadTemplate()
    {
        $headers = ['nama', 'nis_nip', 'email', 'phone', 'role'];
        $examples = [
            ['Budi Santoso', '12345', 'budi@email.com', '08123456789', 'siswa'],
            ['Ibu Teknisi', 'NIP001', 'teknisi@silamet.id', '', 'teknisi'],
        ];

        $callback = function () use ($headers, $examples) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($examples as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_user_silamet.csv"',
        ]);
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
