<div class="tai-license" data-tai-chat data-actor="{{ $actorId }}" data-lesson="{{ $lesson->lessonId }}" data-api="{{ url('/ai-tutor/api/v1') }}">
    <h1>Gia sư AI</h1>
    <p>Bài học: <span data-lesson-label>{{ $lesson->lessonId }}</span> · Chính sách đáp án do LMS quyết định.</p>
    @if(config('ai-tutor.theme.allow_user_switch'))
        <button type="button" data-tai-theme>Đổi sáng/tối</button>
    @endif
    <label for="tai-mode">Cách hướng dẫn</label>
    <select id="tai-mode" data-mode>
        <option value="hints_first">Gợi ý trước</option>
        <option value="socratic">Hỏi gợi mở</option>
        <option value="explain">Giải thích</option>
        <option value="practice">Luyện tập</option>
        <option value="review">Ôn tập</option>
        <option value="exam">Bài thi — không hỗ trợ đáp án</option>
    </select>
    <p role="status" data-status>Sẵn sàng. Credit được kiểm tra trước mỗi lần gọi AI.</p>
    <section data-history aria-label="Hội thoại"></section>
    <form data-chat-form>
        <label for="tai-message">Câu hỏi</label>
        <textarea id="tai-message" name="message" rows="4" maxlength="4000" required></textarea>
        <button type="submit">Gửi câu hỏi</button>
        <button type="button" data-retry hidden>Thử lại cùng yêu cầu</button>
        <button type="button" data-reload>Tải lại hội thoại</button>
        <button type="button" data-export>Xuất hội thoại</button>
        <button type="button" data-delete>Xóa hội thoại</button>
    </form>
</div>
