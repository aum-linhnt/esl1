<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\QuestionBank;

$questions = [
    // ══════════════════════════════════════════════════════════════════════════
    // LISTENING QUESTIONS (A1, A2, B1, B2)
    // ══════════════════════════════════════════════════════════════════════════
    [
        'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe thông báo tại nhà ga xe lửa: "The train to Oxford will depart from Platform 3 in five minutes." Tàu đi Oxford khởi hành tại sân ga nào?',
        'options' => ['Platform 1', 'Platform 2', 'Platform 3', 'Platform 5'],
        'correct_answer' => 'Platform 3',
        'explanation' => 'Thông báo nêu rõ: "depart from Platform 3" (khởi hành tại sân ga số 3).',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe câu hỏi tại quầy lễ tân: "May I have your room key, please?" Người nói đang yêu cầu điều gì?',
        'options' => ['Hộ chiếu', 'Chìa khóa phòng', 'Số điện thoại', 'Hóa đơn thanh toán'],
        'correct_answer' => 'Chìa khóa phòng',
        'explanation' => '"Room key" có nghĩa là chìa khóa phòng khách sạn.',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe đoạn hội thoại mua sắm: "How much is this blue shirt?" - "It is twenty-five dollars." Chiếc áo sơ mi có giá bao nhiêu?',
        'options' => ['$15', '$20', '$25', '$35'],
        'correct_answer' => '$25',
        'explanation' => 'Người bán trả lời: "twenty-five dollars" ($25).',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe tin nhắn thoại: "Hi Tom, our meeting tomorrow has been moved from 9:00 AM to 10:30 AM in Room 204." Cuộc họp dời sang mấy giờ?',
        'options' => ['9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM'],
        'correct_answer' => '10:30 AM',
        'explanation' => 'Lời nhắn: "moved from 9:00 AM to 10:30 AM" (dời sang 10 giờ 30 sáng).',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe chỉ đường: "Go straight for two blocks, then turn left at the bookstore. The post office is on your right." Bưu điện nằm ở đâu?',
        'options' => ['Bên trái nhà sách', 'Bên phải sau khi rẽ trái ở nhà sách', 'Đối diện sân vận động', 'Ở ngã tư thứ nhất'],
        'correct_answer' => 'Bên phải sau khi rẽ trái ở nhà sách',
        'explanation' => 'Chỉ dẫn: đi thẳng 2 dãy nhà, rẽ trái tại hiệu sách, bưu điện nằm bên tay phải ("on your right").',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe thông báo thời tiết: "Expect heavy rain this afternoon with temperatures dropping to 18 degrees Celsius. Don\'t forget your umbrella." Người nghe được khuyên nên mang theo gì?',
        'options' => ['Kính râm', 'Áo khoác dày', 'Ô / dù', 'Nón bảo hiểm'],
        'correct_answer' => 'Ô / dù',
        'explanation' => '"Don\'t forget your umbrella" nghĩa là đừng quên mang ô/dù che mưa.',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'B1', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe đoạn trao đổi công việc: "Due to the unexpected budget cuts, the marketing department will postpone the promotional campaign until next quarter." Kế hoạch tiếp thị bị ảnh hưởng như thế nào?',
        'options' => ['Bị hủy bỏ vĩnh viễn', 'Hoãn lại sang quý tới', 'Tăng gấp đôi ngân sách', 'Chuyển giao cho đối tác'],
        'correct_answer' => 'Hoãn lại sang quý tới',
        'explanation' => '"postpone the promotional campaign until next quarter" có nghĩa là hoãn chiến dịch quảng bá đến quý sau.',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'B1', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe thông báo sân bay: "Passengers on flight VN384 to Seoul please proceed to Gate 18 immediately for final boarding." Hành khách cần làm gì?',
        'options' => ['Lấy hành lý ký gửi', 'Đến Cổng 18 ngay lập tức để lên máy bay', 'Đổi vé tại quầy chăm sóc khách hàng', 'Chờ kiểm tra an ninh bổ sung'],
        'correct_answer' => 'Đến Cổng 18 ngay lập tức để lên máy bay',
        'explanation' => '"proceed to Gate 18 immediately for final boarding" có nghĩa là khẩn trương đến Cổng 18 để lên máy bay.',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],
    [
        'skill' => 'listening', 'difficulty' => 'B2', 'question_type' => 'listening_comprehension',
        'question_text' => 'Nghe trích đoạn bài giảng kinh tế: "The central bank decided to lower interest rates to stimulate consumer spending and encourage business investment amidst inflationary pressures." Mục tiêu của việc giảm lãi suất là gì?',
        'options' => ['Kiểm soát tỷ giá hối đoái', 'Kích thích chi tiêu tiêu dùng và khuyến khích đầu tư kinh doanh', 'Giảm thuế thu nhập doanh nghiệp', 'Hạn chế nhập khẩu hàng tiêu dùng'],
        'correct_answer' => 'Kích thích chi tiêu tiêu dùng và khuyến khích đầu tư kinh doanh',
        'explanation' => '"to stimulate consumer spending and encourage business investment" nói rõ mục đích kích cầu tiêu dùng và thúc đẩy đầu tư.',
        'meta_data' => ['audio_url' => 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3']
    ],

    // ══════════════════════════════════════════════════════════════════════════
    // READING QUESTIONS (A1, A2, B1, B2)
    // ══════════════════════════════════════════════════════════════════════════
    [
        'skill' => 'reading', 'difficulty' => 'A1', 'question_type' => 'reading_comprehension',
        'question_text' => 'Đọc biển báo tại thư viện: "SILENCE PLEASE - Study Area". Biển báo này yêu cầu điều gì?',
        'options' => ['Không được ăn uống', 'Giữ yên lặng', 'Tắt máy tính', 'Không mang sách ra ngoài'],
        'correct_answer' => 'Giữ yên lặng',
        'explanation' => '"Silence please" nghĩa là xin vui lòng giữ trật tự/im lặng.',
        'meta_data' => ['passage_title' => 'Biển báo thư viện', 'passage_content' => 'NOTICE:\nSILENCE PLEASE\nThis is a designated quiet study area. Please turn off phone ringers.']
    ],
    [
        'skill' => 'reading', 'difficulty' => 'A1', 'question_type' => 'reading_comprehension',
        'question_text' => 'Đọc thông báo cửa hàng: "OPEN Monday - Saturday: 8:00 AM - 9:00 PM. CLOSED on Sundays." Cửa hàng đóng cửa vào ngày nào?',
        'options' => ['Thứ Hai', 'Thứ Bảy', 'Chủ Nhật', 'Tất cả các ngày lễ'],
        'correct_answer' => 'Chủ Nhật',
        'explanation' => '"CLOSED on Sundays" nghĩa là đóng cửa vào các ngày Chủ nhật.',
        'meta_data' => ['passage_title' => 'Giờ mở cửa', 'passage_content' => 'STORE HOURS:\nMonday - Saturday: 8:00 AM - 9:00 PM\nSunday: CLOSED']
    ],
    [
        'skill' => 'reading', 'difficulty' => 'A2', 'question_type' => 'reading_comprehension',
        'question_text' => 'Đọc email nội bộ: "Dear Staff, our annual company picnic is scheduled for Saturday, October 15th at Green Lake Park. Transportation will be provided from the office at 7:30 AM." Xe đưa đón nhân viên khởi hành lúc mấy giờ?',
        'options' => ['6:30 AM', '7:00 AM', '7:30 AM', '8:00 AM'],
        'correct_answer' => '7:30 AM',
        'explanation' => 'Đoạn văn nêu rõ: "Transportation will be provided from the office at 7:30 AM".',
        'meta_data' => ['passage_title' => 'Company Picnic Announcement', 'passage_content' => 'Dear Staff,\nOur annual company picnic is scheduled for Saturday, October 15th at Green Lake Park. Free lunch and sports activities will be available for everyone. Transportation will be provided from the office at 7:30 AM.']
    ],
    [
        'skill' => 'reading', 'difficulty' => 'A2', 'question_type' => 'reading_comprehension',
        'question_text' => 'Đọc tờ rơi khóa học: "English for Travel: Learn essential phrases for airports, hotels, and restaurants. 4 weeks, 2 sessions per week. Special 20% discount for early bird registration before May 1st." Ưu đãi nào được dành cho người đăng ký sớm trước ngày 1 tháng 5?',
        'options' => ['Tặng sách giáo trình miễn phí', 'Giảm 20% học phí', 'Học thử miễn phí 1 tuần', 'Được học kèm 1-1'],
        'correct_answer' => 'Giảm 20% học phí',
        'explanation' => 'Tờ rơi ghi: "Special 20% discount for early bird registration before May 1st".',
        'meta_data' => ['passage_title' => 'Travel Course Flyer', 'passage_content' => 'English for Travel Course:\nMaster real-world conversations at international airports, hotels, and dining.\nDuration: 4 weeks (8 classes).\nEarly bird bonus: 20% discount if registered before May 1st.']
    ],
    [
        'skill' => 'reading', 'difficulty' => 'B1', 'question_type' => 'reading_comprehension',
        'question_text' => 'Theo đoạn văn, xu hướng làm việc từ xa (remote work) mang lại lợi ích môi trường nổi bật nào?',
        'options' => ['Tăng sản lượng tiêu thụ điện', 'Giảm thiểu khí thải các-bon do giảm phương tiện đi lại hàng ngày', 'Tăng diện tích văn phòng cho thuê', 'Thúc đẩy sử dụng đồ nhựa dùng một lần'],
        'correct_answer' => 'Giảm thiểu khí thải các-bon do giảm phương tiện đi lại hàng ngày',
        'explanation' => 'Bài viết nhấn mạnh: "reducing daily commutes has led to a measurable decline in carbon emissions and urban traffic congestion".',
        'meta_data' => [
            'passage_title' => 'Environmental Impacts of Remote Work',
            'passage_content' => 'In recent years, the shift toward telecommuting has dramatically reshaped the corporate landscape. Beyond enhancing work-life balance and flexibility for employees, reducing daily commutes has led to a measurable decline in carbon emissions and urban traffic congestion. Studies indicate that companies adopting hybrid models also consume significantly less office electricity and paper.'
        ]
    ],
    [
        'skill' => 'reading', 'difficulty' => 'B1', 'question_type' => 'reading_comprehension',
        'question_text' => 'Theo đoạn văn, nguyên nhân chính khiến loài ong mật suy giảm quần thể là gì?',
        'options' => ['Thiếu nguồn nước sạch', 'Sự kết hợp giữa mất môi trường sống, thuốc trừ sâu và biến đổi khí hậu', 'Sự gia tăng của các loài chim ăn thịt', 'Nhiệt độ mùa đông quá lạnh'],
        'correct_answer' => 'Sự kết hợp giữa mất môi trường sống, thuốc trừ sâu và biến đổi khí hậu',
        'explanation' => 'Bài viết nêu: "a combination of habitat fragmentation, widespread pesticide use, and shifting climatic patterns".',
        'meta_data' => [
            'passage_title' => 'Global Pollinator Crisis',
            'passage_content' => 'Honeybees play a pivotal role in maintaining agricultural biodiversity by pollinating roughly one-third of the global food supply. However, apiaries worldwide are reporting unprecedented colony collapses. Ecologists point to a combination of habitat fragmentation, widespread pesticide use, and shifting climatic patterns as the primary drivers threatening their survival.'
        ]
    ],

    // ══════════════════════════════════════════════════════════════════════════
    // WRITING QUESTIONS (A1, A2, B1, B2, C1)
    // ══════════════════════════════════════════════════════════════════════════
    [
        'skill' => 'writing', 'difficulty' => 'A1', 'question_type' => 'essay_writing',
        'question_text' => 'Hãy viết 2-3 câu đơn giản (tối thiểu 20 từ) giới thiệu về một người bạn thân của bạn (tên, tuổi, sở thích chung).',
        'options' => [],
        'correct_answer' => 'My best friend is Nam. He is twenty years old. We love playing football and listening to music together on weekends.',
        'explanation' => 'Yêu cầu: giới thiệu tên, tuổi và sở thích chung, sử dụng thì hiện tại đơn chính xác.',
        'meta_data' => ['min_words' => 20, 'task_type' => 'Sentence Building & Personal Profile']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'A1', 'question_type' => 'essay_writing',
        'question_text' => 'Hãy viết một ghi chú ngắn (tối thiểu 20 từ) nhắn người bạn cùng phòng mua giúp bạn một số đồ dùng (bánh mì, sữa, trứng).',
        'options' => [],
        'correct_answer' => 'Hi John, I am studying at the library right now. Can you please buy some bread, milk, and eggs on your way home? Thanks a lot!',
        'explanation' => 'Viết mẩu tin nhắn ngắn gọn, nêu rõ các món đồ cần nhờ mua và lời cảm ơn.',
        'meta_data' => ['min_words' => 20, 'task_type' => 'Short Informal Note']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'A2', 'question_type' => 'essay_writing',
        'question_text' => 'Bạn vừa có một chuyến đi du lịch cuối tuần. Hãy viết một email ngắn (tối thiểu 35 từ) kể cho bạn bè nghe bạn đã đi đâu, thời tiết như thế nào và hoạt động bạn thích nhất.',
        'options' => [],
        'correct_answer' => 'Hi Sarah, last weekend I went to Da Nang with my family. The weather was very sunny and breezy. We swam at the beach and ate fresh seafood. I took a lot of photos that I will show you soon!',
        'explanation' => 'Sử dụng thì quá khứ đơn (went, was, swam, took), cấu trúc mạch lạc và đủ thông tin.',
        'meta_data' => ['min_words' => 35, 'task_type' => 'Informal Travel Email']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'A2', 'question_type' => 'essay_writing',
        'question_text' => 'Hãy viết một email xin phép vắng mặt (tối thiểu 35 từ) gửi cho giáo viên tiếng Anh giải thích lý do bạn không thể tham gia buổi học vào ngày mai.',
        'options' => [],
        'correct_answer' => 'Dear Teacher Mary, I am writing to inform you that I cannot attend our English class tomorrow because I have a doctor appointment. I will ask my classmate for the homework notes and submit my assignment on time.',
        'explanation' => 'Giọng văn lịch sự, nêu rõ lý do vắng mặt và cam kết hoàn thành bài tập về nhà.',
        'meta_data' => ['min_words' => 35, 'task_type' => 'Formal Absence Request']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'B1', 'question_type' => 'essay_writing',
        'question_text' => 'Viết một email phản hồi khách hàng (tối thiểu 50 từ) xin lỗi vì đơn hàng bị giao trễ 2 ngày và đưa ra phương án bồi thường (miễn phí vận chuyển hoặc giảm giá 15% cho đơn sau).',
        'options' => [],
        'correct_answer' => 'Dear Mr. Miller, Thank you for contacting our customer support team. We sincerely apologize for the unexpected delay in delivering your package. To compensate for this inconvenience, we have refunded your shipping fee and credited a 15% discount voucher to your account for your next purchase.',
        'explanation' => 'Email thư tín thương mại chuyên nghiệp: nhận lỗi chân thành, giải thích nguyên nhân và đưa ra giải pháp bồi hoàn cụ thể.',
        'meta_data' => ['min_words' => 50, 'task_type' => 'Customer Service Email']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'B1', 'question_type' => 'essay_writing',
        'question_text' => 'Viết một đoạn văn ngắn (tối thiểu 50 từ) trình bày quan điểm của bạn về lợi ích và tác hại của việc sử dụng mạng xã hội đối với giới trẻ hiện nay.',
        'options' => [],
        'correct_answer' => 'Social media has transformed modern communication by allowing teenagers to connect globally and share educational resources effortlessly. However, excessive screen time often leads to digital distractions, anxiety, and a sedentary lifestyle. Therefore, cultivating healthy digital habits is vital for young users.',
        'explanation' => 'Bài viết có cấu trúc hai mặt: phân tích lợi ích kết nối và rủi ro xao nhãng kèm kết luận đề xuất giải pháp.',
        'meta_data' => ['min_words' => 50, 'task_type' => 'Opinion Paragraph']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'B2', 'question_type' => 'essay_writing',
        'question_text' => 'Viết một đoạn văn học thuật (tối thiểu 70 từ) phân tích tầm quan trọng của năng lượng tái tạo (như điện mặt trời và gió) trong việc giảm biến đổi khí hậu toàn cầu.',
        'options' => [],
        'correct_answer' => 'Transitioning toward renewable energy sources, particularly solar and wind power, represents an imperative strategy to mitigate catastrophic climate change. Unlike fossil fuels, renewables emit virtually no greenhouse gases during operation, drastically shrinking national carbon footprints. Furthermore, investing in clean energy infrastructure stimulates economic growth, generates green employment opportunities, and enhances energy independence for developing economies.',
        'explanation' => 'Sử dụng từ vựng học thuật phong phú (mitigate, greenhouse gases, clean energy infrastructure, energy independence), câu ghép phức và liên từ lập luận chặt chẽ.',
        'meta_data' => ['min_words' => 70, 'task_type' => 'Academic Essay Excerpt']
    ],
    [
        'skill' => 'writing', 'difficulty' => 'C1', 'question_type' => 'essay_writing',
        'question_text' => 'Viết một đoạn văn phân tích chuyên sâu (tối thiểu 80 từ) về tác động của trí tuệ nhân tạo (AI) đối với thị trường lao động và yêu cầu tái đào tạo kỹ năng cho người lao động.',
        'options' => [],
        'correct_answer' => 'The accelerating integration of artificial intelligence across industries is profoundly restructuring labor paradigms. While automation inevitably displaces routine manual and cognitive roles, it concurrently catalyzes emerging occupations demanding advanced problem-solving, emotional intelligence, and technological literacy. Consequently, academic institutions and policymakers must proactively foster lifelong reskilling initiatives, thereby equipping the workforce to harmonize with intelligent automation rather than succumb to technological redundancy.',
        'explanation' => 'Đoạn văn đạt chuẩn C1 với từ ngữ tinh tế (restructuring labor paradigms, technological literacy, lifelong reskilling initiatives, technological redundancy).',
        'meta_data' => ['min_words' => 80, 'task_type' => 'High-level Critical Analysis']
    ],

    // ══════════════════════════════════════════════════════════════════════════
    // SPEAKING QUESTIONS (A1, A2, B1, B2, C1)
    // ══════════════════════════════════════════════════════════════════════════
    [
        'skill' => 'speaking', 'difficulty' => 'A1', 'question_type' => 'audio_recording',
        'question_text' => 'Đọc to câu chào hỏi và giới thiệu bản thân vào micro: "Hello! My name is Alex. I come from Vietnam and I am happy to learn English."',
        'options' => [],
        'correct_answer' => 'Phát âm chuẩn ngữ điệu câu đơn giản, rõ âm đuôi /s/ trong Alex, /m/ trong Vietnam.',
        'explanation' => 'Tiêu chí: phát âm to rõ, không ngắc ngứ, trọng âm chuẩn.',
        'meta_data' => ['task_type' => 'Read Aloud Drill', 'prep_time' => 10, 'speak_time' => 20]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'A1', 'question_type' => 'audio_recording',
        'question_text' => 'Trả lời câu hỏi sau bằng micro (nói 1-2 câu): "What do you usually have for breakfast?"',
        'options' => [],
        'correct_answer' => 'I usually have bread and eggs with a cup of hot milk for breakfast.',
        'explanation' => 'Tiêu chí: phản xạ tự nhiên, nêu rõ món ăn sáng thường dùng.',
        'meta_data' => ['task_type' => 'Short Daily Routine Response', 'prep_time' => 10, 'speak_time' => 25]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'A2', 'question_type' => 'audio_recording',
        'question_text' => 'Ghi âm câu trả lời (nói trong 20-30 giây): "Describe your favorite room in your house and explain why you like spending time there."',
        'options' => [],
        'correct_answer' => 'My favorite room is my bedroom because it is very quiet and comfortable. I have a large desk near the window where I study and read books every evening.',
        'explanation' => 'Tiêu chí: miêu tả căn phòng, đồ vật nổi bật và lý do yêu thích bằng câu nối mạch lạc.',
        'meta_data' => ['task_type' => 'Room Description', 'prep_time' => 15, 'speak_time' => 30]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'A2', 'question_type' => 'audio_recording',
        'question_text' => 'Tình huống tại nhà hàng: Ghi âm cuộc gọi đặt bàn (nói trong 20-30 giây): Đặt một bàn cho 4 người vào tối thứ Sáu lúc 7 giờ tối.',
        'options' => [],
        'correct_answer' => 'Good morning! I would like to reserve a table for four people this Friday evening at 7:00 PM, please. Could we have a table by the window?',
        'explanation' => 'Tiêu chí: giọng điệu lịch sự (would like to reserve), đầy đủ số người, ngày giờ và yêu cầu thêm.',
        'meta_data' => ['task_type' => 'Restaurant Reservation Scenario', 'prep_time' => 15, 'speak_time' => 30]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'B1', 'question_type' => 'audio_recording',
        'question_text' => 'Ghi âm câu trả lời (nói trong 30-45 giây): "Some people prefer living in big cities, while others prefer living in the countryside. Which one do you prefer and why?"',
        'options' => [],
        'correct_answer' => 'Personally, I prefer living in the countryside due to its serene atmosphere and fresher air. Unlike bustling urban centers where traffic congestion is frequent, the rural environment provides a peaceful pace of life.',
        'explanation' => 'Tiêu chí: nêu rõ lập trường so sánh thành thị vs nông thôn, đưa ra ít nhất 2 lý do thuyết phục kèm ví dụ.',
        'meta_data' => ['task_type' => 'Comparison & Justification', 'prep_time' => 20, 'speak_time' => 45]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'B1', 'question_type' => 'audio_recording',
        'question_text' => 'Xử lý tình huống công sở: Bạn trễ hạn nộp báo cáo cho sếp. Hãy ghi âm lời giải thích và cam kết thời gian nộp bù (nói trong 30-40 giây).',
        'options' => [],
        'correct_answer' => 'Hi Ms. Jennifer, I sincerely apologize for the delay in submitting the quarterly sales report. We encountered unexpected data errors yesterday, but I am currently finalizing the corrections and will email the completed document by 3:00 PM today.',
        'explanation' => 'Tiêu chí: xin lỗi chuyên nghiệp, giải thích ngắn gọn nguyên nhân và chốt thời hạn nộp chính xác.',
        'meta_data' => ['task_type' => 'Workplace Crisis Communication', 'prep_time' => 20, 'speak_time' => 45]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'B2', 'question_type' => 'audio_recording',
        'question_text' => 'Thuyết trình chủ đề (nói trong 45-60 giây): "Do you think artificial intelligence will completely replace teachers in future classrooms? Explain your perspective."',
        'options' => [],
        'correct_answer' => 'In my view, while artificial intelligence can undoubtedly personalize learning paths and automate administrative grading, it will never entirely supplant human educators. Teachers cultivate empathy, moral values, and critical thinking—qualities that algorithms cannot replicate.',
        'explanation' => 'Tiêu chí: lập luận phản biện sắc sảo, sử dụng liên từ chỉ sự tương phản (while, undoubtedly, never entirely supplant) và phát âm lưu loát.',
        'meta_data' => ['task_type' => 'Oral Debate & Academic Presentation', 'prep_time' => 30, 'speak_time' => 60]
    ],
    [
        'skill' => 'speaking', 'difficulty' => 'C1', 'question_type' => 'audio_recording',
        'question_text' => 'Bình luận chuyên sâu (nói trong 60 giây): "Discuss how globalization has influenced cultural identity in developing nations. Give specific insights."',
        'options' => [],
        'correct_answer' => 'Globalization has exerted a profound, multifaceted influence on developing societies. On one hand, it facilitates cross-cultural exchange and technological diffusion. On the other hand, the pervasive influx of Western media poses an existential threat to indigenous traditions and dialects, requiring deliberate cultural preservation policies.',
        'explanation' => 'Tiêu chí C1: vốn từ học thuật phong phú, khả năng triển khai luận điểm đa chiều (multifaceted influence, technological diffusion, existential threat to indigenous traditions).',
        'meta_data' => ['task_type' => 'Advanced Cultural Commentary', 'prep_time' => 30, 'speak_time' => 60]
    ]
];

$addedCount = 0;
foreach ($questions as $q) {
    // Check if duplicate question_text exists
    $exists = QuestionBank::where('question_text', $q['question_text'])->exists();
    if (!$exists) {
        QuestionBank::create([
            'skill' => $q['skill'],
            'difficulty' => $q['difficulty'],
            'question_type' => $q['question_type'],
            'question_text' => $q['question_text'],
            'options' => $q['options'],
            'correct_answer' => $q['correct_answer'],
            'explanation' => $q['explanation'],
            'meta_data' => $q['meta_data'] ?? [],
        ]);
        $addedCount++;
    }
}

echo "Successfully seeded {$addedCount} new rich questions into QuestionBank!\n";
echo "Total in database now: " . QuestionBank::count() . "\n";
