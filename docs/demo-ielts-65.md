# Khóa mẫu IELTS 6.5 cho demo khách hàng

Chọn `[DEMO] IELTS 6.5 — Luyện 4 kỹ năng` (slug `demo-ai-tutor-ielts`). Trên database local hiện tại: `/courses/4`, quản lý: `/teacher/ai-tutor?course_id=4`.

## Chuẩn dữ liệu

Khóa có 5 bài: Listening, Reading, Writing Task 1, Writing Task 2 và Speaking. Mỗi bài gồm tài liệu đọc, quiz 3 câu có đáp án/giải thích và bài tập nộp văn bản. Sau khi ghép media: tổng cộng 27 hoạt động và 15 câu quiz, gồm 2 audio, 5 video có phụ đề tiếng Anh và 5 PDF bài tập. Nội dung luyện tập tự tạo, rút gọn; không phải đề thi IELTS chính thức.

Listening có transcript đặt lịch học; Reading có bài đọc đầy đủ về thư viện. Writing Task 1 có bảng số liệu ba lớp học; Task 2 có đề về học trực tuyến. Speaking có câu hỏi, gợi ý dàn ý và câu hỏi mở rộng. Các bài nộp mô phỏng ban đầu được thay bằng ví dụ liên quan đến đề; bài thật hoặc bài đã sửa được giữ nguyên.

Khóa mẫu được mở cho luồng học viên, tắt tự ghi danh. Các khóa demo còn lại không thay đổi. Học viên cần được ghi danh; bài tiếp theo mở sau khi hoàn thành các hoạt động của bài trước.

## Chuẩn bị local

```bash
python3 scripts/build-ielts-demo-media.py
php artisan db:seed --class="Database\Seeders\AiTutorDemo\AiTutorIeltsMediaDemoSeeder"
php artisan demo:ielts-student <email-hoc-vien>
```

Lệnh chuẩn bị học viên tạo tài khoản local nếu chưa tồn tại, hiển thị mật khẩu tạm đúng một lần, ghi danh và đặt mục tiêu IELTS 6.5 nếu chưa có mục tiêu. Không gửi email. Tài khoản đã có phải là học viên đang hoạt động; mật khẩu, mục tiêu đã có và tiến độ được giữ nguyên. Cả hai lệnh chỉ chạy local/testing. Không lưu mật khẩu vào tài liệu hoặc mã nguồn.

## Kịch bản demo 10–15 phút

1. Đăng nhập học viên, mở `/my-courses` rồi khóa IELTS 6.5. Kiểm tra 5 bài học và thông tin demo.
2. Vào Listening, chọn audio hội thoại và nghe trước; mở transcript để đối chiếu. Xem video, tải PDF và nhấn hoàn thành các hoạt động media. Đọc và hoàn thành tài liệu.
3. Làm quiz: đáp án lần lượt B, C, A. Nộp bài và kiểm tra kết quả.
4. Nộp ghi chú: `Maya Chen; Tuesday 6:30 p.m.; room 12; fee £80; register by Friday.` Kiểm tra bài có trạng thái chờ chấm, chưa có band IELTS.
5. Quay lại khóa: Reading đã mở, tiến độ thay đổi. Lặp lại đọc → quiz → nộp bài nếu muốn mở Writing/Speaking.
6. Mở Dashboard V2 để xem mục tiêu IELTS 6.5 và bài học tiếp theo.
7. Dùng phiên đăng nhập giáo viên/admin riêng để mở Quản lý Gia sư AI của khóa. Xem học viên cần hỗ trợ, câu hỏi phổ biến, chi phí mô phỏng và hồ sơ học viên. Bài khách hàng vừa nộp là bài thật trong môi trường local; hội thoại/thống kê được seed là dữ liệu mô phỏng.

## Giới hạn hiện tại

Listening có audio hội thoại bằng hai giọng tổng hợp khớp transcript/quiz; Speaking có audio mẫu tổng hợp. Video là slide hướng dẫn có lời đọc và phụ đề, mỗi clip khoảng 30–37 giây. Đây là media demo tự tạo, không phải tài liệu IELTS chính thức. Speaking chưa có ghi âm học viên hoặc đánh giá phát âm/độ trôi chảy. Điểm luyện tập là phần trăm, không quy đổi thành band. Các đoạn bài mẫu Writing được ghi rõ là rút gọn. Hỏi Gia sư AI trực tiếp cần provider, nguồn kiến thức và quyền/credit phù hợp; dữ liệu hội thoại seed không bảo đảm API AI đã sẵn sàng. Không đưa kết quả mock thành kết quả chấm AI thật khi demo.

## Kiểm tra

`tests/Feature/AiTutorIeltsShowcaseTest.php` kiểm tra dữ liệu, chạy lặp, bảo toàn bài đã sửa, chuẩn bị học viên và luồng đọc tài liệu → quiz → nộp bài → mở bài tiếp theo.

Bộ media lưu tại `public/demo/ielts-65`, phục vụ cùng ứng dụng, không phụ thuộc YouTube. `manifest.json` ghi checksum và thời lượng. Script dựng yêu cầu Python/Pillow, PHP, espeak-ng và ffmpeg. Seeder kiểm tra checksum trước khi ghép media. Tài sản demo này là nội dung công khai; không đặt bài nộp riêng tư của học viên trong thư mục này.
