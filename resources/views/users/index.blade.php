<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun - Radar Umroh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-9">
                <h2>Manajemen Akun & Staff</h2>
                <p class="text-muted">Kelola akun Super Admin, Admin Cabang, Tour Leader, Muthawwif, dan Jamaah.</p>
            </div>
            <div class="col-md-3 text-end">
                <a href="/" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card p-4 shadow-sm">
                    <h5>Tambah Akun Baru</h5>
                    <hr>
                    <form action="/users" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. HP (Opsional)</label>
                            <input type="text" name="phone_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hak Akses / Role</label>
                            <select name="role" class="form-select" required>
                                <option value="super_admin">Super Admin (Pusat)</option>
                                <option value="admin">Admin Cabang</option>
                                <option value="tour_leader">Tour Leader</option>
                                <option value="muthawwif">Muthawwif</option>
                                <option value="jamaah">Jamaah</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penempatan Cabang</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- Tanpa Cabang (Pusat) --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name_branch }} ({{ $b->city }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Akun</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card p-4 shadow-sm table-responsive">
                    <h5>Daftar Akun Aktif</h5>
                    <hr>
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Cabang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td><b>{{ $user->name }}</b><br><small class="text-muted">{{ $user->phone_number ?? '-' }}</small></td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-info text-dark">{{ strtoupper(str_replace('_', ' ', $user->role)) }}</span></td>
                                <td>{{ $user->branch ? $user->branch->name_branch : 'Pusat (HQ)' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>