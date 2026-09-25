# AI Tutor — Foundation và signed offline license

Một Composer package tdsoft/ai-tutor, PHP ^8.3 / Laravel ^13.17. Tên phiên bản phát hành
dự kiến đầu tiên là 0.1.0, độc lập với version 2.0.3 của đặc tả. Chưa tạo tag/release.

## Phạm vi

- AiExecutionService, DTO, provider contracts, test-only mock provider.
- BillingManager, customer-key driver và vendor-credit placeholder từ chối thực thi.
- Actor/LMS contracts; website bind adapter trong AiTutorIntegrationServiceProvider.
- Sáu bảng requests, accounts, rules, ledger, usage, cost snapshots mang prefix tutor_ai_.
- Reserve/commit/release trong transaction, khóa account, unique idempotency key.
- Replay từ kết quả mã hóa; kiểm tra actor/quyền lại trước replay.
- Ledger và usage ghi bổ sung, không chỉnh lịch sử; model chặn sửa/xóa từng bản ghi.
  Không dùng bulk update/delete hoặc SQL trực tiếp để thay lịch sử. Người có quyền DB
  vẫn có thể sửa dữ liệu; đây không phải cơ chế chống sửa trên server do khách toàn quyền.
- Event Speaking/Writing skeleton phát sau commit; JS/SCSS entry qua Vite website.
- Preflight schema phân biệt cài mới, installation hợp lệ, cài dở/xung đột.

Phase 2 đã có signed-license client, refresh nền và trang quản trị /admin/ai/license.
Xem [LICENSE-CLIENT.md](LICENSE-CLIENT.md) để chạy khi chưa có License Server.
Chưa có Tutor endpoint/widget, provider API thật, trang quản trị provider credential, RAG,
Speaking/Writing hay Vendor Gateway. Các luồng AI cũ của LMS
chưa chuyển sang package và không được xem là đã đạt tiêu chuẩn V2.

## Tích hợp

Package không import App models hay biết schema LMS. Website resolve actor từ session,
chuyển ID thành chuỗi và chỉ trả DTO nội dung đã được phép. Contract question nhận thêm
lessonId để kiểm tra ràng buộc question–lesson–course rõ ràng.

Adapter ESL hỗ trợ lesson và câu hỏi bank_manual thuộc activity hiển thị.
Question inline/random phải gắn với attempt ở Phase 3; hiện từ chối chúng.
Học thử không lấy summary của lesson trả phí hoặc câu hỏi trong activity trả phí.
correct_answer, explanation và metadata đánh dấu đáp án không được đưa vào DTO.
Teacher/admin giữ quy tắc truy cập hiện tại của LMS. Answer policy mặc định hints_only.

~~~php
$actor = app(\TDSoft\AiTutor\Contracts\ActorResolver::class)->resolve();
$result = app(\TDSoft\AiTutor\Core\AiExecutionService::class)->execute(
    \TDSoft\AiTutor\Core\AiRequest::forFeature(
        feature: 'tutor_message',
        actor: $actor,
        payload: ['message' => 'Explain this sentence'],
        requestId: (string) \Illuminate\Support\Str::uuid(),
        idempotencyKey: $stableSubmissionKey,
        courseId: (string) $courseId,
        lessonId: (string) $lessonId,
    )
);
~~~

Không nhận actor từ request body. Resource ID cần adapter xác thực.
Context truyền sẵn vào DTO bị bỏ và dựng lại qua adapter.
Giữ idempotency key cho cùng một hành động; thay payload với key cũ bị từ chối.
Request processing/failed không tự chạy lại provider. Request ID không tái sử dụng cho key khác.

## Cấu hình

Không cần key AI thật để kiểm thử. File .env website không bị sửa.

~~~dotenv
AI_TUTOR_ENABLED=false
AI_BILLING_MODE=customer_key
AI_DEFAULT_PROVIDER=openai
AI_DEFAULT_CHAT_MODEL=
AI_DAILY_CREDITS=10
OPENAI_API_KEY=
GEMINI_API_KEY=
~~~

Config package: config/ai-tutor.php. Provider resolver chỉ dùng binding đã đăng ký;
CredentialResolver chỉ đọc credential của provider được yêu cầu. Key không nằm trong
request DTO, database request, ledger, job hoặc event. Runtime mock bị từ chối ngoài testing.
Entitlement verify signed license offline; không có license hợp lệ thì từ chối, không có allow-all production.
Test bind fake entitlement riêng. Customer-key không cần Gateway config hoặc License HTTP theo từng request.

## Credit và pricing

Provisioning là thao tác server tin cậy, chưa có endpoint/admin UI:

~~~php
$accounts = app(\TDSoft\AiTutor\Billing\CreditAccounts::class);
$accounts->openLearner($actorId); // balance=0; daily limit từ config; không ghi đè account cũ
app(\TDSoft\AiTutor\Billing\CreditLedger::class)->grant($actorId, 100, 'unique-admin-action-id');
~~~

Rule phải được quản trị/provision trước khi dùng feature. blocks là JSON:

~~~json
{"input_tokens":{"size":2000,"units":1},"audio_seconds":{"size":60,"units":1}}
~~~

Credit tính base_units + tổng ceil(usage/size)*units, chặn ở max_units_per_request.
Reserve toàn bộ ceiling, commit actual product units rồi release phần dư. Ceiling này
không giới hạn chi phí provider; usage vẫn ghi đầy đủ. Snapshot rule cố định cho request.
Balance là phần có thể sử dụng; reserve đã trừ khỏi balance, commit không trừ lần hai.
Balance null là không giới hạn; daily/weekly/monthly limits vẫn kiểm tra.
Quota dùng timezone application, tuần theo Carbon startOfWeek, tính cả hold chưa settle.

Phase 1 resolve account học viên scope system. Schema có owner_type/scope cho mở rộng.
Quota course/role/group và giới hạn token/request/audio độc lập triển khai sau.
Daily credit là quota, không tự cấp thêm balance hằng ngày.

cost_rates: số nguyên micro-currency trên một triệu token hoặc trên một giây audio/item.
Cached input tách khỏi input thường. Thiếu rate cho usage phát sinh thì estimated cost=null.
Usage/audio seconds dùng số nguyên; provider làm tròn duration lên giây.

## Lỗi và đối soát

Mã chính: AI_DISABLED, AI_ACTOR_INVALID, AI_ADAPTER_NOT_CONFIGURED, AI_CONTEXT_FORBIDDEN,
AI_FEATURE_INVALID, AI_REQUEST_INVALID, AI_REQUEST_DUPLICATE, AI_CREDIT_RULE_INVALID,
AI_CREDIT_INSUFFICIENT, AI_DAILY_LIMIT_REACHED, AI_PROVIDER_NOT_CONFIGURED,
AI_PROVIDER_AUTH_FAILED, AI_PROVIDER_RATE_LIMITED, AI_PROVIDER_UNAVAILABLE,
BILLING_MODE_NOT_SUPPORTED, LICENSE_MODULE_NOT_ALLOWED, AI_USAGE_INVALID,
AI_RESERVATION_INVALID, AI_HISTORY_IMMUTABLE, AI_REQUEST_RECONCILIATION_REQUIRED,
AI_DESTRUCTIVE_ROLLBACK_DISABLED.
Weekly/monthly limit failures hiện dùng chung AI_DAILY_LIMIT_REACHED.

Provider chỉ phát known-no-result errors nếu chắc chắn không có kết quả cần tính usage.
Timeout mơ hồ dùng AI_PROVIDER_OUTCOME_UNKNOWN. Không ghi raw exception/provider error.
Response thành công được lưu encrypted trước settlement để đối soát khi transaction lỗi.

Khi AI_REQUEST_RECONCILIATION_REQUIRED hoặc request treo processing: giữ reservation,
không retry provider bằng key mới. Kiểm tra request/provider IDs và encrypted result
trên server có quyền; đối chiếu usage rồi hoàn tất settlement trong transaction bằng ledger.
Nếu chưa có kết quả local, xác nhận outcome với provider trước khi hoàn credit.
Phase 1 chưa có command tự động reconcile; không tự sửa/xóa ledger.
Kết quả mã hóa cần retention theo chính sách dữ liệu khi triển khai Phase 3.

## Event, asset và HTTP

WritingAssessmentCompleted/SpeakingAssessmentCompleted có payload version 1,
event ID, assessment ID, actor/context ID, điểm tham khảo và rubric version.
Website dùng queued listener idempotent theo event ID; retry listener không gọi lại AI.
Điểm AI không tự cập nhật điểm học vụ chính thức.

Website resources/js/ai-tutor.js và resources/scss/ai-tutor.scss import package từ vendor;
Vite đã nối hai entry. Asset chưa gắn layout vì Phase 1 chưa có UI.
Build CI/staging dùng Node >=22.12, lock file và Sass. CSS token có scope riêng.
Không build/publish asset trong Composer scripts.

Khi thêm endpoint Phase 3: session auth + CSRF cùng domain. LMS hiện có CSRF exception
api/*; cần thu hẹp exception hoặc middleware bảo vệ riêng /api/ai-tutor/v1/* và test
401/403/419 trước mở endpoint. Phase 1 không tạo endpoint thực thi AI chưa có license.

## Kiểm thử

~~~bash
docker compose exec -T app php vendor/bin/phpunit -c packages/tdsoft/ai-tutor/tests/phpunit.xml
~~~

Foundation dùng container riêng, không bootstrap website/đọc .env hoặc chạy migration/seeder
LMS; chỉ SQLite :memory: với guard bắt buộc. Suite Website Adapter nằm ở tests/AiTutor,
tạo fixture schema tối thiểu trong memory. Mock provider không gọi mạng.
Không chạy composer setup, scratch scripts hoặc database dev.

Xem [UPGRADE.md](UPGRADE.md) và [CHANGELOG.md](CHANGELOG.md).
