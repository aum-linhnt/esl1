import { repeatedWritingWords } from './writing-vocabulary.js';
export const writingCefrAspectNames = { communication: 'Mục tiêu giao tiếp', development: 'Phát triển và liên kết ý', language: 'Ngôn ngữ theo trình độ' };
export const writingCefrStatusNames = { met: 'Đã thể hiện', partial: 'Thể hiện một phần', not_met: 'Cần cải thiện', not_available: 'Chưa đủ dữ liệu' };

export const writingStructureNames = {
    introduction: 'Mở bài', overview: 'Tổng quan (Overview)', key_features: 'Đặc điểm chính', comparisons: 'So sánh dữ liệu',
    organisation: 'Tổ chức bài viết', position: 'Quan điểm', arguments: 'Phát triển luận điểm', examples: 'Ví dụ minh họa',
    conclusion: 'Kết luận', main_idea: 'Ý chính', supporting_details: 'Chi tiết hỗ trợ', linking: 'Liên kết ý',
};

/** Export the immutable assessed essay, never the editor's unsent content. */
export function writingReport(submission, metadata = {}) {
    if (submission?.status !== 'completed' || !submission.result || typeof submission.original !== 'string') {
        throw new Error('WRITING_REPORT_UNAVAILABLE');
    }
    const result = submission.result;
    const band = result.score_scale === 'ielts_band_0_9';
    const score = value => Number.isFinite(value) ? (band ? value.toFixed(1) : String(value)) : 'Chưa đủ dữ liệu';
    const names = band ? { task_response: 'Task Response', task_achievement: 'Task Achievement', coherence: 'Coherence & Cohesion', vocabulary: 'Lexical Resource', grammar: 'Grammatical Range & Accuracy' }
        : { grammar: 'Ngữ pháp', vocabulary: 'Từ vựng', coherence: 'Mạch lạc', task_response: 'Đáp ứng đề bài', style: 'Phong cách' };
    const lines = ['WRITING · BÁO CÁO ĐÁNH GIÁ', `Phiên bản ${submission.revision}`, `Mã lượt chấm: ${submission.id}`];
    if (metadata.task) lines.push(`Dạng bài: ${metadata.task}`);
    if (metadata.profile) lines.push(`Mục tiêu: ${metadata.profile.framework ?? ''} ${metadata.profile.target ?? ''}`.trim());
    lines.push('', 'ĐỀ BÀI', metadata.topic ?? '', '', 'BÀI ĐÃ ĐƯỢC ĐÁNH GIÁ', submission.original,
        '', 'KẾT QUẢ', `${band ? 'Band dự kiến' : 'Điểm luyện tập'}: ${score(result.overall_score)} (${band ? 'thang 9' : 'thang 100'})`,
        band ? 'Band cho riêng bài Task này do AI ước tính, không phải điểm IELTS chính thức.' : 'Điểm luyện tập do AI ước tính.');
    for (const [key, criterion] of Object.entries(result.criteria ?? {})) {
        lines.push('', `${names[key] ?? key}: ${score(criterion.score)}`);
        if (criterion.rationale) lines.push(criterion.rationale);
        for (const quote of criterion.evidence ?? []) lines.push(`Dẫn chứng: ${quote}`);
        if (criterion.next_step) lines.push(`Bước tiếp theo: ${criterion.next_step}`);
    }
    const section = (title, items) => {
        if (items?.length) lines.push('', title, ...items.map((item, i) => `${i + 1}. ${item}`));
    };
    if (result.feedback) lines.push('', 'NHẬN XÉT TỪ AI', result.feedback);
    section('ĐIỂM TỐT', result.strengths);
    section('CẦN CẢI THIỆN', result.improvements);
    section('VIỆC CẦN SỬA TRƯỚC', result.priority_actions);
    if (result.cefr_target_analysis) {
        const target = result.cefr_target_analysis;
        lines.push('', `SO VỚI MỤC TIÊU CEFR ${target.target}`, target.expectation,
            'Nhận xét cho bài này, không xác nhận trình độ tổng thể và không quy đổi từ điểm 100.');
        for (const item of target.aspects) {
            lines.push(`${writingCefrAspectNames[item.aspect] ?? item.aspect}: ${writingCefrStatusNames[item.status] ?? item.status}`, item.comment);
            for (const quote of item.evidence ?? []) lines.push(`Dẫn chứng: ${quote}`);
            if (item.next_step) lines.push(`Việc nên làm tiếp: ${item.next_step}`);
        }
    }
    if (submission.comparison?.issue_changes) {
        const comparison = submission.comparison;
        lines.push('', `GÓP Ý SO VỚI PHIÊN BẢN ${comparison.previous_revision}`,
            'Đối chiếu cùng loại góp ý và cụm gốc chính xác; AI không báo lại chưa đủ để kết luận lỗi đã sửa.');
        for (const [key, title] of [['recurring', 'AI còn báo lại'], ['not_reported', 'AI không báo lại'], ['newly_reported', 'Góp ý mới']]) {
            const group = comparison.issue_changes[key];
            lines.push(`${title}: ${group.count}`);
            for (const item of group.items) {
                lines.push(`Cụm gốc: ${item.original}`, `Gợi ý: ${item.replacement}`, item.explanation);
                if (key === 'not_reported') lines.push(item.original_still_present ? 'Cụm gốc vẫn còn trong bài mới.' : 'Cụm gốc không còn trong bài mới; chưa xác nhận lỗi đã sửa.');
            }
            if (group.count > group.items.length) lines.push(`Hiển thị ${group.items.length}/${group.count} góp ý.`);
        }
        if (comparison.issue_changes.unverified_count) lines.push(`${comparison.issue_changes.unverified_count} góp ý chưa đủ dẫn chứng để đối chiếu.`);
    }
    const repeated = repeatedWritingWords(submission.original);
    if (repeated.length) {
        lines.push('', 'TỪ XUẤT HIỆN NHIỀU', ...repeated.map(item => `${item.word}: ${item.count} lần`),
            'Thống kê không phân biệt hoa/thường, không gộp biến thể từ. Từ khóa chủ đề có thể cần lặp; đây không phải kết luận có lỗi.');
    }
    if (result.structure_analysis?.length) {
        const states = { met: 'Đã đáp ứng', partial: 'Đáp ứng một phần', not_met: 'Chưa đáp ứng', not_available: 'Chưa đủ dữ liệu' };
        lines.push('', 'CẤU TRÚC BÀI VIẾT');
        for (const item of result.structure_analysis) {
            lines.push(`${writingStructureNames[item.component] ?? item.component}: ${states[item.status] ?? item.status}`, item.comment);
            for (const quote of item.evidence ?? []) lines.push(`Dẫn chứng: ${quote}`);
            if (item.next_step) lines.push(`Bước tiếp theo: ${item.next_step}`);
        }
    }
    if (result.task_requirements?.length) {
        const states = { met: 'Đã đáp ứng', partial: 'Đáp ứng một phần', not_met: 'Chưa đáp ứng', not_available: 'Chưa đủ dữ liệu' };
        lines.push('', 'KIỂM TRA YÊU CẦU ĐỀ');
        for (const item of result.task_requirements) {
            lines.push(`${item.requirement}: ${states[item.status] ?? item.status}`, item.comment);
            for (const quote of item.evidence ?? []) lines.push(`Dẫn chứng: ${quote}`);
        }
    }
    if (result.paragraph_analysis?.length) {
        lines.push('', 'PHÂN TÍCH THEO ĐOẠN');
        for (const item of result.paragraph_analysis) {
            lines.push(`Đoạn ${item.paragraph_number}`, item.excerpt ?? '', item.comment, `Bước tiếp theo: ${item.next_step}`);
        }
    }
    if (result.issues?.length) {
        lines.push('', 'GỢI Ý CHỈNH SỬA CÂU');
        result.issues.forEach((issue, i) => {
            lines.push('', `${i + 1}. ${names[issue.category] ?? issue.category}`, `Câu gốc: ${issue.original}`,
                `Gợi ý: ${issue.replacement ?? ''}`, issue.explanation);
            if (!issue.applicable) lines.push('Vị trí chưa xác minh được; đọc góp ý và sửa thủ công.');
        });
    }
    // Fixed extension and restricted ID prevent topic text becoming a file path.
    const safeId = String(submission.id ?? '').replace(/[^a-zA-Z0-9_-]/g, '').slice(0, 64) || 'result';
    return { filename: `writing-${safeId}-v${Number.isInteger(submission.revision) ? submission.revision : 0}.txt`,
        text: lines.filter(line => line !== undefined && line !== null).join('\n') + '\n' };
}
