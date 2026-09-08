<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\AssessmentSubmission;
use App\Models\UserActivityLog;
use App\Models\UserBadge;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of users with advanced filters.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $roleFilter = $request->get('role');
        $statusFilter = $request->get('status');
        $levelFilter = $request->get('level');

        $query = User::with(['roles', 'badges']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($levelFilter) {
            $query->where('current_level', $levelFilter);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact(
            'users',
            'search',
            'roleFilter',
            'statusFilter',
            'levelFilter',
            'roles'
        ));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return view('admin.users.form', [
            'user' => null,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username|alpha_dash',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'status' => 'required|in:active,trial_expired,blocked',
            'current_level' => 'required|in:A1,A2,B1,B2,C1,C2',
            'coins' => 'required|integer|min:0',
            'trial_days' => 'nullable|integer|min:0',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        $trialEndsAt = $request->trial_days ? now()->addDays((int)$request->trial_days) : null;

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarRecord = app(FileStorageService::class)->store($request->file('avatar'), 'avatars');
            $avatarPath = $avatarRecord->storage_path;
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
            'role' => $request->role,
            'status' => $request->status,
            'current_level' => $request->current_level,
            'coins' => $request->coins,
            'trial_ends_at' => $trialEndsAt,
            'email_verified_at' => now(),
        ]);


        // Sync Spatie role
        $user->syncRoles([$request->role]);

        // Sync direct permissions if provided
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Đã tạo tài khoản {$user->name} ({$user->username}) thành công!");
    }

    /**
     * Display the specified user profile and learning diagnostics.
     */
    public function show($id)
    {
        $user = User::with(['skills', 'badges', 'certificates', 'progress.lesson.course'])
            ->findOrFail($id);

        $submissions = AssessmentSubmission::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $activityLogs = UserActivityLog::with('activity.lesson')
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return view('admin.users.show', compact('user', 'submissions', 'activityLogs'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);
        $roles = Role::all();
        $permissions = Permission::all();

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'status' => 'required|in:active,trial_expired,blocked',
            'current_level' => 'required|in:A1,A2,B1,B2,C1,C2',
            'coins' => 'required|integer|min:0',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
            'current_level' => $request->current_level,
            'coins' => $request->coins,
        ];

        if ($request->hasFile('avatar')) {
            $avatarRecord = app(FileStorageService::class)->store($request->file('avatar'), 'avatars');
            $data['avatar'] = $avatarRecord->storage_path;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }


        if ($request->filled('extend_trial_days')) {
            $data['trial_ends_at'] = now()->addDays((int)$request->extend_trial_days);
        }

        $user->update($data);

        // Sync Spatie role
        $user->syncRoles([$request->role]);

        // Sync direct permissions if provided
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Đã cập nhật thông tin người dùng {$user->name} thành công.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Không thể tự xóa tài khoản quản trị viên đang đăng nhập.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Đã xóa tài khoản {$userName} thành công.");
    }

    /**
     * Quick action: Update role.
     */
    public function updateRole(Request $request, $userId)
    {
        $request->validate(['role' => 'required|string']);

        $user = User::findOrFail($userId);
        $user->update(['role' => $request->role]);
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')
            ->with('success', "Đã đổi vai trò của {$user->name} thành {$request->role}.");
    }

    /**
     * Quick action: Toggle active / blocked status.
     */
    public function toggleStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->status === 'blocked') {
            $user->update(['status' => 'active']);
            $message = "Đã mở khóa tài khoản {$user->name}.";
        } else {
            $user->update(['status' => 'blocked']);
            $message = "Đã khóa tài khoản {$user->name}.";
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Quick action: Add / Subtract coins.
     */
    public function updateCoins(Request $request, $userId)
    {
        $request->validate([
            'action' => 'required|in:add,subtract',
            'amount' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($userId);

        if ($request->action === 'add') {
            $user->increment('coins', $request->amount);
            $message = "Đã cộng {$request->amount} coins cho {$user->name}.";
        } else {
            $newCoins = max(0, $user->coins - $request->amount);
            $user->update(['coins' => $newCoins]);
            $message = "Đã trừ {$request->amount} coins của {$user->name}.";
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Quick action: Activate trial extension.
     */
    public function activateUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'status' => 'active',
            'trial_ends_at' => now()->addDays(30),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Đã kích hoạt tài khoản {$user->name} thêm 30 ngày.");
    }
}
