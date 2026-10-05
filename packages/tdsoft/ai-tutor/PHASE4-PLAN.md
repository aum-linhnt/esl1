# Kế hoạch Phase 4 — English MVP

Ngày lập: 2026-10-05. Trạng thái: kế hoạch triển khai, chưa triển khai Phase 4.

## 1. Mục tiêu và căn cứ

Phase 4 theo `tutor-ai-spec-v2.md`, mục 14, 20, 26 và 27: module tiếng Anh
CEFR/TOEIC/IELTS, Speaking, Writing và giao diện light/dark. Đây không phải Phase 4
Form Request Validation trong `task.md` (đã hoàn thành).

Kết quả mong muốn: học viên soạn bài/thu âm, gửi đánh giá có kiểm soát credit,
theo dõi trạng thái và xem feedback có evidence. Điểm AI là điểm tham khảo;
không tự ghi điểm học vụ cuối cùng. Mọi inference đi qua
`AiExecutionService -> BillingManager`; package độc lập LMS qua contract.

## 2. Hiện trạng ảnh hưởng đến thiết kế

- Phase 3 có actor/context adapter, signed license, credit ledger, recovery,
  queue background actor, theme/widget và conversation retention để tái sử dụng.
- Config đã khai báo `speaking_transcription`, `speaking_assessment`,
  `writing_assessment`, `writing_recheck` và entitlement tương ứng.
- `OpenAiProvider` hiện chỉ xử lý `tutor_message` và `knowledge_embedding`.
  `SpeechToTextProviderInterface` mới là marker interface; chưa có triển khai STT.
- `AiResponse` có `audio_seconds`, hiện yêu cầu số nguyên. Cần chốt quy tắc
  làm tròn và nguồn usage trước khi bổ sung billing audio.
- Các event `WritingAssessmentCompleted`, `SpeakingAssessmentCompleted` đã có
  skeleton và dispatch-after-commit; cần giữ tương thích payload version 1.
- Website có `WritingPracticeController`, `SpeakingPracticeController`,
  `AiWritingService`, `AiSpeakingService` và view cũ. Luồng này gọi Gemini/Speech AI
  trực tiếp, có fallback điểm giả và thưởng XP/coin ngay sau gọi service.
  Không dùng chúng làm assessment engine mới.
- `PHASE3.md` ghi nhận full website suite còn 5 failure/1 error; browser E2E và
  MySQL concurrency chưa thực hiện. Đây là baseline được ghi nhận, chưa xác minh lại.

## 3. Phạm vi MVP

| Nhánh | Phạm vi |
| --- | --- |
| English | Cấu hình mục tiêu CEFR A1–B2, TOEIC 450+/650+/800+, IELTS Foundation/5.5/6.5/7.0+; ngôn ngữ feedback vi/en/song ngữ; prompt/rubric có version |
| Writing | CEFR writing, IELTS Task 1/2; draft autosave, word count, revision, submit, structured feedback, sửa từng lỗi, lịch sử cá nhân |
| Speaking | CEFR conversation, IELTS Part 1/2/3; thu âm, playback, upload private, STT, transcript, assessment theo evidence, lịch sử cá nhân |
| Tích hợp | Trang riêng, navigation từ LMS, entitlement, credit/recovery, event sau commit; light/dark và mobile |

TOEIC là cấu hình mục tiêu và ngữ cảnh luyện tiếng Anh trong MVP; không suy ra
điểm TOEIC chính thức từ Writing/Speaking hay quy đổi tuyến tính CEFR/IELTS/TOEIC.
IELTS Task 1 dùng đề văn bản với dữ liệu mô tả đã duyệt; input ảnh/biểu đồ và phân
tích thị giác tách sang follow-up. Các rubric là rubric luyện tập có version.

Ngoài phạm vi: realtime voice, TTS, chứng nhận điểm thi, tự cập nhật gradebook,
analytics/recommendation Phase 5, vendor gateway Phase 6, PDF/DOCX/PPTX/OCR,
chuyển toàn bộ dữ liệu assessment cũ. Upload audio là luồng riêng, không mở lại
binary document ingestion đã chủ động hoãn ở Phase 3.

## 4. Thứ tự triển khai

### Bước 0 — Baseline và chốt contract

- Chạy package suite và các website suite liên quan trên database testing an toàn;
  ghi baseline full suite, phân loại lỗi cũ và sửa lỗi chặn phạm vi Phase 4.
- Rà routes Speaking/Writing cũ, consumer API và các question handler đang dùng
  service cũ; lập mapping chuyển đổi để tránh bỏ sót đường gọi provider trực tiếp.
- Chốt schema output, state machine, rubric/scoring, giới hạn text/audio và
  provider capabilities. Kiểm tra tài liệu provider chính thức khi triển khai;
  không giả định model, timestamp, pronunciation evidence hoặc giá dịch vụ.
- Chốt cấu hình retention bài viết/audio và consent upload; mặc định automation
  xóa dữ liệu tắt cho đến khi quản trị viên cấu hình.

Điều kiện hoàn thành: contract và route mapping rõ ràng; không còn quyết định
về scoring/billing audio bị ẩn trong controller.

### Bước 1 — English và Assessment foundation

- Thêm module `SubjectEnglish/`, `Assessment/`, `Writing/`, `Speaking/` trong package.
  English chứa profile/prompt/rubric; không sao chép billing hoặc provider.
- Forward migration cho `tutor_ai_assessment_rubrics`,
  `tutor_ai_assessment_rubric_versions`, `tutor_ai_assessment_scores`,
  `tutor_ai_writing_drafts`, `tutor_ai_writing_submissions`,
  `tutor_ai_writing_issues`, `tutor_ai_speaking_attempts`,
  `tutor_ai_speaking_segments`; revision của draft và metadata audio có thể dùng
  bảng bổ sung khi chốt schema, tất cả cùng prefix `tutor_ai_`.
- Version rubric bất biến; mỗi submission/attempt lưu actor, context, task,
  original/revision snapshot, rubric/prompt version, provider/model/request ID,
  usage, scores và evidence. Không sửa migration cũ hoặc bảng assessment website.
- State đề xuất: `draft -> queued -> processing -> completed/failed/reconciliation_required`.
  Speaking thêm `audio_ready` trước queue. Lưu trạng thái từng stage độc lập;
  chưa hoàn thành cả pipeline thì chưa phát event assessment completed.
- Mở rộng schema-check/preflight và test cài mới/nâng cấp; dùng index tên ngắn.
- Rubric mẫu được seed có version; không tự grant credit hay thiết lập giá.

Điều kiện hoàn thành: snapshot không đổi khi sửa rubric/draft; schema upgrade
không ghi đè dữ liệu hiện có; authorization owner/context nhất quán.

### Bước 2 — Writing backend trước

- Draft CRUD có owner check, revision number và optimistic concurrency;
  autosave cũ không được ghi đè revision mới. Submit snapshot revision cụ thể.
- Job chỉ mang submission ID; worker resolve actor từ record, kiểm tra lại
  quyền/context/license và gọi `AiExecutionService` với feature phù hợp.
- Mở rộng provider cho structured assessment; validate JSON, score range,
  criteria/evidence và issue offsets phía server. Nội dung essay là dữ liệu,
  không được dùng để thay system instruction/rubric.
- Offset highlight phải xác định đơn vị và kiểm tra trên original snapshot;
  đề xuất UTF-16 để khớp editor JS, có test Unicode/emoji. Feedback không có
  span hợp lệ vẫn có thể hiển thị dạng danh sách, không áp sửa sai vị trí.
- Lưu grammar/vocabulary/coherence/task response/style theo rubric; sửa từng
  lỗi tạo revision, không tự thay toàn bài. Recheck là thao tác chủ động có
  snapshot và request ID mới, dùng `writing_recheck`, có thông báo credit.
- Validation output lỗi không tạo điểm thành công. Nếu provider đã phát sinh
  usage, giữ usage/settlement và raw result mã hóa để phục hồi nội bộ;
  không trả tiền hoặc gọi lại provider chỉ vì JSON không hợp lệ.
- Event sau commit; reward listener phía LMS idempotent theo event/assessment ID.

Điều kiện hoàn thành: submit/retry/double-click không gọi AI hoặc thưởng hai lần;
provider lỗi không sinh điểm mặc định; revision cũ luôn xem lại được.

### Bước 3 — Writing UI và tích hợp LMS

- Trang `/ai-tutor/writing` và `/ai-tutor/writing/{draft}`: chọn task/target,
  editor, word count, autosave status/conflict, submit, trạng thái queue,
  feedback panel, highlight, apply từng lỗi và lịch sử có phân trang.
- API session dưới `/ai-tutor/api/v1`: `POST /writing/drafts`,
  `GET/PATCH /writing/drafts/{id}`, `POST /writing/drafts/{id}/submit`,
  `GET /writing/submissions/{id}`; bổ sung list/recheck/export/delete khi cần.
- Loading/error/recovery theo durable status. Mất mạng giữ request IDs;
  uncertain outcome chỉ refresh/reconciliation. New paid attempt phải là
  thao tác xác nhận, không tự retry bằng request ID khác.
- Render text an toàn, cùng theme website; đổi theme không mất draft/state.
  Hiển thị draft local chưa đồng bộ rõ ràng; local cache tách theo actor/draft,
  xóa khi logout/delete để tránh nội dung người dùng trước trên thiết bị dùng chung.

Điều kiện hoàn thành: reload mở lại draft server, hai tab không làm mất bài;
mobile/light/dark dùng được; credit/recovery rõ ràng.

### Bước 4 — Speaking backend và audio pipeline

- Browser upload multipart vào private storage khách; không nhận arbitrary
  audio URL, server path hoặc base64 không giới hạn. Xác thực MIME/container,
  dung lượng, duration thực tế phía server, và chống file giả/âm thanh hỏng.
- Đề xuất ban đầu: tối đa 20 MB/5 phút cho một recording, cấu hình được và
  giới hạn theo task. Đây là product limit đề xuất, cần kiểm tra với provider.
- Nếu cần decode/transcode, chạy công cụ trong worker có timeout/resource limit;
  không shell interpolate dữ liệu người dùng. Chỉ bật upload sau khi dependency
  kiểm tra audio đã có và được kiểm thử; file lỗi không tới provider.
- Bổ sung STT adapter và capability declaration qua provider pipeline hiện có.
  Stage 1 `speaking_transcription`; stage 2 `speaking_assessment`, mỗi stage
  một request ID ổn định. Hoàn thành STT rồi assessment lỗi thì tái dùng transcript,
  không transcribe hoặc charge STT lại. Recheck license/quyền ở từng stage.
- Chuẩn hóa transcript/segments/timestamps khi provider hỗ trợ; phân biệt usage
  provider với duration đo local. Quy tắc audio_seconds làm tròn phải nhất quán
  trong DTO, rule snapshot và test; không tự chế giá provider.
- Transcript đủ evidence cho vocabulary/grammar; pronunciation/prosody cần
  acoustic evidence, fluency cần timing/audio evidence phù hợp. Criterion thiếu
  evidence là `not_available`, score null; không tính overall giả từ rubric thiếu
  tiêu chí bắt buộc. Problem words chỉ ghi khi có evidence tương ứng.
- Chặn inference trong exam/no-answer context; đánh giá sau thi chỉ khi LMS
  cho phép rõ ràng. Feedback Speaking không được trở thành đường lấy đáp án.

Điều kiện hoàn thành: private audio không truy cập chéo owner; stage retry không
thu phí lặp; thiếu evidence không trả điểm pronunciation/fluency bịa.

### Bước 5 — Speaking UI

- Trang `/ai-tutor/speaking` và `/ai-tutor/speaking/{attempt}`: chọn task,
  consent/microphone permission, record/stop/playback/re-record, upload,
  transcript, criteria/evidence, problem words khi có và bài luyện tiếp theo.
- API cùng prefix session: `POST /speaking/attempts`,
  `POST /speaking/attempts/{id}/audio`, `POST /speaking/attempts/{id}/submit`,
  `GET /speaking/attempts/{id}`; list/export/delete có owner check.
- Kiểm tra browser capability/MIME, quyền mic bị từ chối, HTTPS requirement,
  mobile và ngắt mạng. Theme switch không recreate recorder hoặc mất recording.
  Re-record không ghi đè audio của attempt đã submit; tạo attempt mới rõ ràng.
- Hiển thị từng stage, khả năng phát sinh hai khoản credit, pending recovery
  và trường hợp audio/transcript/evidence không đủ đánh giá.

Điều kiện hoàn thành: recording/playback/submit hoạt động trong browser thật;
không tạo job/provider call trùng khi người dùng nhấn nhiều lần.

### Bước 6 — Chuyển luồng cũ, dữ liệu và release

- Navigation cũ chuyển sang trang package; đánh giá qua endpoint cũ phải delegate
  engine mới hoặc đóng có thông báo migration. Rà cả `/api/v1/assess*`, activity
  handlers và các caller khác; không để đường legacy bỏ qua license/credit.
- Không chuyển silent fallback điểm cũ vào engine mới; dữ liệu lịch sử cũ giữ
  nguyên và đánh dấu legacy khi hiển thị. Compatibility changes có changelog.
- Export/delete cho bài viết/audio; thao tác xóa kiểm tra owner, chặn busy/unsettled,
  giữ ledger/usage. Xử lý cả encrypted result/prompt replay liên quan để tránh
  xóa nội dung ở bảng module nhưng còn bản sao trong request. Audio retention
  có dry-run; automated deletion opt-in; ghi rõ ảnh hưởng của backup/export.
- Rule admin hỗ trợ các feature Phase 4; không auto-fund. Kiểm tra quota hiện có,
  bổ sung enforcement cần thiết trước khi tuyên bố có giới hạn phút/lượt.
- Viết config/env/error codes, queue/storage/rubric runbook, UPGRADE/CHANGELOG.
  Build assets, backup, forward migration staging, restart workers và smoke test.

Điều kiện hoàn thành: mọi entry point dùng guard/pipeline mới; tài liệu chuyển
đổi rõ ràng; triển khai không mất lịch sử hoặc tự phát sinh provider usage.

## 5. Verification và Definition of Done

- Package: schema upgrade, entitlement/owner/context revoked, snapshot/version,
  invalid structured result, privacy/export/delete, event sau commit.
- Billing/queue: duplicate submit/job, STT completed + assessment failed,
  crash trước/sau settlement, uncertain transport, cùng-ID replay và confirmed
  new attempt; không double charge/reward và không tự reset reservation.
- Website: routes/session CSRF (không đặt endpoint ghi mới dưới `/api/*` đang
  miễn CSRF), navigation, policy/exam, compatibility các caller legacy.
- JS: autosave race/conflict, Unicode highlight, apply fix, recovery và theme.
- Browser E2E: Writing đầy đủ; microphone/audio Speaking, mobile, light/dark,
  disconnect/reload. MySQL concurrency: double submit, queue collision và
  autosave conflict; SQLite không thay thế kiểm tra locking này.
- CI dùng fake provider và database testing, chặn HTTP ngoài. Staging smoke
  provider thật chỉ dùng tài khoản/model/credit được cấu hình rõ ràng;
  không xem fake test là chứng minh STT/evidence hoạt động với provider thật.
- Release gate: suite phạm vi Phase 4 pass; full suite không thêm regression,
  lỗi baseline còn lại được ghi rõ; không điểm giả, credential leak, public audio,
  bypass license/credit hoặc tự ghi gradebook.

## 6. Các mốc bàn giao

1. **M1:** baseline + English/rubric/schema + Writing engine và billing tests.
2. **M2:** Writing UI/end-to-end + autosave/recheck/recovery.
3. **M3:** audio validation/storage + STT/assessment stages và evidence tests.
4. **M4:** Speaking UI/browser verification + chuyển caller legacy.
5. **M5:** retention/export/delete + MySQL concurrency + staging runbook/release gate.

Thứ tự phụ thuộc: M1 -> M2; M3 dùng foundation M1; M4 sau M3; M5 sau M2/M4.
Ưu tiên bắt đầu Bước 0–1 rồi Writing. Không cam kết ngày release trước khi kiểm
tra capability provider/audio infrastructure và baseline test thực tế.
