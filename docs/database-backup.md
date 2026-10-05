# Backup database hằng ngày

Chạy thủ công:

```bash
docker compose exec -T app php artisan db:backup
```

Command dùng connection mặc định từ cấu hình Laravel; có thể chọn connection bằng
`--connection=ten_connection`. Chỉ hỗ trợ MySQL, các bảng InnoDB và trigger. Nếu database
có view, routine/event hoặc engine khác, command báo lỗi để tránh tạo backup thiếu đối tượng.

Backup được lưu tại `storage/app/private/backups/*.sql.gz`, tên gồm database, thời gian và UUID.
File riêng tư có quyền `0600`; tên `.sql.gz` chỉ xuất hiện sau khi ghi nén hoàn tất. Nếu lỗi,
file dở dang được dọn và command trả exit code khác 0. Các bản backup cũ được giữ nguyên.
Backup dùng snapshot transaction chỉ đọc, đọc dữ liệu theo stream và chuẩn hóa timestamp về UTC.
Không chạy thay đổi schema/migration đồng thời với backup.

Laravel Scheduler đăng ký backup mỗi ngày lúc **02:00 Asia/Ho_Chi_Minh**. Thay đổi qua `.env`:

```dotenv
DB_BACKUP_DAILY_TIME=02:00
DB_BACKUP_TIMEZONE=Asia/Ho_Chi_Minh
```

Docker Compose có service `scheduler`, dùng cùng cấu hình database và volume với app:

```bash
docker compose up -d --no-deps scheduler
docker compose ps scheduler
docker compose exec -T app php artisan schedule:list
```

Không cài thêm cron gọi `schedule:run` khi service này đang chạy để tránh hai scheduler.
Nếu thay đổi cấu hình khi đã cache config, cập nhật cache rồi restart service scheduler.
Môi trường không dùng Docker cần chạy `php artisan schedule:run` mỗi phút qua cron, hoặc
quản lý `php artisan schedule:work` bằng process supervisor.

Đọc file private bên trong container, ví dụ kiểm tra gzip:

```bash
docker compose exec -T app gzip -t /app/storage/app/private/backups/TEN_FILE.sql.gz
```

Phục hồi SQL vào một database MySQL mới/rỗng trước để kiểm chứng. File không chứa lệnh
DROP DATABASE/DROP TABLE và không tự chọn database đích. Bản SQL bao gồm schema, dữ liệu,
trigger và bảng migrations; không gồm tài khoản/quyền của MySQL server hay file tải lên trong storage.

## Kiểm chứng 2026-10-05

- Command thực tế tạo file nén; thử nhập vào database tạm riêng bằng MySQL client thành công.
- Checksum của **51 bảng** phục hồi khớp toàn bộ với database nguồn `esl`; database tạm được dọn sau kiểm chứng.
- Test backup + database isolation: **9 test, 37 assertion**, qua trên SQLite/fake connection;
  test không kết nối MySQL ứng dụng.
- `schedule:list` xác nhận lịch `0 2 * * *`; service scheduler được khởi động.
