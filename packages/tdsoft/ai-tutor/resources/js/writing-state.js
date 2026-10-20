export function writingAssessmentState(result, pending) {
    const state = result?.status ?? (pending ? 'confirming' : 'idle');
    const active = ['confirming', 'queued', 'processing'].includes(state);
    const labels = {
        idle: ['Bắt đầu bài viết của bạn', 'Viết bài rồi gửi đánh giá để xem điểm từng tiêu chí, nhận xét và gợi ý sửa câu.'],
        confirming: ['Đang xác nhận yêu cầu', 'Đang kiểm tra lượt gửi của bạn. Bạn có thể cập nhật trạng thái; chưa cần gửi lại.'],
        queued: ['Đang chờ đánh giá', 'Bài đã vào hàng chờ. Kết quả sẽ tự cập nhật khi AI bắt đầu chấm.'],
        processing: ['AI đang phân tích bài viết', 'AI đang đọc bài và đối chiếu từng tiêu chí. Bạn có thể tiếp tục sửa bản nháp trong lúc chờ.'],
        completed: ['Đã đánh giá', 'Bạn có thể sửa bản nháp rồi gửi chấm lại.'],
        failed: ['Đánh giá thất bại', 'Bản nháp của bạn vẫn được giữ.'],
        reconciliation_required: ['Đang chờ đối soát', 'Lượt chấm cần kiểm tra trạng thái credit. Hãy cập nhật kết quả hoặc liên hệ quản trị viên.'],
    };
    const [title, description] = labels[state] ?? ['Đang cập nhật trạng thái', 'Hãy cập nhật kết quả để kiểm tra lượt chấm.'];
    // Billing reconciliation during normal worker processing is not a failure.
    const note = active ? 'Kết quả tự cập nhật · Không cần gửi lại bài.' : '';
    return { state, active, title, description, note, step: state === 'processing' ? 2 : state === 'queued' ? 1 : 0 };
}
