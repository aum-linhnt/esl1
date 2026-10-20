# AI Tutor demo seeders

Các seeder demo mới được gom vào thư mục này, namespace `Database\Seeders\AiTutorDemo`. Không tự chạy qua `DatabaseSeeder`; chỉ cho môi trường local/testing.

Chạy bằng tên class đầy đủ:

```bash
# Khóa IELTS 6.5 mẫu có audio/video/PDF
php artisan db:seed --class='Database\Seeders\AiTutorDemo\AiTutorIeltsMediaDemoSeeder'

# Các nhóm cấp độ
php artisan db:seed --class='Database\Seeders\AiTutorDemo\AiTutorIeltsLevelsDemoSeeder'
php artisan db:seed --class='Database\Seeders\AiTutorDemo\AiTutorToeicLevelsDemoSeeder'
php artisan db:seed --class='Database\Seeders\AiTutorDemo\AiTutorToeicSwLevelsDemoSeeder'
php artisan db:seed --class='Database\Seeders\AiTutorDemo\AiTutorCefrLevelsDemoSeeder'
```

Seeder nền dùng chung là `AiTutorManagementDemoSeeder`. Nội dung khóa mẫu IELTS nằm trong `data/ielts-65-showcase.php`; script dựng media `scripts/build-ielts-demo-media.py` đọc dữ liệu từ đây. Media được phục vụ ở `public/demo/ielts-65`.

Việc chuyển thư mục không thay đổi slug, UUID hoặc dữ liệu đã nạp. Không cần seed lại database chỉ để áp dụng cách tổ chức thư mục.
