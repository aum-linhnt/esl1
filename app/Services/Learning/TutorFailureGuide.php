<?php

namespace App\Services\Learning;

final class TutorFailureGuide
{
    public static function describe(?string $code): array
    {
        return match ($code) {
            'AI_CREDIT_INSUFFICIENT' => ['title' => 'Không đủ credit', 'advice' => 'Nhờ quản trị viên kiểm tra số dư và phần credit đang giữ của học viên.', 'action' => 'credits'],
            'AI_DAILY_LIMIT_REACHED' => ['title' => 'Đã chạm hạn mức sử dụng', 'advice' => 'Kiểm tra hạn mức ngày, tuần hoặc tháng và các yêu cầu đang giữ credit. Học viên có thể chờ hạn mức đặt lại.', 'action' => 'credits'],
            'AI_REQUEST_RECONCILIATION_REQUIRED', 'AI_RESERVATION_INVALID' => ['title' => 'Cần đối soát yêu cầu', 'advice' => 'Nhờ quản trị viên kiểm tra trạng thái yêu cầu và credit trước khi học viên thử lại.', 'action' => 'credits'],
            'AI_PROVIDER_AUTH_FAILED', 'AI_PROVIDER_NOT_CONFIGURED', 'AI_ADAPTER_NOT_CONFIGURED' => ['title' => 'Cấu hình kết nối AI cần kiểm tra', 'advice' => 'Nhờ quản trị viên kiểm tra cấu hình nhà cung cấp và khóa API.', 'action' => 'settings'],
            'AI_PROVIDER_RATE_LIMITED', 'AI_PROVIDER_UNAVAILABLE' => ['title' => 'Dịch vụ AI tạm thời chưa sẵn sàng', 'advice' => 'Học viên có thể thử lại sau. Nếu lỗi lặp lại, nhờ quản trị viên kiểm tra dịch vụ AI.', 'action' => 'settings'],
            'AI_DOCUMENT_NOT_READY', 'AI_KNOWLEDGE_PROCESSING_FAILED', 'AI_SOURCE_NOT_FOUND', 'AI_EMBEDDING_INVALID' => ['title' => 'Nguồn kiến thức cần kiểm tra', 'advice' => 'Kiểm tra học liệu của bài học; nhờ quản trị viên kiểm tra trạng thái tài liệu và lập chỉ mục kiến thức.', 'action' => 'knowledge'],
            'AI_CONTEXT_CHANGED', 'AI_CONTEXT_FORBIDDEN', 'AI_TEACHING_POLICY_INVALID' => ['title' => 'Ngữ cảnh hoặc chính sách bài học đã thay đổi', 'advice' => 'Kiểm tra bài học và chính sách AI đang áp dụng. Học viên cần mở lại bài học với quyền truy cập hiện tại.', 'action' => 'policy'],
            'AI_DISABLED' => ['title' => 'Tính năng AI đang tắt', 'advice' => 'Nhờ quản trị viên kiểm tra trạng thái bật tính năng và quyền sử dụng AI.', 'action' => 'settings'],
            'AI_CONVERSATION_BUSY', 'AI_REQUEST_DUPLICATE' => ['title' => 'Hội thoại hoặc yêu cầu đang được xử lý', 'advice' => 'Học viên cần chờ yêu cầu hiện tại hoàn tất và tải lại hội thoại để kiểm tra kết quả.', 'action' => null],
            default => ['title' => 'Yêu cầu xử lý chưa thành công', 'advice' => 'Kiểm tra bài học liên quan. Nếu lỗi tiếp diễn, cung cấp thời điểm và mã lỗi cho quản trị viên.', 'action' => null],
        };
    }
}
