# Quản lý rule và credit

Trang `/admin/ai/credits` dùng chung layout admin, menu **Rule & Credit AI**.
Chỉ admin đang hoạt động được truy cập; package kiểm tra quyền qua
`CreditAdministrator`, website cung cấp adapter riêng, mặc định package từ chối.

## Cài đặt

Sao lưu database trước khi chạy migration mới `000005_create_tutor_ai_audit_logs`.
Không cần biến môi trường mới. Migration không cấp credit hoặc thay đổi rule cũ.

```sh
docker compose exec -T app php artisan migrate --path=vendor/tdsoft/ai-tutor/database/migrations
docker compose exec -T app php artisan ai-tutor:schema-check
```

Kết quả có `AI Tutor credit admin: installed`. Nếu chưa migrate, trang chỉ đọc
và hiển thị cảnh báo. Không sửa các migration đã áp dụng trước đó.

## Cách dùng

1. Đăng nhập admin, mở **Rule & Credit AI**.
2. Bật `knowledge_embedding` để tạo vector và `tutor_message` để chat.
   Với rule mới, có thể thử base = 1, max = 1: một credit nội bộ mỗi request.
   Đây không phải giá tiền của provider. Max là mức dự trữ trước request;
   cần đủ số dư/quota cho mức này. Rule có blocks cũ vẫn cộng phí theo blocks.
3. Tìm người nhận theo tên hoặc ID, chọn tài khoản, nhập số credit và lý do,
   đánh dấu xác nhận rồi cấp. Cấp cho admin tạo phiên bản Knowledge để ingest;
   cấp cho người hỏi để chat. Truy vấn RAG còn có thể tốn credit embedding.
4. Kiểm tra số dư, lịch sử ledger và audit phía dưới trang.

Cấp credit là cộng số dư, không tăng quota ngày/tuần/tháng. Tài khoản mới dùng
`AI_DAILY_CREDITS`; tài khoản cũ giữ nguyên giới hạn. Trang chưa sửa quota,
không hỗ trợ trừ credit, xóa lịch sử hay sửa trực tiếp số dư.

Rule editor sửa base, max và bật/tắt; giữ nguyên blocks, cost_rates, currency
đã có. Không thay đổi snapshot hay usage của request cũ. Nếu admin khác sửa
rule trước khi lưu, cần tải lại trang; gửi lại cùng thao tác không cấp trùng.

Mỗi thay đổi rule/cấp credit ghi audit cùng transaction với thao tác, gồm
người thực hiện, người nhận/feature, trước/sau và lý do cấp. Form có CSRF,
kiểm tra quyền cả controller và service. Trang provisioning không yêu cầu
license hợp lệ; các chức năng AI vẫn giữ nguyên license guard và kiểm tra quota.
Credit nội bộ không nạp tiền vào tài khoản OpenAI/Gemini.

## Kiểm thử

Suite package dùng SQLite in-memory và mock, không gọi provider hoặc cấp credit
trên website thật. Cần kiểm tra thêm giao diện và khóa đồng thời MySQL ở staging.
