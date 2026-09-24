# Signed offline license client — Phase 2

License Server chưa cần tồn tại để phát triển/test client. Không có license giả cho
runtime và không có private signing key trong package. Bộ test sinh khóa ngẫu nhiên
trong memory, bind fake public-key ring và giả lập HTTP; không gọi dịch vụ thật.

## Sử dụng ngay khi chưa có License Server

Chạy migration mới sau kiểm tra/backup phù hợp, không chạy lại/reset Phase 1:

~~~bash
docker compose exec -T app php artisan ai-tutor:schema-check
docker compose exec -T app php artisan migrate --path=vendor/tdsoft/ai-tutor/database/migrations
docker compose exec -T app php artisan ai-tutor:schema-check
docker compose exec -T app php artisan ai-tutor:license init
docker compose exec -T app php artisan ai-tutor:license status
~~~

Migration mới 2026_09_24_000002_create_tutor_ai_license_tables.php tạo
tutor_ai_license_state và tutor_ai_license_refresh_attempts. Không sửa migration cũ.
Preflight có hook trước migration; kiểm tra cài mới và upgrade qua history/schema.
Trạng thái license unconfigured là đúng khi chưa có license ký; status command trả exit 1.

Đăng nhập admin, mở /admin/ai/license hoặc menu “License Gia sư AI”.
Trang hoạt động kể cả AI_TUTOR_ENABLED=false hoặc license chưa có/hết hạn để admin sửa cấu hình.
Route có web/session auth, adapter quyền admin, CSRF và throttle POST. Trang không hiển thị
license key, envelope hoặc public-key file path. Mã license nhập vào không được flash old input.
Chỉ admin đang active được quản trị; website khác schema thay LicenseAdministrator.

Không nhập key thử rồi mong mở module. Chưa có server thì có thể khởi tạo installation,
xem trạng thái và chạy test; module runtime vẫn từ chối cho đến khi nhận chữ ký tin cậy.

## Cấu hình khi có License Server

~~~dotenv
AI_LICENSE_SERVER_URL=https://license.example.vn
AI_LICENSE_KEY=
AI_LICENSE_PUBLIC_KEY_ID=license-key-2026-01
AI_LICENSE_PUBLIC_KEY_PATH=/secure/path/license-public.key
AI_LICENSE_INSTALLATION_ID=
AI_LICENSE_DOMAIN=
AI_LICENSE_REFRESH_HOURS=24
AI_LICENSE_GRACE_DAYS=7
~~~

File public key chứa Base64 của 32 byte Ed25519 public key, có thể kết thúc bằng newline.
Dùng đường dẫn tuyệt đối trên filesystem server. Không dùng PEM/private key.
Muốn xoay public key, cấu hình nhiều key_id => path trong license.public_keys; chỉ bỏ key cũ
sau khi các license ký bằng key cũ đã được refresh. key_id không được dùng làm đường dẫn.
Giá trị ký dùng [PHP Sodium Ed25519 verification](https://www.php.net/manual/en/function.sodium-crypto-sign-verify-detached.php).

Installation ID được tạo một lần, lưu DB. ENV ID tùy chọn phải trùng ID đã lưu; đổi ENV
không ghi đè identity. Clone installation sang khách/domain khác phải có license riêng.
Domain mặc định lấy hostname từ APP_URL (hoặc AI_LICENSE_DOMAIN), lowercase, không có port.
Không dùng Host header làm nguồn cấu hình. Middleware RequireModule còn kiểm tra host
request với domain cấu hình. IDN phải dùng dạng ASCII/punycode đã chuẩn hóa.

License key nhập admin được mã hóa bằng APP_KEY để refresh; bảo vệ/backup APP_KEY.
AI_LICENSE_KEY là fallback cho integration đã có signed envelope mà không lưu key trong DB.
Không ghi hoặc thay đổi file .env qua UI.

## Chính sách thời gian đã chốt

- Offline deadline = min(last_refreshed_at + 7 ngày, signed grace_until).
- Config grace_days chỉ cho rút ngắn trong 1–7 ngày, không kéo dài quá policy.
- Trước expires_at: active; đến refresh_after (hoặc tối đa 24h sau xác nhận) là refresh_due.
- Refresh lỗi: giữ cache hợp lệ, trạng thái grace trong offline deadline.
- Đến expires_at mà chưa có lỗi refresh: expired. Có lỗi refresh thì được grace, vẫn bị
  chặn ở offline deadline; không xem grace là gia hạn license.
- Đúng thời điểm offline deadline: khóa với LICENSE_GRACE_EXPIRED.
- Signed status=revoked khóa ngay. HTTP 403/410 không có signed document chỉ là lỗi API.
- Cảnh báo admin trong 3 ngày trước hạn license/offline. Đây là cảnh báo trên trang,
  chưa gửi email hoặc thông báo ngoài ứng dụng.
- Sai chữ ký/domain/installation, cache hỏng hoặc public key không tin cậy đều bị từ chối.

Chỉ envelope ký hợp lệ có issued_at mới hơn envelope đã lưu được chấp nhận.
Response cũ/giống hệt bị LICENSE_STALE_DOCUMENT, không đổi last_refreshed_at hoặc kéo dài grace.
Reader luôn verify local. Kiểm tra entitlement không HTTP và không dispatch refresh từ AI request.
last_verified_at ghi lần chấp nhận envelope được xác minh thành công, không ghi lại trên mỗi read.
student_limit được lưu trong signed document và hiển thị admin; enforcement khi ghi danh/
đếm học viên cần integration admission policy riêng, chưa thực hiện trong Phase 2.

## Refresh nền

Package đăng ký scheduler mỗi phút; chỉ enqueue khi đã activate, có server URL và đến
next_attempt_at. Refresh bình thường tối đa mỗi 24h, ưu tiên refresh_after sớm hơn.
Retry sau lỗi: 1h, 6h, 24h; tiếp tục mỗi 24h. Có queue uniqueness và DB lease 2 phút.
Job payload không chứa key/document/actor/student; worker đọc credential ở backend.
Network timeout 15s, connect timeout 5s, không theo redirect, không gửi lại tự động trong
cùng HTTP call. Request ID/idempotency key có trong header, audit lưu mã lỗi an toàn.

Hạ tầng cần scheduler và queue worker theo cơ chế hiện có:

~~~bash
php artisan schedule:run
php artisan queue:work
~~~

schedule:run thường chạy mỗi phút bằng cron; queue:work chạy bằng supervisor/systemd.
Không tự bật daemon trong lần triển khai code. Admin “Refresh” hoặc CLI có thể chạy thủ công:

~~~bash
docker compose exec -T app php artisan ai-tutor:license refresh --force
~~~

## Contract cho License Server tương lai

POST /api/v1/licenses/activate và POST /api/v1/licenses/refresh qua HTTPS.
Header X-Request-ID và Idempotency-Key; server xử lý idempotent theo installation/request.
Activate gửi đúng license_key, domain, installation_id, app_version (version package cài).
Refresh thêm license_id. Không gửi user, tiến độ, hội thoại, audio, tài liệu, usage AI,
provider credential hoặc internal learner credit.

Response thành công:

~~~json
{
  "document": {
    "license_id": "lic_example",
    "installation_id": "install_example",
    "domain": "lms.example.vn",
    "modules": ["ai_tutor_core"],
    "student_limit": 100,
    "issued_at": "2026-09-24T00:00:00Z",
    "expires_at": "2027-09-30T23:59:59Z",
    "refresh_after": "2026-09-25T00:00:00Z",
    "grace_until": "2027-10-07T23:59:59Z"
  },
  "signature": "BASE64_DETACHED_ED25519_SIGNATURE",
  "key_id": "license-key-2026-01"
}
~~~

Document có thể thêm duy nhất status=active|revoked; muốn revoke phải gửi envelope ký
mới với status=revoked, kể cả khi license đã hết hạn. Không nhận field mở rộng chưa định nghĩa.
Server phải cấp issued_at mới mỗi lần refresh thành công; clock skew tương lai tối đa 5 phút.
Version protocol v1 canonicalization: flat JSON object, field keys sắp xếp ASCII tăng dần,
UTF-8 không whitespace/BOM/newline, không escape slash, modules giữ nguyên thứ tự array,
integer dạng thập phân (không float), string ID/domain/module bị giới hạn ASCII theo schema.
Timestamp bắt buộc YYYY-MM-DDTHH:MM:SSZ. Ký toàn bộ canonical document, bao gồm status nếu có.
Đây là schema canonical riêng có test golden bytes, không tuyên bố là RFC 8785 tổng quát.
Xem CanonicalDocument.php để đồng bộ signer ở server. Không đặt signer/private key vào client.
Public key chỉ được chọn từ cấu hình tin cậy; không nhận public key từ response.

## Tích hợp layout quản trị

Package mặc định dùng layout độc lập `ai-tutor::layouts.admin`. Website có thể đặt
`ai-tutor.license.admin_layout` thành tên Blade layout của LMS và `admin_section`
thành tên section nội dung (mặc định `content`), trong integration service provider.
Layout cần có `@stack('styles')` trong head để nạp `asset_entries` qua Vite.
`admin_theme` nhận `light`, `dark`, `system`; null dùng theme mặc định của package.
Website này chọn `layouts.admin` và theme `dark`; package không phụ thuộc layout LMS.
Không thay đổi kiểm tra quyền, route, CSRF hoặc chính sách license khi đổi layout.

## Các mã lỗi bổ sung

LICENSE_NOT_ACTIVATED, LICENSE_MIGRATION_REQUIRED, LICENSE_INVALID,
LICENSE_INVALID_SIGNATURE, LICENSE_PUBLIC_KEY_NOT_CONFIGURED,
LICENSE_DOMAIN_MISMATCH, LICENSE_INSTALLATION_MISMATCH,
LICENSE_EXPIRED, LICENSE_GRACE_EXPIRED, LICENSE_REVOKED,
LICENSE_SERVER_NOT_CONFIGURED, LICENSE_SERVER_UNAVAILABLE,
LICENSE_KEY_REQUIRED, LICENSE_REFRESH_BUSY, LICENSE_STALE_DOCUMENT.

Lỗi API/invalid refresh không ghi đè cache đã xác minh. Client không lưu raw response hoặc
exception message vào audit/application log. Giao diện light/dark dùng asset website Vite;
build lại asset sau nâng cấp. Không có nút tự chạy Composer hoặc migration qua HTTP.
