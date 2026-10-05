# TUTOR AI V2 — SELF-HOSTED PRODUCT & TECHNICAL SPECIFICATION

> **Tên tài liệu:** `tutor-ai-spec-v2.md`  
> **Phiên bản:** 2.0.3  
> **Ngày cập nhật:** 2026-09-24  
> **Trạng thái:** Nguồn sự thật cho Codex khi phân tích và triển khai  
> **Kiến trúc chốt:** Self-hosted AI Tutor + BYOK + signed offline license  
> **Stack mặc định:** Laravel 13, PHP 8.3+, MySQL 8+, Redis, Blade + Bootstrap 5  
> **Ngôn ngữ sản phẩm:** Tiếng Việt; nội dung học có thể dùng tiếng Anh

---

## 1. Chỉ dẫn bắt buộc cho Codex

Codex phải đọc toàn bộ tài liệu này trước khi lập kế hoạch, sửa code hoặc tạo migration.

Nếu yêu cầu mới nhất của người dùng mâu thuẫn với tài liệu, ưu tiên yêu cầu mới nhất. Nếu điểm chưa rõ có thể thay đổi kiến trúc dữ liệu, billing, license, bảo mật hoặc chi phí, phải hỏi lại trước khi triển khai.

Quy tắc bắt buộc:

1. Không tự ý chuyển kiến trúc thành SaaS tập trung.
2. Dữ liệu học viên và dữ liệu AI mặc định nằm trên server khách.
3. Không gửi hội thoại, audio, bài Writing, tài liệu RAG hoặc API key về License Server.
4. Không đặt API key OpenAI, Gemini hoặc nhà cung cấp AI ở frontend, widget, mobile app hay repository.
5. Module nghiệp vụ không được gọi trực tiếp SDK AI; mọi lệnh AI đi qua `AiExecutionService`.
6. Module nghiệp vụ không được tự xử lý billing; mọi lệnh đi qua `BillingManager`.
7. V1 triển khai `customer_key`; kiến trúc bắt buộc sẵn sàng cho `vendor_credit`.
8. Mọi request tốn tài nguyên phải có `request_id` và `idempotency_key`.
9. Credit phải dùng transaction và các trạng thái reserve/commit/release.
10. Usage record và credit ledger là lịch sử bất biến; không sửa/xóa tùy tiện.
11. License phải xác minh bằng chữ ký bất đối xứng; không nhúng private key hoặc shared signing secret vào server khách.
12. Không kiểm tra license online ở từng câu hỏi AI.
13. Không tin `user_id`, `course_id`, `lesson_id`, `question_id` từ browser nếu chưa xác thực quyền.
14. Không để AI tự quyết định điểm học vụ cuối cùng khi chưa lưu rubric, evidence, model và prompt version.
15. Không dùng `migrate:fresh`, `migrate:reset`, `db:wipe`, rollback phá dữ liệu.
16. Test chỉ chạy với SQLite `:memory:` hoặc database có hậu tố `_testing`.
17. Không dùng `rm -rf`, `git clean`, `git reset --hard`; không xóa `.env`, uploads hoặc dữ liệu người dùng.
18. Khi hoàn thành phải nêu file đã sửa, migration, biến môi trường, test đã chạy và phần chưa làm.

---

## 2. Quyết định kiến trúc cốt lõi

Sản phẩm sử dụng mô hình:

> **Self-hosted AI Tutor + Bring Your Own Key (BYOK) + signed offline license**

Mục tiêu:

- Khoảng 95% dữ liệu và xử lý nằm tại server khách.
- Khách hàng kiểm soát dữ liệu, API key và chi phí AI.
- Phần mềm vẫn hoạt động trong thời gian License API tạm mất kết nối.
- Nhà cung cấp bán phần mềm theo module, domain, installation, thời hạn và giới hạn gói.
- Có thể bổ sung dịch vụ bán credit AI sau mà không sửa Tutor, Speaking, Writing, RAG hoặc UI.

### 2.1. Sơ đồ tổng thể

```text
Browser / Mobile
    -> Laravel LMS trên server khách
        -> AI Tutor modules
        -> Local database / storage / Redis / vector store
        -> BillingManager
            -> CustomerKeyBillingDriver -> OpenAI / Gemini / provider khác
            -> VendorCreditBillingDriver -> Vendor AI Gateway (sau V1)
        -> LicenseClient
            -> xác minh license cache bằng public key
            -> refresh định kỳ với License Server
```

### 2.2. Nguyên tắc phụ thuộc

- Luồng học tập không phụ thuộc License Server theo thời gian thực.
- Chế độ `customer_key` không phụ thuộc Vendor AI Gateway.
- Chế độ `vendor_credit` phụ thuộc Vendor AI Gateway cho từng request AI.
- License Server không phải nơi lưu dữ liệu học tập.
- Website khách là nguồn dữ liệu chính cho user, khóa học, tiến độ và kết quả.

---

## 3. Phân chia trách nhiệm

### 3.1. Server khách lưu và xử lý

- Người dùng, vai trò, ghi danh và phân quyền.
- Khóa học, bài học, bài tập và kết quả học.
- Hội thoại Tutor AI, message và citation.
- Speaking audio, transcript, điểm và feedback.
- Writing draft, submission, issue, rubric và feedback.
- Documents, chunks, embeddings và vector index.
- API key AI của khách.
- Credit/quota nội bộ.
- Usage, token, audio duration và estimated cost.
- Recommendation, learner skill snapshot.
- Queue, audit và application log.
- License document/cache đã ký.

### 3.2. License Server của nhà cung cấp lưu

```text
customers
products
product_modules
licenses
license_modules
license_domains
license_installations
license_activations
license_refresh_logs
release_versions
release_artifacts
license_audit_logs
```

License Server không lưu:

- Nội dung câu hỏi/hội thoại.
- Audio hoặc transcript.
- Bài Writing.
- Điểm và tiến độ học viên.
- Tài liệu, chunk hoặc embedding.
- API key AI của khách.
- Credit nội bộ từng học viên.

### 3.3. Vendor AI Gateway — chỉ dùng khi triển khai `vendor_credit`

- Xác thực installation và request signature.
- Kiểm tra license/module.
- Reserve/commit/release số dư vendor credit.
- Giữ API key AI của nhà cung cấp phần mềm.
- Gọi AI provider và trả kết quả chuẩn hóa.
- Đối soát request, usage và số dư trung tâm.

Vendor AI Gateway không cần được triển khai trong V1, nhưng contract và driver phải được chuẩn bị.

---

## 4. Phạm vi sản phẩm

### 4.1. V1

1. AI Tutor Core.
2. Provider abstraction.
3. `AiExecutionService`.
4. `BillingManager` và `CustomerKeyBillingDriver`.
5. Credit/quota/usage local.
6. Signed offline license và module entitlement.
7. Knowledge Base/RAG local.
8. Tutor theo khóa học/bài học.
9. AI Speaking.
10. AI Writing.
11. Recommendation cơ bản.
12. Dashboard học viên/giáo viên.
13. Dark theme cho sản phẩm tiếng Anh A1–B2, TOEIC, IELTS.

### 4.2. Chuẩn bị trong V1 nhưng chưa vận hành

- `VendorCreditBillingDriver` dạng placeholder an toàn.
- DTO và API contract cho Vendor AI Gateway.
- `remote_request_id`, `billing_mode` và các trường đối soát trong database.
- Cấu hình billing theo hệ thống/tính năng nhưng chưa cho phép mode chưa hỗ trợ.

### 4.3. Sau V1

- Vendor Credit và Vendor AI Gateway.
- Toán, Ngữ văn, Lý, Hóa, Sinh, Khoa học xã hội.
- Exercise Generator nâng cao.
- Realtime voice conversation.
- Analytics và cảnh báo sớm nâng cao.

---

## 5. Stack và cách đóng gói

Nếu repository chưa có quy định khác:

- Laravel 13, PHP 8.3+.
- Blade + Bootstrap 5, Vite, SCSS.
- MySQL 8+.
- Redis cho cache, queue và rate limit.
- Queue worker và scheduler riêng.
- Local/S3-compatible object storage.
- SSE cho streaming V1.
- Vector store được thay thế qua interface; có thể dùng database/vector service phù hợp hạ tầng khách.

### 5.1. Quyết định đóng gói V1

V1 phát hành **một Composer package**, bên trong chia module chức năng. Tên minh họa `your-vendor/ai-tutor`; thay vendor thực tế khi khởi tạo repository. Không tạo nhiều package độc lập cho từng tính năng trong V1.

Cấu trúc khi phát triển trong Laravel Base:

```text
packages/your-vendor/ai-tutor/
  composer.json
  src/
    Core/
    Contracts/
    Providers/
    Billing/
    Licensing/
    Knowledge/
    Conversations/
    Speaking/
    Writing/
    Recommendations/
    Analytics/
    Integrations/
    Subjects/English/
    AiTutorServiceProvider.php
  config/
  database/migrations/
  resources/views/
  resources/js/
  resources/scss/
  routes/
  tests/
```

Package sở hữu migration, model, service, API, giao diện học viên và trang quản trị AI. Các tính năng được bật theo cấu hình và entitlement; cùng nằm trong package không có nghĩa mọi khách được sử dụng mọi module.

Phát triển có thể dùng Composer path repository. Khi phát hành, web khách cài bản có tag qua private Composer/VCS repository. Không chỉnh trực tiếp code trong `vendor/`. Giữ ranh giới contract để có thể tách package về sau nếu cần phát hành độc lập.

License Server là ứng dụng riêng phía nhà cung cấp; Vendor AI Gateway triển khai sau là dịch vụ riêng. Cả hai không được đóng như server chạy kèm bắt buộc trong LMS khách.

### 5.2. Ranh giới tùy chỉnh

- Package: nghiệp vụ AI, credit, usage, provider, license client, UI và schema `tutor_ai_*`.
- Website: authentication, phân quyền LMS, adapter dữ liệu, event listener, menu và cấu hình tích hợp.
- Tùy chỉnh qua contract, config, event hoặc view override có namespace.
- View override phải được kiểm tra lại khi nâng cấp; file đã copy sang website không tự nhận thay đổi từ package.
- Không hard-code `App\Models\User`, `Course`, `Lesson` hoặc tên bảng LMS bên trong package. Adapter chuyển đối tượng website thành DTO trung lập; ID LMS coi là opaque string và cấu hình kiểu khóa nếu cần liên kết trực tiếp.

---

## 6. Hai chế độ billing

```dotenv
AI_BILLING_MODE=customer_key
# AI_BILLING_MODE=vendor_credit
```

### 6.1. `customer_key` — triển khai trong V1

- Khách tự nhập API key.
- Server khách gọi trực tiếp provider.
- Khách trả chi phí trực tiếp cho provider.
- Credit là quota nội bộ do khách cấu hình.
- Usage, credit transaction và estimated cost lưu tại server khách.
- License Server không tham gia vào từng request.

### 6.2. `vendor_credit` — làm sau

- Server khách gọi Vendor AI Gateway.
- Vendor giữ API key và bán credit cho khách.
- Gateway kiểm tra số dư và gọi provider.
- Usage local vẫn được lưu để dashboard và audit tại web khách.
- Vendor Gateway giữ bản ghi tối thiểu phục vụ billing/đối soát.

### 6.3. Không tự động fallback billing mode

Nếu `vendor_credit` chưa được hỗ trợ hoặc Gateway lỗi, không được âm thầm chuyển sang API key khách. Trả error rõ ràng:

```text
BILLING_MODE_NOT_SUPPORTED
VENDOR_GATEWAY_UNAVAILABLE
VENDOR_CREDIT_INSUFFICIENT
```

Việc fallback, nếu có sau này, phải là cấu hình opt-in có audit.

---

## 7. Billing abstraction bắt buộc

Các module Tutor, Speaking, Writing, RAG và Recommendation chỉ gọi:

```php
$response = $aiExecutionService->execute(
    AiRequest::forFeature(
        feature: 'writing_assessment',
        actor: $user,
        payload: $payload,
        requestId: $requestId,
        idempotencyKey: $idempotencyKey,
    )
);
```

Không module nào được đọc trực tiếp `AI_BILLING_MODE`.

### 7.1. Contract

```php
interface AiBillingDriver
{
    public function authorize(AiRequest $request): BillingAuthorization;

    public function execute(
        AiRequest $request,
        BillingAuthorization $authorization
    ): AiResponse;

    public function recordUsage(
        AiRequest $request,
        AiResponse $response
    ): void;

    public function release(
        AiRequest $request,
        BillingAuthorization $authorization,
        ?Throwable $error = null
    ): void;
}
```

Driver:

```text
CustomerKeyBillingDriver
VendorCreditBillingDriver
```

### 7.2. BillingManager

```php
final class BillingManager
{
    public function driver(?string $mode = null): AiBillingDriver;
}
```

Resolver lấy mode từ cấu hình đã được xác thực:

```php
return [
    'mode' => env('AI_BILLING_MODE', 'customer_key'),
    'drivers' => [
        'customer_key' => CustomerKeyBillingDriver::class,
        'vendor_credit' => VendorCreditBillingDriver::class,
    ],
];
```

### 7.3. Vai trò của AiExecutionService

1. Xác thực actor và feature.
2. Kiểm tra module entitlement.
3. Tạo/tìm `tutor_ai_request` theo idempotency key.
4. Tính/kiểm tra quota.
5. Reserve credit local.
6. Chọn billing driver.
7. Thực thi provider/Gateway.
8. Chuẩn hóa response.
9. Ghi `tutor_ai_usage_records`.
10. Commit credit.
11. Ghi audit/telemetry.
12. Khi lỗi: release credit và cập nhật request.

---

## 8. API key và provider

### 8.1. Provider contracts

```php
interface ChatProviderInterface {}
interface EmbeddingProviderInterface {}
interface SpeechToTextProviderInterface {}
interface TextToSpeechProviderInterface {}
interface ModerationProviderInterface {}
```

Provider response phải được chuẩn hóa về DTO nội bộ. Không để payload riêng của OpenAI/Gemini lan vào module nghiệp vụ.

### 8.2. Cách lưu API key

Hỗ trợ:

1. Biến môi trường — ưu tiên cho triển khai kỹ thuật.
2. Trang quản trị — lưu encrypted nếu khách cần tự nhập.

```text
tutor_ai_provider_credentials
- id
- provider
- label
- encrypted_api_key
- organization_id nullable
- default_chat_model nullable
- default_embedding_model nullable
- default_stt_model nullable
- status
- last_four
- last_verified_at nullable
- verification_error_code nullable
- created_by
- timestamps
```

Yêu cầu:

- Mã hóa bằng khóa thuộc server khách.
- Không trả lại key đầy đủ qua API.
- Không ghi key vào log, queue payload, exception hoặc failed job.
- Chỉ hiển thị dạng mask.
- Có thao tác kiểm tra kết nối và thay key.
- Không lưu key Vendor vào server khách khi dùng `vendor_credit`.

---

## 9. Credit, quota và usage local

Ngay cả khi `AI_BILLING_MODE=customer_key`, web khách vẫn phải lưu lịch sử sử dụng credit và AI usage.

### 9.1. Phân biệt khái niệm

| Khái niệm | Ý nghĩa |
|---|---|
| Credit unit | Đơn vị sản phẩm/quota nội bộ |
| Provider usage | Token, audio seconds, image count, pages |
| Estimated cost | Ước tính chi phí provider |
| Vendor credit | Số dư mua từ nhà cung cấp, chỉ có ở mode sau |

Không đồng nhất credit unit với USD hoặc token.

### 9.2. Bảng chính

```text
tutor_ai_requests
tutor_ai_credit_accounts
tutor_ai_credit_rules
tutor_ai_credit_transactions
tutor_ai_usage_records
tutor_ai_cost_snapshots
```

### 9.3. `tutor_ai_requests`

```text
id
request_id UUID/ULID UNIQUE
idempotency_key UNIQUE
user_id nullable
feature
billing_mode
provider nullable
model nullable
status: pending|authorized|processing|completed|failed|cancelled
reserved_units
actual_units
remote_request_id nullable
error_code nullable
started_at nullable
completed_at nullable
failed_at nullable
timestamps
```

### 9.4. `tutor_ai_usage_records`

Đây là lịch sử usage thực tế, dùng trong cả hai billing mode:

```text
id
request_id UNIQUE
user_id nullable
feature
billing_mode
provider
model
input_tokens
output_tokens
cached_tokens
audio_seconds
image_count
document_pages
credit_units
estimated_cost
currency
provider_request_id nullable
remote_request_id nullable
latency_ms nullable
status
metadata JSON nullable
timestamps
```

`tutor_ai_usage_records` không phải bảng số dư.

### 9.5. `tutor_ai_credit_accounts`

Account có thể thuộc học viên, nhóm/lớp hoặc toàn trung tâm:

```text
id
owner_type
owner_id
scope
balance nullable
daily_limit nullable
weekly_limit nullable
monthly_limit nullable
status
timestamps
```

### 9.6. `tutor_ai_credit_transactions`

```text
id
account_id
request_id
type: grant|reserve|commit|release|adjustment|expire
units
feature
balance_before nullable
balance_after nullable
idempotency_key UNIQUE
reference_type nullable
reference_id nullable
metadata JSON nullable
created_at
```

Ledger là append-only. Việc sửa sai tạo transaction bù, không sửa bản ghi cũ.

### 9.7. Luồng credit

```text
Request
 -> kiểm tra rule/quota
 -> reserve estimated units
 -> gọi AI
 -> ghi usage thực tế
 -> commit actual units
 -> release phần dư

Nếu lỗi trước khi có kết quả:
 -> release toàn bộ reservation
```

---

## 10. Pricing rule và quota

Feature code chuẩn:

```text
tutor_message
tutor_image_question
speaking_transcription
speaking_assessment
writing_assessment
writing_recheck
knowledge_embedding
exercise_generation
tts_playback
```

Rule có thể tính theo:

- Mỗi request.
- Token block.
- Audio minute.
- Image/page.
- Kết hợp base unit + variable unit.

Ví dụ:

```json
{
  "feature": "writing_assessment",
  "base_units": 1,
  "per_input_tokens": 2000,
  "units_per_input_block": 1,
  "max_units_per_request": 5
}
```

Quota hỗ trợ:

- Credit/ngày.
- Request/ngày.
- Token/tháng.
- Speaking minutes/ngày.
- Writing submissions/tuần.
- Giới hạn theo user, course, role hoặc toàn hệ thống.

---

## 11. Signed offline license

### 11.1. Mô hình chữ ký

- Private key chỉ nằm tại License Server.
- Public key được đóng trong license client/package.
- Thuật toán mặc định: Ed25519.
- Server khách chỉ verify, không có khả năng ký license.
- Không dùng shared secret chung để ký license.

### 11.2. Activation

```http
POST /api/v1/licenses/activate
```

Request:

```json
{
  "license_key": "LIC-XXXX-XXXX",
  "domain": "lms.trungtamabc.vn",
  "installation_id": "install_01J...",
  "app_version": "2.0.0"
}
```

Response:

```json
{
  "document": {
    "license_id": "lic_01J...",
    "installation_id": "install_01J...",
    "domain": "lms.trungtamabc.vn",
    "modules": [
      "ai_tutor_core",
      "ai_tutor_english",
      "ai_tutor_speaking",
      "ai_tutor_writing"
    ],
    "student_limit": 1000,
    "issued_at": "2026-09-24T00:00:00Z",
    "expires_at": "2027-09-30T23:59:59Z",
    "refresh_after": "2026-09-25T00:00:00Z",
    "grace_until": "2027-10-07T23:59:59Z"
  },
  "signature": "BASE64_ED25519_SIGNATURE",
  "key_id": "license-key-2026-01"
}
```

Chữ ký phải bao phủ canonical serialized document, không phụ thuộc thứ tự JSON tùy ý.

### 11.3. Cache và grace period

Mặc định:

| Thành phần | Giá trị đề xuất |
|---|---:|
| Refresh license | 24 giờ |
| Retry khi lỗi | 1 giờ, 6 giờ, 24 giờ |
| Grace period mất kết nối | 7 ngày |
| Cảnh báo admin | Trước khi khóa 3 ngày |

Luồng:

```text
Verify chữ ký local
 -> license còn hạn: hoạt động
 -> tới refresh_after: queue refresh nền
 -> refresh lỗi: dùng cache trong grace period
 -> hết grace: khóa module có kiểm soát
```

Không gọi License Server trong từng AI request.

### 11.4. Trạng thái license client

```text
active
refresh_due
grace
expired
revoked
invalid_signature
domain_mismatch
installation_mismatch
```

### 11.5. Module entitlement

```php
if (! $entitlements->allows('ai_tutor_speaking')) {
    abort(403);
}
```

Phải kiểm tra ở backend. Ẩn nút UI không thay thế authorization.

### 11.6. Giới hạn bảo vệ

Khách có toàn quyền source/server có thể sửa bỏ license guard. Self-hosted không thể ngăn tuyệt đối.

Biện pháp hợp lý:

- Private package/repository.
- Signed release artifact và checksum.
- Chỉ license hợp lệ được tải update/support.
- Ràng buộc domain và installation.
- Kiểm tra entitlement ở nhiều ranh giới service.
- Obfuscation chỉ là lớp phụ, không phải bảo mật tuyệt đối.

---

## 12. Cấu trúc module

```text
ai-tutor/
├── core
├── providers
├── billing
├── licensing
├── knowledge
├── conversations
├── assessment
├── recommendations
├── analytics
├── widget
├── integrations
├── subject-english
└── subject-*
```

Module môn học chỉ chứa:

- Prompt chuyên môn.
- Tool/capability được phép dùng.
- Rubric.
- Quy tắc trình bày.
- Chính sách an toàn.
- Cấu hình chương trình học.

Không sao chép billing, provider, conversation hoặc RAG vào từng môn.

---

## 13. AI Tutor Core

Teaching mode:

- `socratic`
- `hints_first`
- `explain`
- `practice`
- `review`
- `exam`

Answer policy:

- `no_answer`
- `hints_only`
- `hints_first`
- `full_solution`
- `teacher_controlled`

Cấp gợi ý:

1. Định hướng.
2. Nhắc kiến thức.
3. Minh họa bước gần nhất.
4. Lời giải đầy đủ nếu được phép.

Mỗi phản hồi nên có:

- Nội dung.
- Gợi ý/câu hỏi tiếp theo.
- Citation.
- Cảnh báo khi thiếu nguồn.
- Credit đã dùng/còn lại.
- Structured metadata.

---

## 14. Module tiếng Anh

Lộ trình:

- CEFR A1, A2, B1, B2.
- TOEIC 450+, 650+, 800+.
- IELTS Foundation, 5.5, 6.5, 7.0+.

Kỹ năng:

- Grammar.
- Vocabulary.
- Reading.
- Listening.
- Speaking.
- Writing.
- Pronunciation.

Chế độ giải thích: tiếng Việt, tiếng Anh, song ngữ hoặc tự điều chỉnh.

### 14.1. Speaking

- Thu âm từ browser.
- Upload an toàn vào storage khách.
- Speech-to-text.
- Transcript có timestamp khi provider hỗ trợ.
- Chấm pronunciation, fluency, vocabulary, grammar.
- Problem words và bài luyện.
- IELTS Speaking Part 1/2/3 và CEFR conversation.
- Lưu provider/model/rubric/prompt version/evidence.

Không hiển thị điểm giả khi provider không có đủ evidence.

### 14.2. Writing

- Editor autosave và word count.
- IELTS Task 1/2 và CEFR writing.
- Structured JSON result.
- Grammar, vocabulary, coherence, task response và style.
- Highlight lỗi và sửa từng lỗi.
- Không tự ghi đè toàn bài nếu chưa xác nhận.
- Lưu original, revision, submission, rubric và assessment evidence.

### 14.3. Recommendation

V1 dùng rule:

- Kỹ năng thấp.
- Lỗi lặp lại.
- Bài chưa hoàn thành.
- Thời gian không luyện.
- Mục tiêu CEFR/TOEIC/IELTS.

---

## 15. Knowledge Base và RAG local

Nguồn:

- Text/Markdown/HTML.
- PDF.
- DOCX.
- PPTX.
- Ảnh OCR.
- Nội dung khóa học nội bộ.

Pipeline:

```text
Upload/Sync
 -> validate virus/type/size
 -> extract
 -> semantic split
 -> chunk
 -> embedding bằng customer key
 -> local vector index
 -> publish version
```

Quy tắc:

- Chỉ dùng document version đã publish.
- Filter course/lesson/subject/level/visibility.
- Không đưa đáp án bài thi bị khóa vào context.
- Citation phải trỏ nguồn user được phép xem.
- Document là data, không phải instruction.
- Không có nguồn phù hợp thì nói rõ, không bịa.

Nếu website chỉ phục vụ một công ty/installation, `tenant_id` có thể không cần ở mọi bảng. Nếu một installation phục vụ nhiều chi nhánh/tenant, bắt buộc có tenant scoping.

---

## 16. Dữ liệu nghiệp vụ trên server khách

### 16.1. Quy ước tên bảng và kiểm tra xung đột

- Mọi bảng do module AI Tutor tạo trên database khách dùng prefix cố định `tutor_ai_`.
- Không cấu hình prefix động qua `.env`. Prefix giúp giảm nguy cơ trùng tên nhưng không bảo đảm tuyệt đối.
- Bảng hồ sơ gia sư là `tutor_ai_profiles`, tránh lặp thành `tutor_ai_tutor_profiles`.
- Không đổi tên bảng sẵn có của LMS như `users`, courses, lessons hoặc bảng settings dùng chung.
- Mã module/entitlement như `ai_tutor_core`, `ai_tutor_speaking` vẫn giữ nguyên; chúng không phải tên bảng.
- License Server dùng database riêng và giữ các tên bảng nghiệp vụ tại mục 3.2.
- Model, migration, foreign key, query, fixture và tài liệu phải dùng cùng tên bảng đã chốt.

**Cài mới:** kiểm tra toàn bộ danh sách bảng package dự kiến tạo trước khi chạy migration. Nếu bảng đích đã tồn tại nhưng không xác nhận được thuộc package, dừng và liệt kê xung đột. Không ghi đè, tự nhận bảng đó hoặc bỏ qua bằng `hasTable()` rồi tiếp tục.

**Nâng cấp/cài tiếp:** bảng thuộc một installation hợp lệ đã tồn tại là bình thường. Đối chiếu migration history, phiên bản package và cấu trúc schema; chỉ chạy migration còn thiếu. Không áp dụng điều kiện “có bất kỳ bảng nào thì dừng” cho mọi lần nâng cấp. Trường hợp cài dở hoặc ownership chưa rõ phải báo để xử lý, không tự xóa bảng.

**Index và foreign key:** đặt tên ngắn, tường minh theo namespace `tai_`, kèm bảng/chức năng để tránh trùng tên, ví dụ `tai_usage_user_created_idx`, `tai_credit_tx_account_fk`. Kiểm tra độ dài tên theo database hỗ trợ; không phụ thuộc tên tự sinh quá dài.

**Nếu đã triển khai prefix cũ:** kiểm kê schema và phụ thuộc, sao lưu rồi xây migration tiến tới đổi tên bảng/constraint và cập nhật mapping. Không tạo bộ bảng mới rỗng để thay dữ liệu đang dùng; không chạy lệnh reset database. Đây là quy tắc triển khai, tài liệu này không tự thực hiện migration.


### Tutor

```text
tutor_ai_profiles
tutor_ai_conversations
tutor_ai_conversation_messages
tutor_ai_message_sources
tutor_ai_message_feedback
```

### Knowledge

```text
tutor_ai_knowledge_documents
tutor_ai_knowledge_document_versions
tutor_ai_knowledge_chunks
tutor_ai_knowledge_processing_jobs
```

### Assessment

```text
tutor_ai_assessment_rubrics
tutor_ai_assessment_rubric_versions
tutor_ai_speaking_attempts
tutor_ai_speaking_segments
tutor_ai_writing_drafts
tutor_ai_writing_submissions
tutor_ai_writing_issues
tutor_ai_assessment_scores
```

### Recommendation và audit

```text
tutor_ai_learner_skill_snapshots
tutor_ai_learner_recommendations
tutor_ai_audit_logs
```

### License local

```text
tutor_ai_license_state
tutor_ai_license_refresh_attempts
```

License document và signature có thể lưu encrypted trong một state record hoặc protected file. Phải lưu lần verify/refresh thành công và error code gần nhất.

---

## 17. API nội bộ của web khách

Prefix đề xuất: `/api/ai-tutor/v1`.

Conversation:

```text
POST /conversations
GET  /conversations
GET  /conversations/{id}
POST /conversations/{id}/messages
GET  /messages/{id}/stream
POST /messages/{id}/feedback
```

Knowledge:

```text
POST /knowledge/documents
GET  /knowledge/documents
POST /knowledge/documents/{id}/versions
POST /knowledge/document-versions/{id}/publish
```

Speaking:

```text
POST /speaking/attempts
POST /speaking/attempts/{id}/audio
POST /speaking/attempts/{id}/submit
GET  /speaking/attempts/{id}
```

Writing:

```text
POST  /writing/drafts
PATCH /writing/drafts/{id}
POST  /writing/drafts/{id}/submit
GET   /writing/submissions/{id}
```

Billing/usage admin:

```text
GET /admin/ai/usage
GET /admin/ai/credit-accounts
GET /admin/ai/credit-transactions
GET /admin/ai/providers
POST /admin/ai/providers/test
```

License admin:

```text
GET  /admin/ai/license
POST /admin/ai/license/activate
POST /admin/ai/license/refresh
```

---

## 18. Widget và tích hợp LMS

Vì Tutor AI chạy ngay trong web khách, tích hợp ưu tiên:

1. Blade component/native module cho Laravel LMS.
2. Local widget bundle cùng domain.
3. Iframe chỉ khi cần cô lập hoặc dùng giữa nhiều ứng dụng.

Blade:

```blade
<x-ai-tutor-widget :course="$course" :lesson="$lesson" />
```

JavaScript API:

```javascript
window.AITutor.open();
window.AITutor.close();
window.AITutor.setContext({ lessonId: 'lesson_36' });
window.AITutor.ask('Hãy giải thích câu này.');
```

Website phải xác thực:

- User đã đăng nhập.
- User có quyền course/lesson/question.
- Chính sách xem đáp án.
- Module/license.
- Quota/credit.

Không gửi đáp án đúng ra HTML trước khi học viên nộp.

### 18.1. Contract giữa website và package

Trong cùng ứng dụng Laravel, website gọi service/adapter nội bộ; browser gọi route cùng domain do package đăng ký. Không cần tạo API HTTP riêng chỉ để package lấy dữ liệu trong cùng ứng dụng.

Các tên dưới đây là contract thiết kế, chưa phải code đã triển khai:

```php
interface LmsContextAdapter
{
    public function canAccessLesson(string $userId, string $lessonId): bool;
    public function getLessonContext(string $userId, string $lessonId): LessonContext;
    public function getQuestionContext(string $userId, string $questionId): QuestionContext;
}
```

Website triển khai `WebsiteLmsAdapter` bằng model/policy hiện có rồi bind với interface của package trong service provider. Package cần contract riêng để resolve actor đã đăng nhập thành `LearnerIdentity`; không nhận danh tính học viên từ request body.

Adapter phải kiểm tra quyền và trả DTO chỉ chứa nội dung được phép, level/subject, course/lesson ID và answer policy. Kiểm tra question thuộc lesson/course yêu cầu; không chỉ kiểm tra một ID riêng lẻ. Không trả đáp án bị khóa cho model. Lỗi thiếu adapter phải báo rõ cho quản trị viên, không fallback sang bỏ kiểm tra quyền.

### 18.2. Luồng tương tác

1. Website đặt Blade component có namespace của package tại trang bài học, ví dụ `<x-ai-tutor::widget :course-id="$course->id" :lesson-id="$lesson->id" />`.
2. Browser gửi message và context ID tới API cùng domain; dùng phiên đăng nhập và bảo vệ CSRF phù hợp với cơ chế auth đã chọn của website.
3. Package resolve actor, kiểm tra authorization qua adapter và entitlement; tạo context an toàn.
4. `AiExecutionService` xử lý billing/provider/usage và lưu dữ liệu local.
5. UI nhận streaming hoặc trạng thái job. Đóng/mở drawer không tạo lại request AI đang xử lý.

API JavaScript `open`, `close`, `setContext`, `ask` giữ nguyên. `setContext` chỉ chọn ngữ cảnh, không cấp quyền. Với cài đặt cùng ứng dụng, không bắt buộc SSO/JWT riêng. Tích hợp ứng dụng khác domain cần thiết kế session/SSO, origin và scope riêng trước khi mở rộng.

### 18.3. Event kết quả và tùy chỉnh website

Package phát các event có version payload ổn định, ví dụ `WritingAssessmentCompleted`, `SpeakingAssessmentCompleted`, sau khi transaction kết quả đã commit. Payload gồm event ID, submission/attempt ID, actor ID, context ID, điểm tham khảo, rubric version; không chứa credential.

Website đăng ký listener để cập nhật dashboard, tiến độ hoặc thông báo. Listener phải idempotent theo event ID vì có thể retry. Lỗi listener không được làm gọi AI hoặc trừ credit lại. Điểm AI không tự trở thành điểm học vụ chính thức nếu chưa có chính sách duyệt của trung tâm.

Base 13 có thể cung cấp adapter dùng chung cho các website cùng schema. Website khác schema thay adapter, không sửa nghiệp vụ package.

---

## 19. Queue và trạng thái

Queue cho:

- Extraction/OCR/embedding.
- Speaking transcription/assessment.
- Writing assessment dài.
- Recommendation hàng loạt.

Job phải có:

- Idempotency.
- Retry/backoff.
- Timeout.
- `pending|processing|completed|failed|cancelled`.
- Error code an toàn.
- Không chứa raw API key trong serialized payload.
- Không trừ credit hai lần khi retry.

---

## 20. Giao diện

Hệ thống bắt buộc hỗ trợ hai giao diện:

- `light`: giao diện sáng.
- `dark`: giao diện tối.

Quản trị viên cấu hình được:

- Theme mặc định của toàn hệ thống.
- Có cho phép học viên tự chuyển theme hay không.
- Có sử dụng theme theo hệ điều hành/trình duyệt hay không.

Thứ tự xác định theme:

```text
Lựa chọn đã lưu của người dùng
 -> theme mặc định của hệ thống
 -> prefers-color-scheme của trình duyệt nếu bật system mode
 -> light
```

Nếu `allow_user_theme_switch=false`, bỏ qua lựa chọn cá nhân và sử dụng theme do quản trị viên cấu hình.

### 20.1. Cấu hình theme

```php
return [
    'theme' => [
        'default' => env('AI_TUTOR_DEFAULT_THEME', 'system'),
        'allow_user_switch' => env('AI_TUTOR_ALLOW_THEME_SWITCH', true),
        'available' => ['light', 'dark'],
    ],
];
```

Giá trị `default` hợp lệ:

```text
light
dark
system
```

Nếu có trang cấu hình trong database, giá trị database có thể ghi đè biến môi trường theo convention của repository.

Lựa chọn cá nhân có thể lưu vào:

- Cột preference/setting hiện có của `users`; hoặc
- Bảng `user_preferences`; hoặc
- `localStorage` đối với khách chưa đăng nhập.

Không tạo bảng riêng chỉ có một cột theme nếu hệ thống đã có cơ chế user settings dùng chung.

### 20.2. Design token

Không hard-code màu trực tiếp trong từng component. Sử dụng CSS custom properties chung.

Dark theme:

- Background `#07111F`.
- Panel `#0F1B2D`.
- Primary `#7C5CFC`.
- Accent `#27D3C2`.
- Warning vàng ấm.
- Error coral/red.

Light theme:

- Background `#F5F7FB`.
- Panel `#FFFFFF`.
- Primary `#6654E8`.
- Accent `#0FAE9E`.
- Text chính `#172033`.
- Border `#DCE3EE`.

Theme được áp dụng bằng thuộc tính:

```html
<html data-bs-theme="dark" data-ai-tutor-theme="dark">
```

hoặc:

```html
<html data-bs-theme="light" data-ai-tutor-theme="light">
```

Widget, editor Writing, biểu đồ và các modal phải dùng cùng theme hiện tại. Nếu dùng iframe, parent truyền theme qua cấu hình/`postMessage`; iframe không tự đoán theme khác parent.

Khi chuyển theme:

- Áp dụng ngay, không cần tải lại trang.
- Lưu preference nếu user đăng nhập và được phép.
- Tránh nháy sai giao diện lúc tải trang.
- Không làm mất nội dung đang chat, ghi âm hoặc soạn Writing.
- Đảm bảo độ tương phản và focus state đạt mức dễ đọc.

### 20.3. Hình thức hiển thị theo tính năng

Không sử dụng một kiểu popup/modal cho toàn bộ AI Tutor. Mỗi tính năng dùng hình thức phù hợp với độ dài và ngữ cảnh thao tác.

| Tính năng | Hình thức mặc định |
|---|---|
| Hỏi nhanh trong bài học | Nút nổi mở drawer/panel bên phải |
| Hội thoại Tutor AI đầy đủ | Trang riêng hoặc mở rộng từ drawer |
| Speaking | Trang riêng |
| Writing | Trang riêng |
| Lộ trình, lịch sử, thống kê | Trang riêng |
| Xác nhận, cảnh báo, chọn cấu hình ngắn | Modal giữa màn hình |

#### Chat trong bài học

- Hiển thị launcher ở góc dưới bên phải theo mặc định.
- Cho phép quản trị viên chuyển sang góc dưới bên trái.
- Khi bấm launcher, mở drawer/panel từ cạnh tương ứng.
- Desktop: panel mặc định rộng `420px`, cho phép cấu hình trong khoảng hợp lý.
- Tablet: drawer chiếm khoảng `45–60%` chiều rộng.
- Mobile: mở toàn màn hình.
- Có nút đóng, thu gọn và “Mở rộng” sang trang chat riêng.
- Không dùng modal giữa màn hình cho hội thoại chính vì sẽ che bài học.
- Khi mở rộng/thu gọn, phải giữ conversation, draft message, citation và trạng thái streaming.
- Launcher không được che nút điều khiển video, nút nộp bài hoặc thanh điều hướng mobile.

Vị trí hợp lệ:

```text
bottom-right
bottom-left
```

Chế độ chat bài học hợp lệ:

```text
drawer
floating_panel
full_page
```

Mặc định:

```text
launcher_position = bottom-right
lesson_chat_mode = drawer
mobile_mode = fullscreen
```

#### Speaking

Speaking bắt buộc ưu tiên trang riêng vì cần:

- Đề bài và thời gian chuẩn bị.
- Quyền microphone.
- Trạng thái ghi âm và waveform.
- Transcript.
- Điểm và evidence.
- Nghe lại, luyện từ và thử lại.

Route gợi ý:

```text
/ai-tutor/speaking/{attempt}
```

#### Writing

Writing bắt buộc ưu tiên trang riêng để có editor, autosave, word count, highlight lỗi và panel nhận xét.

Desktop dùng bố cục hai cột:

```text
Editor bài viết | Điểm, lỗi và gợi ý AI
```

Mobile chuyển thành tab/section, không ép hai cột.

Route gợi ý:

```text
/ai-tutor/writing/{draft}
```

#### Dashboard, lộ trình và lịch sử

Sử dụng trang riêng vì đây là dữ liệu cần điều hướng, lọc và xem lại:

```text
/ai-tutor
/ai-tutor/history
/ai-tutor/learning-path
```

#### Modal giữa màn hình

Chỉ dùng modal cho tác vụ ngắn:

- Xác nhận nộp bài.
- Xác nhận xóa.
- Thông báo số credit dự kiến.
- Chọn chế độ chấm.
- Chọn/kiểm tra microphone.
- Cảnh báo chưa cấu hình provider/API key.

Modal không được dùng làm giao diện chính cho chat, Speaking hoặc Writing.

### 20.4. Cấu hình hình thức hiển thị

```php
return [
    'ui' => [
        'launcher_position' => env('AI_TUTOR_LAUNCHER_POSITION', 'bottom-right'),
        'lesson_chat_mode' => env('AI_TUTOR_LESSON_CHAT_MODE', 'drawer'),
        'desktop_panel_width' => (int) env('AI_TUTOR_DESKTOP_PANEL_WIDTH', 420),
        'mobile_mode' => env('AI_TUTOR_MOBILE_MODE', 'fullscreen'),
        'allow_expand_to_page' => env('AI_TUTOR_ALLOW_EXPAND_TO_PAGE', true),
        'speaking_mode' => 'page',
        'writing_mode' => 'page',
        'dashboard_mode' => 'page',
    ],
];
```

Quản trị viên được phép ghi đè các cấu hình phù hợp qua trang cài đặt. Backend phải validate enum và giới hạn chiều rộng panel; không đưa giá trị chưa kiểm tra trực tiếp vào CSS.

### 20.5. Bốn màn chính

1. Gia sư AI trong bài học.
2. Speaking với AI.
3. Writing với AI.
4. Lộ trình A1 → B2 → TOEIC/IELTS.

Mọi màn tốn AI phải hiển thị khi phù hợp:

- Quota/credit còn lại.
- Trạng thái xử lý.
- Retry.
- Provider connection error dạng thân thiện.
- Billing mode chỉ hiển thị cho admin, không gây nhiễu học viên.

---

## 21. Bảo mật và riêng tư

- API key chỉ ở backend.
- Credential encrypted at rest.
- Authorization bằng policy/service.
- Rate limit theo user/IP/feature.
- Validate MIME/size và quét upload.
- Signed upload hoặc backend upload.
- Redact secret và dữ liệu nhạy cảm trong log.
- Không ghi full prompt/response vào application log.
- Nội dung nghiệp vụ nằm trong bảng có phân quyền và retention.
- Tool call dùng allowlist/schema.
- Model không tự gọi URL tùy ý.
- Có retention/export/delete cho hội thoại, audio và bài viết.
- Audit thay đổi API credential, credit rule, rubric, prompt và license.

---

## 22. Observability

Mỗi request có `request_id` xuyên API, queue, provider và usage.

Theo dõi:

- Latency và time to first token.
- Provider error/rate limit.
- Token/audio/image/page usage.
- Estimated cost.
- Credit reserve/commit/release.
- Queue depth/failure.
- RAG hit/no result.
- Speaking/Writing completion.
- License refresh và thời gian grace còn lại.
- Vendor Gateway error khi mode tương ứng được bật.

---

## 23. Testing

Unit:

- BillingManager resolver.
- CustomerKeyBillingDriver.
- VendorCreditBillingDriver placeholder.
- Credit calculator và ledger.
- Usage recorder.
- Idempotency.
- License signature verifier.
- Entitlement resolver.
- Prompt/context builder.

Feature:

- API key customer gọi provider mock.
- Hết quota không gọi provider.
- Retry không trừ credit hai lần.
- Provider lỗi sẽ release reservation.
- Usage record được lưu ở `customer_key`.
- Mode chưa hỗ trợ trả error ổn định.
- License cache/grace hoạt động khi License API lỗi.
- Module bị tắt trả 403.
- User không truy cập course/lesson ngoài quyền.

Integration:

- Mock provider/Gateway; test mặc định không gọi AI thật.
- SSE.
- Queue retry.
- Activation/refresh license với fake signed document.

Security:

- Invalid signature.
- Domain/installation mismatch.
- ID enumeration.
- Upload MIME giả.
- API key không xuất hiện trong response/log/job payload.

---

## 24. Biến môi trường

```dotenv
AI_TUTOR_ENABLED=true
AI_BILLING_MODE=customer_key
AI_DEFAULT_PROVIDER=openai
AI_DEFAULT_CHAT_MODEL=
AI_DEFAULT_EMBEDDING_MODEL=
AI_STREAMING_DRIVER=sse
AI_DAILY_CREDITS=10
AI_TUTOR_DEFAULT_THEME=system
AI_TUTOR_ALLOW_THEME_SWITCH=true
AI_TUTOR_LAUNCHER_POSITION=bottom-right
AI_TUTOR_LESSON_CHAT_MODE=drawer
AI_TUTOR_DESKTOP_PANEL_WIDTH=420
AI_TUTOR_MOBILE_MODE=fullscreen
AI_TUTOR_ALLOW_EXPAND_TO_PAGE=true

OPENAI_API_KEY=
GEMINI_API_KEY=

AI_LICENSE_SERVER_URL=https://license.example.vn
AI_LICENSE_KEY=
AI_LICENSE_PUBLIC_KEY_PATH=/secure/path/license-public.key
AI_LICENSE_INSTALLATION_ID=
AI_LICENSE_REFRESH_HOURS=24
AI_LICENSE_GRACE_DAYS=7

AI_VENDOR_GATEWAY_URL=
AI_VENDOR_GATEWAY_TIMEOUT_SECONDS=60

AI_AUDIO_RETENTION_DAYS=30
AI_CONVERSATION_RETENTION_DAYS=365
```

Chỉ validate credential của provider/billing mode đang dùng. Không yêu cầu Vendor Gateway config trong `customer_key`.

---

## 25. Error code ổn định

```text
AI_DISABLED
AI_PROVIDER_NOT_CONFIGURED
AI_PROVIDER_AUTH_FAILED
AI_PROVIDER_RATE_LIMITED
AI_PROVIDER_UNAVAILABLE
AI_REQUEST_DUPLICATE
AI_DAILY_LIMIT_REACHED
AI_CREDIT_INSUFFICIENT
BILLING_MODE_NOT_SUPPORTED
VENDOR_GATEWAY_UNAVAILABLE
VENDOR_CREDIT_INSUFFICIENT
LICENSE_INVALID
LICENSE_EXPIRED
LICENSE_GRACE_EXPIRED
LICENSE_MODULE_NOT_ALLOWED
LICENSE_DOMAIN_MISMATCH
LICENSE_INSTALLATION_MISMATCH
```

Không trả raw provider error hoặc secret cho client.

---

## 26. Trình tự triển khai

### Phase 1 — Foundation

- Khởi tạo một Composer package theo mục 5; config và module boundaries.
- Contract adapter/actor và fake adapter cho kiểm thử, skeleton event và pipeline asset.
- Provider contracts và mock provider.
- `AiRequest`, `AiResponse`, `AiExecutionService`.
- BillingManager.
- CustomerKeyBillingDriver.
- VendorCreditBillingDriver placeholder.
- Credit account/rule/transaction.
- Requests, usage và cost snapshot.
- Test idempotency/reserve/commit/release.

### Phase 2 — Signed offline license

- Installation identity.
- Activation/refresh client.
- Ed25519 verifier.
- Cache, scheduler, retry và grace.
- Entitlement middleware/service.
- License admin status.

### Phase 3 — Knowledge và Tutor Core

- Local document/version/chunk/vector.
- Conversation/message/source.
- Streaming.
- Teaching mode và answer policy.

### Phase 4 — English MVP

- CEFR/TOEIC/IELTS.
- Speaking.
- Writing.
- Giao diện Light/Dark và drawer/trang riêng theo mục 20.

### Phase 5 — Analytics và Recommendation

- Usage/cost dashboard.
- Teacher dashboard.
- Skill snapshots.
- Rule-based recommendations.

### Phase 6 — Vendor Credit

- Vendor AI Gateway.
- Authentication/request signing.
- Vendor wallet/ledger.
- Reserve/commit/release từ xa.
- Reconciliation.
- Hoàn thiện VendorCreditBillingDriver.
- Không sửa public contract của Tutor/Speaking/Writing.

---

## 27. Definition of Done

Một feature AI chỉ hoàn thành khi:

1. Đi qua `AiExecutionService`.
2. Không gọi SDK AI trực tiếp từ controller/module.
3. Kiểm tra entitlement.
4. Kiểm tra quota/credit.
5. Có idempotency.
6. Lưu `tutor_ai_requests`.
7. Lưu `tutor_ai_usage_records` khi phát sinh usage, kể cả `customer_key`.
8. Reserve/commit/release đúng.
9. Không lộ credential.
10. Có authorization và validation.
11. Có loading/error/retry state.
12. Có test bằng database testing an toàn.
13. Không phụ thuộc License Server theo từng request.
14. Có tài liệu config/env/error code liên quan.
15. Giao diện hoạt động đúng ở cả light và dark theme.
16. Theme switch không làm mất state của Tutor, Speaking hoặc Writing.
17. Chat bài học dùng drawer/panel hoặc full page theo cấu hình, không dùng modal chính.
18. Speaking, Writing, dashboard và lịch sử có trang riêng.
19. Chuyển drawer sang trang đầy đủ không làm mất conversation hoặc trạng thái đang xử lý.
20. Launcher không che nội dung hoặc điều khiển quan trọng trên desktop/mobile.
21. Tất cả bảng package dùng `tutor_ai_`; kiểm tra xung đột cài mới và nâng cấp được tách biệt, không ghi đè bảng khách.
22. Index/foreign key dùng tên ngắn rõ ràng và được kiểm tra xung đột.
23. Tích hợp LMS qua contract; không gắn cứng model website trong package.
24. Thay đổi phát hành có migration tiến tới, changelog và hướng dẫn nâng cấp theo mục 31.

---

## 28. Anti-pattern bị cấm

```php
// Cấm: gọi provider trực tiếp trong controller.
OpenAI::chat()->create(...);

// Cấm: module tự đọc mode và tự phân nhánh.
if (env('AI_BILLING_MODE') === 'vendor_credit') { ... }

// Cấm: trừ credit không có idempotency/transaction.
$account->decrement('balance', 1);

// Cấm: gọi License API trong mỗi message.
$licenseApi->checkBeforeEveryAiRequest();
```

Luồng đúng:

```php
$aiExecutionService->execute($request);
```

---

## 29. Quyết định đã chốt

- V1 dùng một Composer package chia module nội bộ; website tích hợp bằng adapter, component và event.
- Cập nhật qua Composer với lock file đã kiểm tra; V1 chỉ thông báo có bản mới, không tự nâng cấp qua admin.

- Prefix bảng tại web khách cố định là `tutor_ai_`; License Server giữ database và tên bảng riêng.

- Đây là sản phẩm self-hosted, không phải SaaS AI tập trung.
- Dữ liệu học viên và AI mặc định nằm trên server khách.
- Khách tự dùng API key trong V1.
- `AI_BILLING_MODE=customer_key` là mặc định.
- `AI_BILLING_MODE=vendor_credit` làm sau nhưng kiến trúc V1 phải tương thích.
- Customer key vẫn lưu đầy đủ usage và credit history tại web khách.
- `tutor_ai_usage_records` lưu usage thực tế; không phải bảng số dư.
- `tutor_ai_credit_transactions` là ledger cộng/trừ/giữ/hoàn.
- License dùng Ed25519 signed document, verify offline.
- License refresh nền mỗi 24 giờ và grace mặc định 7 ngày.
- License Server không lưu dữ liệu học tập hoặc API key khách.
- Không kiểm tra license theo từng câu hỏi AI.
- Tutor, Speaking, Writing, RAG không biết billing driver cụ thể.
- Không tự động fallback giữa hai billing mode.
- Ưu tiên tiếng Anh A1–B2, TOEIC, IELTS, Speaking và Writing.
- Hệ thống hỗ trợ cả light và dark theme.
- Quản trị viên cấu hình theme mặc định và quyền cho học viên tự chuyển theme.
- Mặc định đề xuất `system`; dark dùng hệ navy–violet–cyan.
- Chat trong bài học mặc định là launcher góc dưới bên phải mở drawer.
- Launcher có thể chuyển sang góc dưới bên trái bằng cấu hình.
- Mobile mở chat toàn màn hình; desktop dùng panel mặc định `420px`.
- Speaking, Writing, dashboard, lộ trình và lịch sử sử dụng trang riêng.
- Modal giữa màn hình chỉ dùng cho xác nhận, cảnh báo và cấu hình ngắn.

---

## 30. Mẫu lệnh cho Codex

```text
Đọc toàn bộ tutor-ai-spec-v2.md trước.
Kiểm tra repository hiện tại và lập kế hoạch triển khai Phase 1 theo file.
Chỉ triển khai CustomerKeyBillingDriver; tạo VendorCreditBillingDriver dạng placeholder.
Mọi feature phải đi qua AiExecutionService.
Không triển khai Tutor/Knowledge/UI trong lần này.
Không chạy test trên database dev.
```

Hoặc:

```text
Đọc tutor-ai-spec-v2.md.
Triển khai Phase 2 signed offline license bằng Ed25519.
License cache phải tiếp tục hoạt động trong grace period khi API license lỗi.
Không gửi dữ liệu học viên về License Server.
```

---

## 31. Phát hành và cập nhật package tại web khách

### 31.1. Version và tương thích

- Dùng version phát hành riêng của package theo dạng MAJOR.MINOR.PATCH; version tài liệu không phải version package.
- PATCH sửa lỗi tương thích; MINOR thêm tính năng tương thích; MAJOR dành cho thay đổi phá vỡ contract/schema/config.
- Mỗi release có changelog, yêu cầu PHP/Laravel/database, hướng dẫn migration/asset và lưu ý view override.
- Khai báo giới hạn tương thích thực tế trong `composer.json` và kiểm tra với Laravel Base trước phát hành.
- Giữ adapter contract, DTO và event tương thích trong cùng major; thêm contract mới hoặc deprecation khi cần thay đổi.

### 31.2. Phân phối và quyền tải

- Phát hành tag/artifact qua private repository; quản lý credential tải package phía triển khai, không đưa vào repository hoặc browser.
- License Server có thể trả phiên bản mới nhất khách đủ quyền tải, changelog và điều kiện nâng cấp.
- Quyền download update và quyền runtime là hai quyền riêng. Lỗi kiểm tra update không khóa bản đang chạy còn license hợp lệ.
- V1 chỉ hiển thị thông báo có bản mới trong admin. Không có nút tự chạy Composer/migration bằng HTTP trên production.
- Không tải và thực thi archive từ URL tùy ý. Khi có signed artifact/checksum, xác minh trước triển khai theo cơ chế phát hành đã chọn.

### 31.3. Quy trình nâng cấp đề xuất

1. Đọc changelog và kiểm tra phiên bản tương thích; chuẩn bị backup database, uploads, cấu hình và khóa mã hóa theo quy trình khách.
2. Tại development/staging, cập nhật package, review dependency diff và chốt `composer.lock`.
3. Chạy migration tiến tới, build/cập nhật asset theo release, kiểm thử adapter và các luồng AI bằng provider mock.
4. Đưa mã website cùng lock file đã kiểm tra lên production. Cài đúng lock file, không resolve version mới trực tiếp trên production.
5. Theo kế hoạch release, chạy migration, cập nhật cache và restart worker bằng cơ chế phù hợp hạ tầng. Dùng maintenance/drain job nếu thay đổi không tương thích với request đang chạy.
6. Smoke check bài học/widget, Speaking/Writing, license, credit và queue; ghi nhận version đang triển khai.

Lệnh minh họa ở staging (tên package thay bằng tên thực):

```bash
composer update your-vendor/ai-tutor --with-dependencies
```

Lệnh cài dependency theo lock file trên production:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

Không tự chạy các lệnh production trong quá trình chỉ viết code/đặc tả. Runbook của release phải liệt kê chính xác migration, cache và worker command cần thiết cho repository/hạ tầng thực tế.

### 31.4. Database và cấu hình khi nâng cấp

- Migration package đã phát hành là bất biến; thay đổi schema bằng migration mới, tuyệt đối không reset dữ liệu.
- Kiểm tra upgrade path từ bản trước có dữ liệu, không chỉ kiểm tra cài database trống.
- Thay đổi lớn ưu tiên mở rộng schema, backfill có thể tiếp tục, chuyển code rồi mới loại bỏ cấu trúc cũ trong release được lên kế hoạch.
- Settings khách đã lưu và file config tùy chỉnh phải được giữ; merge default mới và tài liệu hóa giá trị cần thêm. Không publish đè config bằng force khi nâng cấp thường lệ.
- Nếu migration thất bại, dừng rollout, kiểm tra trạng thái trước retry. Không mặc định rollback schema; phục hồi phải xét tương thích code/schema và dữ liệu phát sinh sau backup.

### 31.5. Giao diện và asset

V1 chọn pipeline asset qua Vite của website: package cung cấp JS/SCSS entry có tài liệu import rõ ràng, website build vào artifact triển khai. Build trên CI/staging; production không bắt buộc có Node khi nhận asset đã build.

Chỉ chạy `npm run build` sau khi website đã nối entry package vào Vite. Nếu tương lai chuyển sang phân phối asset dựng sẵn, release phải nêu lệnh publish/version cache cụ thể; không giả định Composer tự cập nhật mọi asset hoặc view đã publish.

### 31.6. Tiêu chí phát hành

- Có changelog, upgrade guide và compatibility matrix.
- Kiểm tra cài mới, nâng cấp từ bản trước có dữ liệu, collision detection và adapter compatibility.
- Credit/history/API key/config khách vẫn nguyên vẹn sau nâng cấp.
- UI Light/Dark, drawer/full page và asset version hoạt động đúng.
- Có kế hoạch phục hồi phù hợp schema; không hứa rollback code luôn đủ.

---

**Kết thúc đặc tả V2.**
