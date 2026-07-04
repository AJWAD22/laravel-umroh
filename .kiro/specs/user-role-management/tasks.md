# Implementation Plan: Sistem Manajemen User & Role

## Overview

Implementasi sistem manajemen user & role untuk aplikasi travel umroh berbasis Laravel 11.
Menggunakan arsitektur berlapis Controller → Service → Repository → Model → Database, dilengkapi
Event-Driven side effects melalui Laravel Queue dan Broadcasting (Reverb/Pusher), serta pengujian
property-based menggunakan `eris/eris`.

## Tasks

- [x] 1. Database Migrations — Buat tabel-tabel baru dan perbarui tabel existing
  - [x] 1.1 Buat migration `create_departures_table`
    - Kolom: `id`, `branch_id` (FK→branches), `program_name`, `departure_date`, `return_date`, `timestamps`
    - Tambahkan index: `branch_id`
    - _Requirements: 26.1, 26.2_
  - [x] 1.2 Buat migration `create_groups_table`
    - Kolom: `id`, `departure_id` (FK→departures), `group_name`, `tour_leader_id` (FK→users nullable), `muthawwif_id` (FK→users nullable), `timestamps`
    - Tambahkan index: `departure_id`, `tour_leader_id`, `muthawwif_id`
    - _Requirements: 27.1, 27.2_
  - [x] 1.3 Buat migration `create_group_jamaah_table`
    - Kolom: `id`, `group_id` (FK→groups), `user_id` (FK→users), `created_at`
    - Unique constraint: `[group_id, user_id]`
    - _Requirements: 27.3_
  - [x] 1.4 Buat migration `create_hotels_table`
    - Kolom: `id`, `departure_id` (FK→departures), `hotel_name`, `address` (text), `latitude` (double), `longitude` (double), `timestamps`
    - _Requirements: 28.1, 28.2_
  - [x] 1.5 Buat migration `create_geofences_table`
    - Kolom: `id`, `group_id` (FK→groups), `name`, `center_latitude` (double), `center_longitude` (double), `radius_meters` (int), `is_active` (boolean, default true), `timestamps`
    - _Requirements: 20.1_
  - [x] 1.6 Buat migration `update_locations_table_add_gps_fields`
    - Tambahkan kolom: `accuracy` (float), `speed` (float), `heading` (float), `battery` (tinyint), `resolved_by` (FK→users nullable), `resolved_at` (timestamp nullable), `timestamp` (timestamp — ISO 8601 dari device)
    - Tambahkan index: `[user_id, created_at]`, `[is_sos, resolved_at]`
    - _Requirements: 13.1, 13.2, 13.3, 29.1_
  - [x] 1.7 Buat migration `update_users_table_add_indexes`
    - Tambahkan index compound: `[branch_id, role]`
    - Pastikan FK constraint `branch_id → branches.id` terdefinisi dengan benar
    - _Requirements: 3.1, 5.6_

- [ ] 2. Eloquent Models — Buat dan perbarui model-model Eloquent
  - [~] 2.1 Perbarui `app/Models/User.php`
    - Tambahkan `use HasApiTokens` (sudah ada), relasi: `locations()`, `latestLocation()`, `groups()`, `ledGroups()`, `guidedGroups()`
    - Tambahkan scopes: `scopeOfBranch()`, `scopeOfRole()`
    - Tambahkan method `isOnline(int $thresholdMinutes = 5): bool`
    - Ganti atribut `#[Fillable]` dengan `protected $fillable` (kompatibilitas dengan factory)
    - _Requirements: 5.1, 3.1_
  - [~] 2.2 Perbarui `app/Models/Location.php`
    - Tambahkan semua fillable baru: `accuracy`, `speed`, `heading`, `battery`, `timestamp`, `resolved_at`, `resolved_by`
    - Tambahkan relasi: `resolver()` (belongsTo User via resolved_by)
    - Tambahkan scopes: `scopeActiveSos()`, `scopeLatestPerUser()`
    - _Requirements: 13.1, 14.1, 29.1_
  - [~] 2.3 Buat `app/Models/Departure.php`
    - Fillable, casts untuk `departure_date` dan `return_date` sebagai `'date'`
    - Relasi: `branch()`, `groups()`, `hotels()`
    - _Requirements: 26.1_
  - [~] 2.4 Buat `app/Models/Group.php`
    - Fillable, relasi: `departure()`, `tourLeader()`, `muthawwif()`, `jamaah()` (BelongsToMany via group_jamaah), `geofences()`
    - _Requirements: 27.1_
  - [~] 2.5 Buat `app/Models/Hotel.php`
    - Fillable, relasi: `departure()`
    - _Requirements: 28.1_
  - [~] 2.6 Buat `app/Models/Geofence.php`
    - Fillable, relasi: `group()`
    - Implementasikan method `containsPoint(float $lat, float $lng): bool` menggunakan Haversine formula
    - _Requirements: 20.1, 20.2_
  - [~] 2.7 Perbarui `app/Models/Branch.php`
    - Tambahkan relasi: `users()`, `departures()`
    - Ganti atribut `#[Fillable]` dengan `protected $fillable`
    - _Requirements: 4.1_

- [~] 3. Checkpoint — Validasi migrasi dan model
  - Jalankan `php artisan migrate:fresh` dan pastikan tidak ada error
  - Pastikan semua relasi dapat di-resolve dengan `php artisan tinker`
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum melanjutkan.

- [ ] 4. Config, Exception Handler, dan Infrastructure
  - [~] 4.1 Buat `config/umroh.php`
    - Tambahkan key: `online_threshold_minutes` → `env('ONLINE_THRESHOLD_MINUTES', 5)`
    - _Requirements: 19.1_
  - [~] 4.2 Perbarui `app/Exceptions/Handler.php`
    - Override `register()` untuk convert `AuthenticationException` → 401 JSON
    - Convert `AuthorizationException` → 403 JSON
    - Convert `ModelNotFoundException` → 404 JSON
    - Convert `ValidationException` → 422 JSON dengan format `{success, message, errors}`
    - _Requirements: 1.4, 2.2, 5.4_
  - [~] 4.3 Install dependency `eris/eris` via composer untuk property-based testing
    - Tambahkan `"eris/eris": "^0.12"` ke `require-dev` di `composer.json`
    - Jalankan `composer update`
    - _Requirements: Design Testing Strategy_

- [ ] 5. Middleware Layer
  - [~] 5.1 Buat `app/Http/Middleware/CheckRole.php`
    - Terima parameter role yang diizinkan dari route definition (e.g., `role:super_admin,admin`)
    - Jika role user tidak ada dalam daftar → return 403 JSON response
    - _Requirements: 2.1, 2.2, 2.3_
  - [~] 5.2 Buat `app/Http/Middleware/BranchScope.php`
    - Untuk user dengan role `admin`: inject `branch_id` dari `$request->user()->branch_id` ke request context
    - Simpan sebagai `$request->merge(['_scoped_branch_id' => $user->branch_id])`
    - _Requirements: 3.1, 3.2, 3.3_
  - [~] 5.3 Daftarkan kedua middleware di `bootstrap/app.php`
    - Alias `role` → `CheckRole`, alias `branch.scope` → `BranchScope`
    - _Requirements: 2.1_

- [ ] 6. Form Requests (Validasi Input)
  - [~] 6.1 Buat `app/Http/Requests/Auth/LoginRequest.php`
    - Rules: `email` required|email, `password` required|string
    - _Requirements: 1.1_
  - [~] 6.2 Buat `app/Http/Requests/User/StoreUserRequest.php`
    - Rules: `name` required|string|max:255, `email` required|email|unique:users, `password` required|min:8, `role` required|in:super_admin,admin,tour_leader,muthawwif,jamaah, `branch_id` nullable|exists:branches,id, `phone_number` nullable|string
    - `authorize()`: return true (Policy handles authorization)
    - _Requirements: 5.1, 5.4, 5.5, 5.6, 5.7_
  - [~] 6.3 Buat `app/Http/Requests/User/UpdateUserRequest.php`
    - Rules mirip StoreUserRequest, email unique kecuali user saat ini, password nullable
    - _Requirements: 5.2_
  - [~] 6.4 Buat `app/Http/Requests/Branch/StoreBranchRequest.php` dan `UpdateBranchRequest.php`
    - Rules: `name_branch` required|string|max:255, `city` required|string|max:255
    - _Requirements: 4.1_
  - [~] 6.5 Buat `app/Http/Requests/Location/StoreLocationRequest.php`
    - Rules: `latitude` between:-90,90, `longitude` between:-180,180, `accuracy` numeric|min:0, `speed` numeric|min:0, `heading` between:0,360, `battery` integer|between:0,100, `timestamp` date_format:Y-m-d\TH:i:sP
    - _Requirements: 13.2, 13.3, 13.4_
  - [~] 6.6 Buat `app/Http/Requests/Sos/StoreSosRequest.php` dan `ResolveSosRequest.php`
    - StoreSosRequest: same rules as StoreLocationRequest
    - ResolveSosRequest: `notes` nullable|string|max:500
    - _Requirements: 14.1, 29.2_
  - [~] 6.7 Buat `app/Http/Requests/Departure/StoreDepartureRequest.php` dan `UpdateDepartureRequest.php`
    - Rules: `branch_id` required|exists:branches,id, `program_name` required|string|max:255, `departure_date` required|date, `return_date` required|date|after:departure_date
    - _Requirements: 26.1, 26.2_
  - [~] 6.8 Buat `app/Http/Requests/Group/StoreGroupRequest.php` dan `AssignMemberRequest.php`
    - StoreGroupRequest: `departure_id` required|exists, `group_name` required|string|max:255
    - AssignMemberRequest: `user_id` required|exists:users,id
    - _Requirements: 27.1, 27.3_
  - [~] 6.9 Buat `app/Http/Requests/Hotel/StoreHotelRequest.php`
    - Rules: `departure_id` exists, `hotel_name` required, `address` required, `latitude` between:-90,90, `longitude` between:-180,180
    - _Requirements: 28.1, 28.3_
  - [~] 6.10 Buat `app/Http/Requests/Geofence/StoreGeofenceRequest.php`
    - Rules: `group_id` exists, `name` required, `center_latitude` between:-90,90, `center_longitude` between:-180,180, `radius_meters` integer|min:1
    - _Requirements: 20.1_
  - [~] 6.11 Buat `app/Http/Requests/History/HistoryRequest.php`
    - Rules: `start_date` nullable|date, `end_date` nullable|date|after_or_equal:start_date
    - _Requirements: 17.1_

- [ ] 7. Repository Layer
  - [~] 7.1 Buat `app/Repositories/UserRepository.php`
    - Methods: `allScoped(?int $branchId): Collection`, `find(int $id): User`, `create(array $data): User`, `update(User $user, array $data): User`, `delete(User $user): void`, `listByBranch(int $branchId): Collection`, `listByRole(string $role, ?int $branchId): Collection`
    - _Requirements: 3.1, 5.1, 6.1_
  - [~] 7.2 Buat `app/Repositories/BranchRepository.php`
    - Methods: `all(): Collection`, `find(int $id): Branch`, `create(array $data): Branch`, `update(Branch $branch, array $data): Branch`, `delete(Branch $branch): void`
    - _Requirements: 4.1_
  - [~] 7.3 Buat `app/Repositories/LocationRepository.php`
    - Methods: `store(array $data): Location`, `latestPerUser(?int $branchId): Collection`, `historyForUser(int $userId, ?Carbon $start, ?Carbon $end): Collection`, `activeSosByBranch(?int $branchId): Collection`
    - _Requirements: 13.1, 13.7, 17.1_
  - [~] 7.4 Buat `app/Repositories/DepartureRepository.php`
    - Methods: `allScoped(?int $branchId): Collection`, `find(int $id): Departure`, `create(array $data): Departure`, `update(Departure $d, array $data): Departure`, `delete(Departure $d): void`
    - _Requirements: 26.1_
  - [~] 7.5 Buat `app/Repositories/GroupRepository.php`
    - Methods: `allScoped(?int $branchId): Collection`, `find(int $id): Group`, `create(array $data): Group`, `update(Group $g, array $data): Group`, `delete(Group $g): void`, `assignLeader(Group $g, int $userId): void`, `assignMuthawwif(Group $g, int $userId): void`, `addMember(Group $g, int $userId): void`, `removeMember(Group $g, int $userId): void`
    - _Requirements: 27.1, 27.3_
  - [~] 7.6 Buat `app/Repositories/HotelRepository.php`
    - Methods: `allByDeparture(int $departureId): Collection`, `find(int $id): Hotel`, `create(array $data): Hotel`, `update(Hotel $h, array $data): Hotel`, `delete(Hotel $h): void`
    - _Requirements: 28.1_
  - [~] 7.7 Buat `app/Repositories/GeofenceRepository.php`
    - Methods: `allByGroup(int $groupId): Collection`, `find(int $id): Geofence`, `create(array $data): Geofence`, `update(Geofence $g, array $data): Geofence`, `delete(Geofence $g): void`
    - _Requirements: 20.1_
  - [~] 7.8 Buat `app/Repositories/SosRepository.php`
    - Methods: `create(array $data): Location`, `findActive(int $id): Location`, `resolve(Location $sos, int $resolvedBy): Location`, `historyScoped(?int $branchId, ?int $groupId): Collection`
    - _Requirements: 14.1, 29.1, 29.6_

- [ ] 8. Policy Layer
  - [~] 8.1 Buat `app/Policies/UserPolicy.php`
    - `viewAny`: super_admin atau admin
    - `view`: super_admin, atau admin dengan branch_id sama
    - `create`: super_admin atau admin (admin tidak bisa buat role super_admin/admin)
    - `update`: super_admin, atau admin dengan branch_id sama (tidak bisa naikkan role ke super_admin/admin)
    - `delete`: super_admin, atau admin dengan branch_id sama
    - _Requirements: 5.1, 6.1, 6.2, 6.4, 6.5_
  - [~] 8.2 Buat `app/Policies/BranchPolicy.php`
    - `viewAny`, `view`: super_admin atau admin (read-only untuk admin)
    - `create`, `update`, `delete`: super_admin only
    - _Requirements: 4.1, 4.4_
  - [~] 8.3 Buat `app/Policies/LocationPolicy.php`
    - `create`: jamaah only
    - `view`, `viewHistory`: super_admin, admin (branch-scoped), tour_leader (group-scoped), muthawwif (group-scoped)
    - _Requirements: 13.5, 17.1_
  - [~] 8.4 Buat `app/Policies/SosPolicy.php`
    - `create`: jamaah only
    - `resolve`: super_admin, admin (branch-scoped), tour_leader (group-scoped), muthawwif (group-scoped)
    - `viewHistory`: sama dengan resolve
    - _Requirements: 14.1, 29.1, 29.6_
  - [~] 8.5 Buat `app/Policies/DeparturePolicy.php`
    - `viewAny`, `create`, `update`, `delete`: super_admin atau admin (branch-scoped)
    - _Requirements: 26.1, 26.3_
  - [~] 8.6 Buat `app/Policies/GroupPolicy.php`
    - `viewAny`, `create`, `update`, `delete`, `assign`: super_admin atau admin (branch-scoped)
    - _Requirements: 27.1, 27.4_
  - [~] 8.7 Buat `app/Policies/HotelPolicy.php`
    - `viewAny`, `create`, `update`, `delete`: super_admin atau admin (branch-scoped)
    - _Requirements: 28.1, 28.4_
  - [~] 8.8 Buat `app/Policies/GeofencePolicy.php`
    - `viewAny`, `create`, `update`, `delete`: super_admin atau admin (branch-scoped)
    - _Requirements: 20.1, 20.4_
  - [~] 8.9 Daftarkan semua Policy di `app/Providers/AppServiceProvider.php` menggunakan `Gate::policy()`
    - _Requirements: 2.1_

- [ ] 9. Service Layer — Auth, User, Branch
  - [~] 9.1 Buat `app/Services/AuthService.php`
    - Method `login(array $credentials): array` — verifikasi kredensial, buat Sanctum token, return `{token, user}`
    - Method `logout(User $user): void` — revoke token aktif via `$user->currentAccessToken()->delete()`
    - _Requirements: 1.1, 1.2, 1.3, 1.5_
  - [~] 9.2 Buat `app/Services/UserService.php`
    - Method `list(User $actor): Collection` — panggil UserRepository dengan branch scoping berdasarkan role actor
    - Method `create(User $actor, array $data): User` — enforce branch_id untuk admin, validasi role restriction
    - Method `update(User $actor, User $target, array $data): User` — enforce role restriction untuk admin
    - Method `delete(User $actor, User $target): void`
    - _Requirements: 3.5, 6.1, 6.2, 6.4_
  - [~] 9.3 Buat `app/Services/BranchService.php`
    - Method `list(): Collection`, `create(array $data): Branch`, `update(Branch $b, array $data): Branch`, `delete(Branch $b): void`
    - _Requirements: 4.1, 4.2, 4.3_

- [ ] 10. Service Layer — Dashboard dan Map
  - [~] 10.1 Buat `app/Services/OnlineStatusService.php`
    - Constructor menerima `int $thresholdMinutes` dari `config('umroh.online_threshold_minutes')`
    - Method `resolve(User $user): string` — returns 'online' atau 'offline'
    - Method `summarize(Collection $users): array` — returns `['online' => int, 'offline' => int]`
    - _Requirements: 19.1, 19.2, 19.3_
  - [~] 10.2 Buat `app/Services/DashboardService.php`
    - Method `getStats(User $actor): array` — hitung statistik sesuai role actor (national untuk super_admin, branch-scoped untuk admin, group-scoped untuk tour_leader/muthawwif)
    - Gunakan `OnlineStatusService` untuk hitung online/offline
    - _Requirements: 7.1–7.9, 8.1–8.8_
  - [~] 10.3 Buat `app/Services/MapService.php`
    - Method `getLocations(User $actor): Collection` — ambil latest location per user, scope berdasarkan role
    - Method `getHotels(User $actor): Collection`
    - Method `getMuthawwifPositions(User $jamaah): Collection` — untuk Flutter App
    - _Requirements: 9.1, 10.1, 11.2, 12.1_

- [ ] 11. Service Layer — Location, SOS, History
  - [~] 11.1 Buat `app/Services/LocationService.php`
    - Method `store(User $user, array $payload): Location` — simpan GPS payload via LocationRepository, dispatch `LocationStoredEvent`
    - _Requirements: 13.1, 13.8_
  - [~] 11.2 Buat `app/Services/SosService.php`
    - Method `store(User $user, array $payload): Location` — simpan dengan `is_sos = true` via SosRepository, dispatch `SosTriggeredEvent`
    - _Requirements: 14.1, 14.2_
  - [~] 11.3 Buat `app/Services/SosResolverService.php`
    - Method `resolve(User $resolver, int $sosId): Location` — cek idempotency (jika sudah resolved → throw ValidationException), set `resolved_at` dan `resolved_by`, dispatch `SosResolvedEvent`
    - _Requirements: 29.1, 29.6_
  - [~] 11.4 Buat `app/Services/HistoryService.php`
    - Method `getHistory(User $actor, int $targetUserId, ?Carbon $start, ?Carbon $end): Collection`
    - Verifikasi scope access (Policy check), query via LocationRepository, urutkan ascending by `created_at`
    - _Requirements: 17.1, 17.2_
  - [~] 11.5 Buat `app/Services/NotificationService.php`
    - Method `notifySosRecipients(Location $sos): void` — resolve daftar penerima (muthawwif, tour_leader, admin, super_admin), dispatch job notifikasi masing-masing
    - _Requirements: 14.5, 14.6, 14.7, 14.8, 12.2_

- [ ] 12. Service Layer — Operational (Departure, Group, Hotel, Geofence)
  - [~] 12.1 Buat `app/Services/DepartureService.php`
    - CRUD methods dengan Policy authorization dan validasi `return_date > departure_date`
    - _Requirements: 26.1, 26.2_
  - [~] 12.2 Buat `app/Services/GroupService.php`
    - CRUD methods dengan Policy authorization
    - Method `assignLeader(Group $g, int $userId): void` — validasi branch_id sama dengan group
    - Method `assignMuthawwif(Group $g, int $userId): void` — validasi branch_id sama
    - Method `addMember(Group $g, int $userId): void` — validasi role jamaah dan branch_id sama
    - Method `removeMember(Group $g, int $userId): void`
    - _Requirements: 27.1–27.9_
  - [~] 12.3 Buat `app/Services/HotelService.php`
    - CRUD methods dengan Policy authorization
    - _Requirements: 28.1–28.5_
  - [~] 12.4 Buat `app/Services/GeofenceService.php`
    - CRUD methods dengan Policy authorization
    - Method `checkViolations(User $jamaah, float $lat, float $lng): array`
    - _Requirements: 20.1, 20.2, 20.3_

- [ ] 13. Events, Listeners, dan Jobs (Broadcasting & Queue)
  - [~] 13.1 Buat Event classes
    - `app/Events/LocationStoredEvent.php` — payload: Location model
    - `app/Events/SosTriggeredEvent.php` — payload: Location model (is_sos=true)
    - `app/Events/SosResolvedEvent.php` — payload: Location model (resolved)
    - `app/Events/GeofenceBreachedEvent.php` — payload: User, Geofence, lat, lng
    - _Requirements: 13.8, 14.3, 29.4_
  - [~] 13.2 Buat Job classes untuk Location broadcasting dan Geofence check
    - `app/Jobs/BroadcastLocationJob.php` — implements `ShouldQueue`, broadcast ke channel `branch.{branch_id}`
    - `app/Jobs/CheckGeofenceJob.php` — implements `ShouldQueue`, panggil `GeofenceService::checkViolations()`, dispatch `GeofenceBreachedEvent` jika ada pelanggaran
    - _Requirements: 13.8, 20.3_
  - [~] 13.3 Buat Job classes untuk SOS notifications
    - `app/Jobs/BroadcastSosJob.php` — broadcast SOS event ke `branch.{branch_id}` dan `sos.global`
    - `app/Jobs/NotifyMuthawwifJob.php` — kirim notifikasi ke muthawwif group jamaah
    - `app/Jobs/NotifyTourLeaderJob.php` — kirim notifikasi ke tour leader group jamaah
    - `app/Jobs/NotifyAdminJob.php` — kirim notifikasi ke admin cabang jamaah
    - `app/Jobs/NotifySuperAdminJob.php` — kirim notifikasi ke semua super_admin
    - _Requirements: 14.3, 14.5, 14.6, 14.7, 14.8_
  - [~] 13.4 Buat Job classes untuk SOS resolution dan Geofence breach
    - `app/Jobs/BroadcastSosResolutionJob.php` — broadcast resolved event ke channels yang relevan
    - `app/Jobs/NotifyGeofenceViolationJob.php` — kirim notifikasi pelanggaran geofence ke muthawwif/tour_leader
    - _Requirements: 29.4, 20.3_
  - [~] 13.5 Buat Listener classes dan daftarkan di `app/Providers/EventServiceProvider.php`
    - `LocationStoredListener` → dispatch `BroadcastLocationJob` dan `CheckGeofenceJob`
    - `SosTriggeredListener` → dispatch semua SOS notification jobs
    - `SosResolvedListener` → dispatch `BroadcastSosResolutionJob`
    - `GeofenceBreachedListener` → dispatch `NotifyGeofenceViolationJob`
    - Atau gunakan `$listen` array di `EventServiceProvider`
    - _Requirements: 13.8, 14.3, 14.5_

- [ ] 14. Broadcasting Channels
  - [~] 14.1 Perbarui `routes/channels.php`
    - Channel `branch.{branchId}` (Private): izinkan `super_admin` atau admin dengan `branch_id` matching
    - Channel `group.{groupId}` (Private): izinkan user yang merupakan `tour_leader_id` atau `muthawwif_id` dari group
    - Channel `sos.global` (Private): izinkan `super_admin` only
    - Channel `user.{userId}` (Private): izinkan user dengan `id` matching
    - _Requirements: 14.3, 13.8_

- [ ] 15. Controller Layer — Auth dan User/Branch
  - [~] 15.1 Buat `app/Http/Controllers/AuthController.php` (ganti / buat baru)
    - Method `login(LoginRequest $request): JsonResponse` — panggil `AuthService::login()`
    - Method `logout(Request $request): JsonResponse` — panggil `AuthService::logout()`
    - Method `me(Request $request): JsonResponse` — return user data dengan relasi branch
    - _Requirements: 1.1, 1.2, 1.3, 1.5_
  - [~] 15.2 Perbarui `app/Http/Controllers/UserController.php`
    - Refactor untuk gunakan `UserService` dan `UserPolicy` via `$this->authorize()`
    - Tambahkan methods: `show()`, `update()`, `destroy()`
    - Gunakan `StoreUserRequest` dan `UpdateUserRequest`
    - Return JSON response standar untuk API calls
    - _Requirements: 5.1, 5.2, 5.3, 6.1, 6.2_
  - [~] 15.3 Buat `app/Http/Controllers/BranchController.php`
    - Full CRUD: `index()`, `store()`, `show()`, `update()`, `destroy()`
    - Gunakan `BranchService` dan `BranchPolicy`
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 16. Controller Layer — Dashboard, Map, Location, SOS
  - [~] 16.1 Perbarui `app/Http/Controllers/DashboardController.php`
    - Refactor untuk gunakan `DashboardService` (role-aware statistics)
    - Return stats yang sesuai role: super_admin stats, admin stats, tour_leader stats, muthawwif stats
    - _Requirements: 7.1–7.9, 8.1–8.8_
  - [~] 16.2 Perbarui `app/Http/Controllers/MapController.php`
    - Refactor `getJamaahLocations()` → gunakan `MapService`, tambahkan `getHotels()` dan `getMuthawwifPositions()`
    - _Requirements: 9.1, 9.2, 10.1_
  - [~] 16.3 Buat `app/Http/Controllers/LocationController.php`
    - Method `store(StoreLocationRequest $request): JsonResponse` — panggil `LocationService::store()`
    - _Requirements: 13.1, 13.5_
  - [~] 16.4 Buat `app/Http/Controllers/SosController.php`
    - Method `store(StoreSosRequest $request): JsonResponse` — panggil `SosService::store()`
    - Method `resolve(ResolveSosRequest $request, int $id): JsonResponse` — panggil `SosResolverService::resolve()`
    - Method `history(Request $request): JsonResponse` — panggil via `LocationRepository`, scope by role
    - _Requirements: 14.1, 14.2, 29.1, 29.6_
  - [~] 16.5 Buat `app/Http/Controllers/HistoryController.php`
    - Method `index(HistoryRequest $request, int $userId): JsonResponse` — panggil `HistoryService::getHistory()`
    - _Requirements: 17.1, 17.2_

- [ ] 17. Controller Layer — Operational (Departure, Group, Hotel, Geofence)
  - [~] 17.1 Buat `app/Http/Controllers/DepartureController.php`
    - Full CRUD via `DepartureService` dan `DeparturePolicy`
    - _Requirements: 26.1, 26.2, 26.3_
  - [~] 17.2 Buat `app/Http/Controllers/GroupController.php`
    - CRUD methods via `GroupService` dan `GroupPolicy`
    - Extra methods: `assignLeader()`, `assignMuthawwif()`, `addMember()`, `removeMember()`
    - _Requirements: 27.1–27.9_
  - [~] 17.3 Buat `app/Http/Controllers/HotelController.php`
    - Full CRUD via `HotelService` dan `HotelPolicy`
    - _Requirements: 28.1–28.5_
  - [~] 17.4 Buat `app/Http/Controllers/GeofenceController.php`
    - Full CRUD via `GeofenceService` dan `GeofencePolicy`
    - _Requirements: 20.1, 20.4_

- [ ] 18. Routes — API dan Web
  - [~] 18.1 Perbarui `routes/api.php` — Auth dan GPS/SOS endpoints
    - `POST /api/auth/login` → AuthController@login (public)
    - `POST /api/auth/logout` → AuthController@logout (auth:sanctum)
    - `GET /api/auth/me` → AuthController@me (auth:sanctum)
    - `POST /api/location` → LocationController@store (auth:sanctum, role:jamaah)
    - `POST /api/sos` → SosController@store (auth:sanctum, role:jamaah)
    - `POST /api/sos/{id}/resolve` → SosController@resolve (auth:sanctum, role:super_admin,admin,tour_leader,muthawwif)
    - `GET /api/sos/history` → SosController@history (auth:sanctum)
    - _Requirements: 1.1, 13.1, 14.1, 29.1_
  - [~] 18.2 Perbarui `routes/api.php` — Management dan Dashboard endpoints
    - `GET /api/dashboard` → DashboardController@index (auth:sanctum)
    - `apiResource /api/users` → UserController (auth:sanctum, role:super_admin,admin)
    - `apiResource /api/branches` → BranchController (auth:sanctum, role:super_admin,admin)
    - `GET /api/map/locations` → MapController@getJamaahLocations (auth:sanctum, branch.scope)
    - `GET /api/map/hotels` → MapController@getHotels (auth:sanctum)
    - `GET /api/map/muthawwif` → MapController@getMuthawwifPositions (auth:sanctum)
    - `GET /api/history/{userId}` → HistoryController@index (auth:sanctum)
    - _Requirements: 9.1, 10.1_
  - [~] 18.3 Perbarui `routes/api.php` — Operational endpoints
    - `apiResource /api/departures` (auth:sanctum, role:super_admin,admin)
    - `apiResource /api/groups` (auth:sanctum, role:super_admin,admin)
    - Extra group routes: `assign-leader`, `assign-muthawwif`, `members` (POST/DELETE)
    - `apiResource /api/hotels` (auth:sanctum, role:super_admin,admin)
    - `apiResource /api/geofences` (auth:sanctum, role:super_admin,admin)
    - _Requirements: 26.1, 27.1, 28.1, 20.1_
  - [~] 18.4 Perbarui `routes/web.php` — Web panel routes dengan auth middleware
    - `GET /login` → AuthController@showLogin, `POST /login` → AuthController@loginWeb
    - `POST /logout` → AuthController@logoutWeb
    - `GET /` (dashboard), `GET /map`, `GET /users`, `GET /branches`, `GET /departures`, `GET /groups` → dengan middleware `auth` dan `role` yang sesuai
    - _Requirements: 1.1, 2.3_

- [ ] 19. Blade Views — Layout dan Auth
  - [~] 19.1 Buat `resources/views/layouts/app.blade.php`
    - Layout utama dengan Bootstrap 5 CDN
    - Sidebar navigasi dengan link per role (sembunyikan menu yang tidak relevan berdasarkan `auth()->user()->role`)
    - Navbar atas dengan nama user dan tombol logout
    - `@yield('content')` dan `@stack('scripts')`
    - _Requirements: 2.3_
  - [~] 19.2 Buat `resources/views/auth/login.blade.php`
    - Form login (email, password) dengan Bootstrap 5
    - Tampilkan pesan error jika kredensial tidak valid
    - _Requirements: 1.1, 1.2_
  - [~] 19.3 Perbarui `resources/views/dashboard.blade.php`
    - Extend layout, tampilkan statistik cards: total cabang, jamaah, muthawwif, tour leader, admin
    - Tampilkan counter online/offline jamaah dan SOS aktif
    - Grafik batang jamaah per cabang (menggunakan Chart.js dari CDN)
    - _Requirements: 7.1–7.9, 8.1–8.8_

- [ ] 20. Blade Views — Map, Users, Branches
  - [~] 20.1 Perbarui `resources/views/map.blade.php`
    - Extend layout, integrasikan Leaflet.js (CDN)
    - Inisialisasi peta dengan tile layer OpenStreetMap
    - Fetch data `/api/map/locations` via AJAX, render marker per jamaah
    - Marker merah berkedip untuk `is_sos = true`
    - Popup detail per marker: name, status, battery, speed, accuracy, last_seen
    - Filter panel: by branch, by group, by status (online/offline/SOS)
    - Real-time update via Laravel Echo + WebSocket (subscribe ke channel sesuai role)
    - Tampilkan marker Hotel
    - _Requirements: 9.3, 9.4, 9.5, 9.6, 9.7, 9.8, 9.11, 10.3, 10.4_
  - [~] 20.2 Perbarui `resources/views/users/index.blade.php`
    - Extend layout, tabel daftar user dengan kolom: name, email, role, branch, phone
    - Modal Bootstrap untuk form tambah user (role-aware: admin tidak tampilkan option super_admin/admin)
    - Modal edit user, tombol hapus dengan konfirmasi
    - _Requirements: 5.1, 6.1_
  - [~] 20.3 Buat `resources/views/branches/index.blade.php`
    - Tabel daftar cabang, modal tambah/edit, tombol hapus
    - Hanya tampil untuk super_admin
    - _Requirements: 4.1_

- [ ] 21. Blade Views — Departures, Groups, SOS History
  - [~] 21.1 Buat `resources/views/departures/index.blade.php`
    - Tabel keberangkatan dengan kolom: program_name, branch, departure_date, return_date
    - Form tambah/edit dengan date picker
    - _Requirements: 26.1_
  - [~] 21.2 Buat `resources/views/groups/index.blade.php`
    - Tabel group dengan kolom: group_name, departure, tour_leader, muthawwif, jumlah jamaah
    - Form assignment tour_leader, muthawwif, dan anggota jamaah
    - _Requirements: 27.1, 27.3_
  - [~] 21.3 Buat `resources/views/sos/history.blade.php`
    - Tabel histori SOS dengan kolom: jamaah name, branch, waktu SOS, posisi, status (resolved/active), resolver, waktu resolved
    - Filter by status (active/resolved)
    - _Requirements: 29.1, 29.5_

- [ ] 22. Database Factories dan Seeder
  - [~] 22.1 Buat/perbarui `database/factories/UserFactory.php`
    - Tambahkan states: `role(string $role)`, `withBranch()`, `branch(int $branchId)`
    - Tambahkan `withGroup()` state untuk digunakan di property tests
    - _Requirements: Design Testing Strategy_
  - [~] 22.2 Buat `database/factories/LocationFactory.php`
    - Default: valid GPS payload dengan random lat/lng dalam range
    - States: `sos()` (is_sos=true), `resolved(int $resolverId)`
    - _Requirements: 13.1_
  - [~] 22.3 Buat `database/factories/BranchFactory.php`, `DepartureFactory.php`, `GroupFactory.php`
    - Masing-masing dengan data yang realistis untuk testing
    - _Requirements: Design Testing Strategy_
  - [~] 22.4 Buat `database/seeders/DatabaseSeeder.php` (ganti yang ada)
    - Buat 1 user `super_admin` (email: `superadmin@umroh.test`, password: `password`)
    - Buat 3 Branch sample: Surabaya, Jakarta, Bandung
    - Per cabang: 1 admin, 2 tour_leader, 3 muthawwif, 10 jamaah
    - Buat 1 Departure per cabang dengan 2 Group masing-masing
    - Assign tour_leader dan muthawwif ke Group
    - Assign jamaah ke Group (rata)
    - Buat sample Hotel per Departure
    - _Requirements: 4.1, 5.1_

- [~] 23. Checkpoint — Verifikasi integrasi awal
  - Jalankan `php artisan migrate:fresh --seed` dan pastikan berhasil tanpa error
  - Test login endpoint via `curl` atau Postman: `POST /api/auth/login`
  - Pastikan response mengandung `token`, `role`, dan `branch_id`
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum melanjutkan.

- [ ] 24. Property-Based Tests (eris/eris)
  - [~] 24.1 Buat `tests/Property/LocationValidationPropertyTest.php`
    - **Property 7: GPS Payload Validation — koordinat dalam range diterima, di luar ditolak**
    - **Property 19: Input Validation — request invalid selalu menghasilkan 422**
    - **Validates: Requirements 13.2, 13.3, 13.6, 28.3, 16.1–16.5**
    - Gunakan `Generator\float(-90, 90)` dan `Generator\float(-180, 180)` untuk valid range
    - Test out-of-range dengan `Generator\float(-1000, -90.01)` dan `Generator\float(90.01, 1000)`
  - [ ]* 24.2 Buat `tests/Property/OnlineStatusPropertyTest.php`
    - **Property 11: Online Status Correctness — threshold menentukan status secara konsisten**
    - **Validates: Requirements 19.1, 19.2, 19.3**
    - Gunakan `Generator\choose(1, 4)` untuk recent (< threshold) dan `Generator\choose(6, 120)` untuk stale
  - [ ]* 24.3 Buat `tests/Property/GeofenceContainmentPropertyTest.php`
    - **Property 12: Geofence Containment Consistency — Haversine check konsisten dengan radius**
    - **Validates: Requirements 20.2**
    - Test bahwa center point selalu `containsPoint` = true
    - Test titik di luar radius selalu `containsPoint` = false
  - [ ]* 24.4 Buat `tests/Property/BranchScopingPropertyTest.php`
    - **Property 4: Branch Scoping — admin hanya melihat data cabangnya sendiri**
    - **Property 5: Branch Auto-Assignment — admin tidak bisa override branch_id**
    - **Validates: Requirements 3.1, 3.4, 3.5, 6.1**
    - Generate random branch_id berbeda untuk verifikasi tidak muncul di response
  - [ ]* 24.5 Buat `tests/Property/RoleAccessPropertyTest.php`
    - **Property 3: Role-Based Access Enforcement — restricted roles mendapat 403**
    - **Property 6: Admin tidak bisa buat/naikkan role ke super_admin atau admin**
    - **Validates: Requirements 2.2, 2.3, 2.4, 6.2, 6.4**
    - Gunakan `Generator\elements('tour_leader', 'muthawwif', 'jamaah')` untuk test 403
  - [ ]* 24.6 Buat `tests/Property/DashboardCountsPropertyTest.php`
    - **Property 13: Dashboard Counts Accuracy — statistik dashboard akurat**
    - **Property 14: Branch-Scoped Dashboard Accuracy — statistik admin scoped**
    - **Validates: Requirements 7.1–7.5, 8.1–8.3, 3.2**
    - Generate N user jamaah, verifikasi count = N di response
  - [ ]* 24.7 Buat `tests/Property/SosIdempotencyPropertyTest.php`
    - **Property 10: SOS Resolution Idempotency — SOS resolved tidak bisa diresolve lagi**
    - **Validates: Requirements 29.6**
    - Pastikan second resolve request return 422 dan data tidak berubah
  - [ ]* 24.8 Buat `tests/Property/HistoryOrderingPropertyTest.php`
    - **Property 15: History Ordering — histori GPS selalu ascending by created_at**
    - **Validates: Requirements 17.1, 17.2**
    - Generate N random location records, verifikasi urutan ascending
  - [ ]* 24.9 Buat `tests/Property/InputValidationPropertyTest.php` (lanjutan Property 19)
    - **Property 19: Input Validation — field invalid selalu 422 dengan pesan deskriptif**
    - **Validates: Requirements 5.4, 5.5, 5.6, 5.7, 16.1–16.5**
    - Test dengan email tidak valid, password terlalu pendek, role tidak valid
  - [ ]* 24.10 Buat `tests/Property/TokenRevocationPropertyTest.php`
    - **Property 2: Token Revocation — token tidak dapat digunakan setelah logout**
    - **Validates: Requirements 1.3, 1.4**
    - Verifikasi setiap request setelah logout mendapat 401
  - [ ]* 24.11 Buat `tests/Property/TokenLoginPropertyTest.php`
    - **Property 1: Token Login Round-Trip — role dan branch_id selalu tersedia**
    - **Validates: Requirements 1.1, 1.5**
    - Generate user dengan berbagai kombinasi role, verifikasi response selalu berisi role dan branch_id
  - [ ]* 24.12 Buat test untuk Properties 8, 9, 16, 17, 18 di file yang sesuai
    - **Property 8** (GPS persistence): `tests/Property/GpsPersistencePropertyTest.php`
      - **Validates: Requirements 13.1, 13.7**
    - **Property 9** (SOS creation): bagian dari `tests/Property/SosIdempotencyPropertyTest.php`
      - **Validates: Requirements 14.1**
    - **Property 16** (Scope enforcement): tambahkan ke `tests/Property/RoleAccessPropertyTest.php`
      - **Validates: Requirements 11.3, 12.3, 24.4, 25.5**
    - **Property 17** (Departure date validation): `tests/Property/DepartureDatePropertyTest.php`
      - **Validates: Requirements 26.2**
    - **Property 18** (Cross-branch assignment): `tests/Property/CrossBranchAssignmentPropertyTest.php`
      - **Validates: Requirements 27.7, 27.8, 27.9**

- [ ] 25. Feature Tests dan Unit Tests
  - [~] 25.1 Buat `tests/Feature/Auth/LoginTest.php` dan `LogoutTest.php`
    - Test: login valid → 200 + token + role + branch_id
    - Test: login invalid → 401 + pesan error
    - Test: logout → token terhapus → subsequent request → 401
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_
  - [ ]* 25.2 Buat `tests/Feature/Users/SuperAdminUserCrudTest.php`
    - Test full CRUD oleh super_admin: create, read, update, delete
    - Test unique email validation, role validation, branch_id validation
    - _Requirements: 5.1–5.8_
  - [ ]* 25.3 Buat `tests/Feature/Users/AdminCabangUserCrudTest.php`
    - Test admin buat user → branch_id otomatis sesuai admin
    - Test admin tidak bisa buat role super_admin/admin → 403
    - Test admin tidak bisa akses user cabang lain → 403
    - _Requirements: 6.1, 6.2, 6.4, 3.4_
  - [ ]* 25.4 Buat `tests/Feature/Location/GpsSubmissionTest.php`
    - Test valid GPS → 201 + record tersimpan
    - Test invalid lat/lng → 422
    - Test tanpa token → 401
    - _Requirements: 13.1–13.7_
  - [ ]* 25.5 Buat `tests/Feature/Location/SosSubmissionTest.php`
    - Test SOS tersimpan dengan `is_sos = true`
    - Test `user_id` berasal dari token bukan request body
    - _Requirements: 14.1, 14.2_
  - [ ]* 25.6 Buat `tests/Feature/SosResolution/SosResolverTest.php`
    - Test resolve sukses → `resolved_at` dan `resolved_by` terisi
    - Test resolve SOS yang sudah resolved → 422 (idempotency)
    - _Requirements: 29.1, 29.6_
  - [ ]* 25.7 Buat `tests/Feature/History/LocationHistoryTest.php`
    - Test histori sorted ascending by created_at
    - Test filter by date range
    - Test tour_leader tidak bisa akses history jamaah di luar grupnya → 403
    - _Requirements: 17.1, 17.2, 11.3_
  - [ ]* 25.8 Buat `tests/Feature/Groups/GroupManagementTest.php`
    - Test CRUD group oleh admin
    - Test cross-branch assignment ditolak → 422
    - _Requirements: 27.1, 27.7, 27.8, 27.9_
  - [ ]* 25.9 Buat `tests/Unit/Services/OnlineStatusServiceTest.php`
    - Test user dengan location recent → 'online'
    - Test user tanpa location → 'offline'
    - Test `summarize()` menghitung benar
    - _Requirements: 19.1, 19.2, 19.3_
  - [ ]* 25.10 Buat `tests/Unit/Models/GeofenceContainsPointTest.php`
    - Test center point selalu inside
    - Test titik tepat di batas radius (edge case)
    - Test titik jauh di luar radius
    - _Requirements: 20.2_

- [~] 26. Checkpoint Final — Semua test harus hijau
  - Jalankan `php artisan test` dan pastikan tidak ada test yang gagal
  - Jalankan `php artisan migrate:fresh --seed` untuk memastikan seeder berjalan bersih
  - Tanyakan kepada pengguna jika ada pertanyaan sebelum deploy.

## Notes

- Task yang diakhiri `*` bersifat opsional dan dapat dilewati untuk MVP lebih cepat
- Setiap task menyertakan referensi requirements untuk keterlacakan
- Property-based tests menggunakan `eris/eris` dengan minimal 100 iterasi per property
- Unit tests melengkapi property tests untuk kasus spesifik dan edge case
- Semua API response menggunakan format standar: `{success, data, message}`
- Middleware `BranchScope` memastikan branch scoping konsisten tanpa perlu filter manual di setiap controller
- Semua side effects (broadcast, notifikasi) berjalan asinkron melalui Queue untuk menjaga response time < 200ms pada GPS endpoint


## Task Dependency Graph

```json
{
  "waves": [
    {
      "id": 0,
      "tasks": ["1.1", "1.2", "1.3", "1.4", "1.5", "1.6", "1.7"]
    },
    {
      "id": 1,
      "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6", "2.7"]
    },
    {
      "id": 2,
      "tasks": ["4.1", "4.2", "4.3"]
    },
    {
      "id": 3,
      "tasks": ["5.1", "5.2", "5.3"]
    },
    {
      "id": 4,
      "tasks": [
        "6.1", "6.2", "6.3", "6.4", "6.5", "6.6",
        "6.7", "6.8", "6.9", "6.10", "6.11"
      ]
    },
    {
      "id": 5,
      "tasks": [
        "7.1", "7.2", "7.3", "7.4", "7.5", "7.6", "7.7", "7.8"
      ]
    },
    {
      "id": 6,
      "tasks": [
        "8.1", "8.2", "8.3", "8.4", "8.5", "8.6", "8.7", "8.8", "8.9"
      ]
    },
    {
      "id": 7,
      "tasks": ["9.1", "9.2", "9.3"]
    },
    {
      "id": 8,
      "tasks": ["10.1", "10.2", "10.3"]
    },
    {
      "id": 9,
      "tasks": ["11.1", "11.2", "11.3", "11.4", "11.5"]
    },
    {
      "id": 10,
      "tasks": ["12.1", "12.2", "12.3", "12.4"]
    },
    {
      "id": 11,
      "tasks": ["13.1", "13.2", "13.3", "13.4", "13.5"]
    },
    {
      "id": 12,
      "tasks": ["14.1"]
    },
    {
      "id": 13,
      "tasks": ["15.1", "15.2", "15.3"]
    },
    {
      "id": 14,
      "tasks": ["16.1", "16.2", "16.3", "16.4", "16.5"]
    },
    {
      "id": 15,
      "tasks": ["17.1", "17.2", "17.3", "17.4"]
    },
    {
      "id": 16,
      "tasks": ["18.1", "18.2", "18.3", "18.4"]
    },
    {
      "id": 17,
      "tasks": ["19.1", "19.2", "19.3"]
    },
    {
      "id": 18,
      "tasks": ["20.1", "20.2", "20.3"]
    },
    {
      "id": 19,
      "tasks": ["21.1", "21.2", "21.3"]
    },
    {
      "id": 20,
      "tasks": ["22.1", "22.2", "22.3", "22.4"]
    },
    {
      "id": 21,
      "tasks": [
        "24.1", "24.2", "24.3", "24.4", "24.5",
        "24.6", "24.7", "24.8", "24.9", "24.10", "24.11", "24.12"
      ]
    },
    {
      "id": 22,
      "tasks": [
        "25.1", "25.2", "25.3", "25.4", "25.5",
        "25.6", "25.7", "25.8", "25.9", "25.10"
      ]
    }
  ]
}
```
