# Task List — Tái Cấu Trúc Source Code

## Phase 1: Tách God Controllers
- [x] Tách `Admin/CourseController.php` → 5 controllers
  - [x] `CourseController.php` (CRUD only)
  - [x] `CourseEnrollmentController.php`
  - [x] `CourseLessonController.php`
  - [x] `CourseActivityController.php`
  - [x] `CourseQuestionController.php`
- [x] Tách `PracticeController.php` → 3 controllers
  - [x] `PracticeController.php` (hub + static exam)
  - [x] `AdaptiveTestController.php`
  - [x] `QuizApiController.php`
- [x] Cập nhật routes cho controllers mới

## Phase 2: Phân nhóm Services
- [x] Tạo cấu trúc thư mục `AI/`, `Assessment/`, `LMS/`, `Storage/`
- [x] Di chuyển services vào các folder tương ứng
- [x] Cập nhật tất cả namespace references

## Phase 3: Tách Routes
- [x] Tạo `routes/student.php`
- [x] Tạo `routes/admin.php`
- [x] Tạo `routes/teacher.php`
- [x] Cập nhật `routes/web.php`

## Phase 4: Form Request Validation
- [x] `StoreCourseRequest.php`
- [x] `UpdateCourseRequest.php`
- [x] `StoreActivityRequest.php`
- [x] `StoreLessonRequest.php`
- [x] `StoreQuestionRequest.php`
- [x] Wire Form Requests vào các Controllers

## Phase 5: Verification
- [x] Chạy `php artisan route:list` — không mất route (100% routes mapped)
- [x] Chạy `php artisan test` — tất cả 97/97 tests pass
- [x] Verify imports/namespaces không lỗi
