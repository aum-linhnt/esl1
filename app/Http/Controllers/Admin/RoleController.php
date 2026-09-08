<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Enrollment;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with permission tags and user counts.
     */
    public function index()
    {
        $allRoles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all();

        // Separate System Roles and Course Context Roles
        $courseRoleKeys = ['course_manager', 'course_teacher', 'course_assistant', 'course_student'];
        
        $systemRoles = $allRoles->filter(fn($r) => !in_array($r->name, $courseRoleKeys));
        $courseRoles = $allRoles->filter(fn($r) => in_array($r->name, $courseRoleKeys));

        // Count enrollments per course role
        $enrollmentCounts = Enrollment::selectRaw('course_role, count(*) as total')
            ->groupBy('course_role')
            ->pluck('total', 'course_role')
            ->toArray();

        // Metadata mapping for course roles
        $courseRoleMeta = [
            'course_manager' => [
                'name' => 'Quản trị khóa học (Course Manager)',
                'icon' => '👑',
                'badge' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                'desc' => 'Toàn quyền quản trị nội dung giáo trình, ghi danh và thiết lập trong khóa học',
                'count' => $enrollmentCounts[Enrollment::ROLE_MANAGER] ?? 0,
            ],
            'course_teacher' => [
                'name' => 'Giáo viên phụ trách (Teacher)',
                'icon' => '👨‍🏫',
                'badge' => 'bg-purple-500/15 text-purple-300 border-purple-500/30',
                'desc' => 'Toàn quyền soạn bài giảng, học liệu, chấm điểm bài thi & xem sổ điểm lớp',
                'count' => $enrollmentCounts[Enrollment::ROLE_TEACHER] ?? 0,
            ],
            'course_assistant' => [
                'name' => 'Trợ giảng (Teaching Assistant)',
                'icon' => '🧑‍💼',
                'badge' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                'desc' => 'Hỗ trợ giảng dạy, chấm bài tập, giám sát tiến độ và hỗ trợ học viên trong khóa',
                'count' => $enrollmentCounts[Enrollment::ROLE_ASSISTANT] ?? 0,
            ],
            'course_student' => [
                'name' => 'Học viên (Student)',
                'icon' => '🎓',
                'badge' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
                'desc' => 'Tham gia học tập, làm bài tập & kiểm tra, xem tiến độ cá nhân',
                'count' => $enrollmentCounts[Enrollment::ROLE_STUDENT] ?? 0,
            ],
        ];

        return view('admin.roles.index', compact('systemRoles', 'courseRoles', 'courseRoleMeta', 'permissions'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::all();
        $groupedPermissions = $this->groupPermissions($permissions);

        return view('admin.roles.form', [
            'role' => null,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|alpha_dash',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => strtolower($request->name),
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', "Đã tạo vai trò '{$role->name}' và gán quyền thành công!");
    }

    /**
     * Show the form for editing role permissions.
     */
    public function edit($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all();
        $groupedPermissions = $this->groupPermissions($permissions);

        return view('admin.roles.form', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
        ]);
    }

    /**
     * Update role permissions.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:50|alpha_dash|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $coreRoles = ['admin', 'teacher', 'student', 'course_manager', 'course_teacher', 'course_assistant', 'course_student'];

        if (!in_array($role->name, $coreRoles)) {
            $role->update(['name' => strtolower($request->name)]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', "Đã cập nhật bảng phân quyền cho vai trò '{$role->name}' thành công!");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $coreRoles = ['admin', 'teacher', 'student', 'course_manager', 'course_teacher', 'course_assistant', 'course_student'];

        // Protect core roles
        if (in_array($role->name, $coreRoles)) {
            return redirect()->route('admin.roles.index')
                ->with('error', "Không thể xóa các vai trò cốt lõi của hệ thống và khóa học.");
        }

        $roleName = $role->name;
        $role->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')
            ->with('success', "Đã xóa vai trò '{$roleName}' thành công.");
    }

    /**
     * Group permissions logically for easy checkbox UI display with Vietnamese labels.
     */
    protected function groupPermissions($permissions)
    {
        $permissionLabels = [
            // Course capabilities
            'view course content'           => 'Xem nội dung bài học & học liệu số',
            'submit course activities'      => 'Làm bài tập trắc nghiệm & nộp bài AI',
            'bypass lesson locks'           => 'Bỏ qua điều kiện khóa bài (Mở toàn bộ)',
            'grade course submissions'      => 'Chấm điểm bài tập, bài nói/viết học viên',
            'view course gradebook'         => 'Xem Sổ điểm toàn lớp & tiến độ học viên',
            'manage course curriculum'      => 'Soạn thảo & chỉnh sửa giáo trình bài học',
            'manage course enrollments'     => 'Ghi danh & phân vai trò học viên khóa học',
            'suspend course participants'   => 'Tạm đình chỉ (Suspend) & gia hạn học viên',

            // System course permissions
            'view courses'                  => 'Xem danh sách khóa học hệ thống',
            'create courses'                => 'Tạo khóa học đào tạo mới',
            'edit courses'                  => 'Chỉnh sửa thông tin khóa học',
            'delete courses'                => 'Xóa khóa học khỏi hệ thống',

            // Lesson permissions
            'view lessons'                  => 'Xem danh sách bài học',
            'create lessons'                => 'Thêm bài học mới',
            'edit lessons'                  => 'Chỉnh sửa bài học',
            'delete lessons'                => 'Xóa bài học',

            // Question bank permissions
            'view question bank'            => 'Xem ngân hàng câu hỏi',
            'create questions'              => 'Tạo câu hỏi luyện thi mới',
            'edit questions'                => 'Chỉnh sửa câu hỏi',
            'delete questions'              => 'Xóa câu hỏi ngân hàng',

            // Users & roles
            'manage users'                  => 'Quản lý tài khoản người dùng',
            'manage roles'                  => 'Quản lý vai trò & phân quyền RBAC',

            // Reports & utilities
            'view reports'                  => 'Xem báo cáo & phân tích số liệu',
            'access marketplace'            => 'Truy cập gói dịch vụ & tiện ích',
        ];

        $groups = [
            '📚 Đặc quyền Khóa học (Course Capabilities)' => [],
            '🌐 Quản trị Khóa học Hệ thống (Courses)' => [],
            '📖 Bài học & Hoạt động (Lessons)' => [],
            '❓ Ngân hàng Câu hỏi (Question Bank)' => [],
            '👥 Người dùng & Phân quyền (Users & Roles)' => [],
            '📊 Báo cáo & Tiện ích (Reports)' => [],
        ];

        foreach ($permissions as $p) {
            $p->label = $permissionLabels[$p->name] ?? $p->name;

            if (str_starts_with($p->name, 'view course content') || 
                str_starts_with($p->name, 'submit course') || 
                str_starts_with($p->name, 'bypass lesson') || 
                str_starts_with($p->name, 'grade course') || 
                str_starts_with($p->name, 'view course gradebook') || 
                str_starts_with($p->name, 'manage course') || 
                str_starts_with($p->name, 'suspend course')) {
                $groups['📚 Đặc quyền Khóa học (Course Capabilities)'][] = $p;
            } elseif (str_contains($p->name, 'course')) {
                $groups['🌐 Quản trị Khóa học Hệ thống (Courses)'][] = $p;
            } elseif (str_contains($p->name, 'lesson')) {
                $groups['📖 Bài học & Hoạt động (Lessons)'][] = $p;
            } elseif (str_contains($p->name, 'question')) {
                $groups['❓ Ngân hàng Câu hỏi (Question Bank)'][] = $p;
            } elseif (str_contains($p->name, 'user') || str_contains($p->name, 'role')) {
                $groups['👥 Người dùng & Phân quyền (Users & Roles)'][] = $p;
            } else {
                $groups['📊 Báo cáo & Tiện ích (Reports)'][] = $p;
            }
        }

        return $groups;
    }
}
