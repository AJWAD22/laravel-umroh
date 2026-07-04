<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;

class UserController extends Controller
{
    // 1. Menampilkan Halaman Daftar Akun
    public function index()
    {
        $users = User::with('branch')->get(); // Ambil semua user beserta data cabangnya
        $branches = Branch::all(); // Ambil semua cabang untuk pilihan di form input
        
        return view('users.index', compact('users', 'branches'));
    }

    // 2. Memproses Simpan Akun Baru ke Database
    public function store(Request $request)
    {
        // Validasi inputan form
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required',
            'branch_id' => 'nullable|integer',
        ]);

        // Simpan ke database
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password), // Password di-enkripsi
            'role' => $request->role,
            'branch_id' => $request->branch_id,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->back()->with('success', 'Akun berhasil dibuat!');
    }
}