<?php

// Original, shortened practice material for the customer demo, not an official IELTS test.
return [
    [
        'objective' => 'Xác định thông tin chính, nhận diện thay đổi và ghi chú bằng từ khóa.',
        'body' => "LISTENING — COURSE REGISTRATION\n\nBài demo dùng transcript, chưa có audio.\n\nReceptionist: Good morning. How can I help you?\nStudent: I would like to join the evening English course. My name is Maya Chen.\nReceptionist: The class normally starts at six, but next Tuesday it will begin at half past six.\nStudent: Which room should I go to?\nReceptionist: Room twelve, on the second floor. Please bring a notebook. The course fee is eighty pounds.\nStudent: Can I pay on the first day?\nReceptionist: Yes, but please register by Friday.\n\nCách luyện: xác định loại thông tin cần tìm → gạch chân từ khóa → kiểm tra lời sửa đổi → ghi chú ngắn.\nPhân biệt giờ thông thường với giờ của buổi học được hỏi.",
        'quizzes' => [
            ['When does the class start next Tuesday?', ['6:00', '6:30', '7:00', '5:30'], 1, 'The class normally starts at six, but next Tuesday it begins at half past six.'],
            ['Which room should Maya go to?', ['Room two', 'Room ten', 'Room twelve', 'Room twenty'], 2, 'The receptionist specifies room twelve, on the second floor.'],
            ['What is the registration deadline?', ['Friday', 'Tuesday', 'Monday', 'The first class'], 0, 'Payment can be made on the first day, but registration must be completed by Friday.'],
        ],
        'assignment' => "[DEMO Listening — transcript, chưa có audio]\nDùng hội thoại Course Registration trong tài liệu bài học.\n1. Ghi tên học viên, giờ học thứ Ba, phòng học, học phí và hạn đăng ký.\n2. Chỉ ra câu làm thay đổi thông tin về giờ học.\n3. Giải thích vì sao ngày thanh toán không phải hạn đăng ký.\nNộp ghi chú dạng văn bản; giáo viên nhận xét độ chính xác và cách chọn từ khóa.",
        'sample' => 'Name: Maya Chen. Tuesday class: 6:30 p.m. Room: 12, second floor. Fee: £80. Register by Friday. She can pay on the first day, but she must register earlier. The word “but” signals the change from the usual starting time.',
        'feedback' => 'Ghi chú cần phân biệt giờ thường lệ và giờ đã đổi; kiểm tra hạn đăng ký khác với ngày thanh toán.',
    ],
    [
        'objective' => 'Tìm ý chính, quét thông tin cụ thể và phân biệt lợi ích với hạn chế.',
        'body' => "READING — THE CHANGING ROLE OF LIBRARIES\n\nMany public libraries now offer more than shelves of books. At Riverside Library, visitors can borrow laptops, attend language clubs and use quiet study rooms. These services are free, making them useful for people who cannot easily buy equipment or find a suitable place to study.\n\nThe library introduced online reservations last year. Visitors can book a study room before leaving home, although reservations are limited to two hours per person each day. Staff report that this system has reduced queues at the front desk.\n\nHowever, digital services do not meet every visitor's needs. Some older residents prefer asking a librarian for help, and unreliable internet access can prevent people from using the website. Riverside therefore keeps a staffed booking desk alongside its online system.\n\nCách luyện: skimming để tìm ý chính mỗi đoạn; scanning để tìm giới hạn thời gian; đọc câu trước và sau để hiểu lý do giữ quầy hỗ trợ.",
        'quizzes' => [
            ['What is the main purpose of the passage?', ['Explain book prices', 'Describe library services and access challenges', 'Recommend closing libraries', 'Advertise a laptop shop'], 1, 'The passage describes expanded services, reservations and barriers to digital access.'],
            ['How long may one person reserve a study room each day?', ['One hour', 'Three hours', 'All day', 'Two hours'], 3, 'Reservations are limited to two hours per person each day.'],
            ['Why does Riverside keep a staffed booking desk?', ['Some visitors need help or cannot use the website', 'Online reservations are always unavailable', 'The library sells laptops', 'The rooms have no internet'], 0, 'Some visitors prefer staff assistance, while others have unreliable internet access.'],
        ],
        'assignment' => "[DEMO Reading]\nĐọc The Changing Role of Libraries.\n1. Viết một câu tóm tắt mỗi đoạn.\n2. Ghi ba dịch vụ và giới hạn đặt phòng.\n3. Trích một câu làm dẫn chứng cho lý do duy trì quầy đặt phòng.\n4. Giải thích skimming và scanning đã giúp bạn ở bước nào.\nNộp bài văn bản; ưu tiên ý đúng và dẫn chứng phù hợp.",
        'sample' => 'Paragraph 1 describes free services such as laptop borrowing, language clubs and study rooms. Paragraph 2 explains online booking and its two-hour daily limit. Paragraph 3 discusses barriers to digital access. The staffed desk helps visitors who prefer personal assistance or have unreliable internet access.',
        'feedback' => 'Tóm tắt nên bao quát ý chính từng đoạn; dùng dẫn chứng cụ thể thay vì chỉ liệt kê từ khóa.',
    ],
    [
        'objective' => 'Chọn đặc điểm nổi bật, viết overview và so sánh số liệu chính xác.',
        'body' => "WRITING TASK 1 — LANGUAGE COURSE ENROLMENT\n\nDữ liệu luyện tập tự tạo: số học viên đăng ký ba lớp ngoại ngữ.\nNăm        Lớp A        Lớp B        Lớp C\n2022          20              30              40\n2023          28              38              45\n2024          35              45              50\n\nĐề bài: Summarise the information by selecting the main features and making relevant comparisons.\n\nGợi ý cấu trúc: mở bài diễn đạt lại nội dung → overview về xu hướng chung → chi tiết có so sánh.\nCả ba lớp đều tăng; lớp C đông nhất ở mọi mốc. Lớp A và B cùng tăng 15 người, lớp C tăng 10 người.\nKhông suy diễn nguyên nhân từ bảng số liệu.",
        'quizzes' => [
            ['Which overview matches the table?', ['All classes declined', 'Only C increased', 'All classes grew, with C remaining the largest', 'The classes had equal enrolment'], 2, 'All three classes increased, and C had the most students in each year.'],
            ['How much did enrolment in Class A increase from 2022 to 2024?', ['10 students', '15 students', '20 students', '35 students'], 1, 'Class A rose from 20 to 35, an increase of 15 students.'],
            ['Which claim cannot be supported by the table alone?', ['C had 50 students in 2024', 'B grew over the period', 'A started with 20 students', 'Better teachers caused the increase'], 3, 'The table gives enrolment figures but no evidence about causes.'],
        ],
        'assignment' => "[DEMO Writing Task 1]\nDùng bảng Language Course Enrolment trong tài liệu. Viết bài luyện khoảng 150–180 từ bằng tiếng Anh.\nYêu cầu: mở bài, overview và các so sánh có số liệu. Không thêm nguyên nhân không có trong đề.\nTự kiểm tra: overview rõ? mốc năm đúng? đơn vị đúng? có so sánh thay vì liệt kê?\nBài được nộp cho giáo viên. Điểm phần trăm là điểm luyện tập, không quy đổi thành band IELTS.",
        'sample' => '[DEMO — đoạn mẫu rút gọn] The table compares enrolment in three language classes between 2022 and 2024. Overall, all three classes grew, while Class C remained the largest. Class A increased from 20 to 35 students and Class B from 30 to 45, a rise of 15 in each case. Class C grew more slowly, from 40 to 50 students.',
        'feedback' => 'Overview nên nêu xu hướng chung và lớp đông nhất; bổ sung so sánh chính xác và phát triển bài đầy đủ thay vì dùng đoạn mẫu rút gọn.',
    ],
    [
        'objective' => 'Nêu lập trường nhất quán, phát triển lý do và dùng ví dụ phù hợp.',
        'body' => "WRITING TASK 2 — ONLINE LEARNING\n\nĐề luyện tự tạo: Some people believe online learning should replace classroom learning. To what extent do you agree or disagree?\n\nChuẩn bị ý: học trực tuyến linh hoạt, tiết kiệm đi lại; lớp học hỗ trợ trao đổi trực tiếp và hoạt động nhóm. Có thể lập luận rằng trực tuyến bổ sung cho lớp học thay vì thay thế hoàn toàn.\n\nCấu trúc luyện: mở bài nêu quan điểm → đoạn 1 với lý do và ví dụ → đoạn 2 cân nhắc khía cạnh khác → kết luận nhất quán.\nMỗi đoạn cần một ý chính, giải thích và ví dụ. Tránh số liệu hoặc nghiên cứu không có nguồn.",
        'quizzes' => [
            ['Which sentence states a clear position?', ['Online learning exists', 'I believe online lessons should complement, rather than fully replace, classroom teaching', 'There are many students', 'Schools have classrooms'], 1, 'This sentence directly answers the question and states the writer’s position.'],
            ['Which example supports flexibility?', ['A worker watches recorded lessons after a late shift', 'A classroom has blue walls', 'A library has new shelves', 'A school opens a sports field'], 0, 'Recorded lessons allow a worker to study around a changing schedule.'],
            ['What should the conclusion do?', ['Introduce an unrelated topic', 'List every sentence again', 'Restate the position consistently', 'Invent a study'], 2, 'The conclusion should summarize the argument without changing the established position.'],
        ],
        'assignment' => "[DEMO Writing Task 2]\nTrả lời đề Online Learning trong tài liệu. Viết bài luyện khoảng 250–280 từ.\nNêu quan điểm rõ, hai luận điểm có giải thích và ví dụ, kết luận nhất quán.\nTự kiểm tra: trả lời đúng đề? mỗi đoạn có ý chính? ví dụ hỗ trợ lập luận? liên kết và ngữ pháp rõ?\nNộp bài cho giáo viên. Nhận xét demo không phải kết quả chấm band IELTS chính thức.",
        'sample' => '[DEMO — đoạn mẫu rút gọn] I believe online learning should complement classroom teaching rather than replace it completely. Recorded lessons let workers study around changing schedules. However, direct discussion and group activities can be easier in a classroom. A blended approach can preserve flexibility while giving students opportunities to interact in person.',
        'feedback' => 'Giữ lập trường nhất quán; phát triển mỗi luận điểm bằng giải thích và ví dụ cụ thể. Đoạn mẫu rút gọn chưa phải một bài hoàn chỉnh.',
    ],
    [
        'objective' => 'Mở rộng câu trả lời bằng trải nghiệm, lý do và ví dụ cụ thể.',
        'body' => "SPEAKING — A SKILL YOU LEARNED\n\nBài demo dùng dàn ý văn bản, chưa có bản ghi âm hoặc chấm phát âm.\n\nWarm-up: What do you enjoy learning? Why?\nPrompt: Describe a skill you learned recently. Explain what it was, how you learned it, what was difficult and why it is useful.\nFollow-up: Is it easier to learn alone or with other people?\n\nGợi ý dàn ý: học nấu ăn → xem hướng dẫn và luyện cuối tuần → khó kiểm soát thời gian → tự chuẩn bị bữa ăn.\nMở rộng bằng chi tiết cá nhân; liên kết quá khứ, khó khăn và kết quả. Tránh học thuộc một câu trả lời chung cho mọi đề.",
        'quizzes' => [
            ['Which answer gives both an activity and a reason?', ['Cooking.', 'Last month.', 'At home.', 'I enjoy learning to cook because it helps me prepare healthier meals.'], 3, 'The answer names the activity and explains why it matters.'],
            ['Which detail develops the skill-learning story?', ['I practised a new recipe each weekend and learned to manage the timing', 'It was a skill', 'There are many skills', 'Some people learn things'], 0, 'A concrete practice routine and challenge make the story more specific.'],
            ['Which response balances two ways of learning?', ['Learning alone is always perfect', 'Studying alone offers flexibility, while a group can provide feedback', 'Groups never help', 'Nobody should learn alone'], 1, 'This answer explains an advantage of each option rather than making an unsupported absolute claim.'],
        ],
        'assignment' => "[DEMO Speaking — dàn ý văn bản, chưa có bản ghi âm]\nChuẩn bị câu trả lời cho A Skill You Learned trong tài liệu.\nNộp dàn ý gồm: kỹ năng, cách học, khó khăn, ích lợi và một ví dụ cá nhân. Thêm câu trả lời cho câu hỏi học một mình hay theo nhóm.\nBạn có thể luyện nói ngoài hệ thống. Bài demo này không đánh giá phát âm, độ trôi chảy hay band Speaking từ văn bản.",
        'sample' => '[DEMO — dàn ý, không phải bản ghi âm] Skill: cooking simple meals. Method: watched short tutorials and practised at weekends. Challenge: timing several ingredients. Benefit: preparing healthier lunches. Example: made a vegetable dish for my family. Follow-up: individual practice is flexible, while group learning offers feedback.',
        'feedback' => 'Dàn ý có chi tiết cụ thể; khi luyện nói hãy kết nối các ý thành câu trả lời tự nhiên. Chưa có bằng chứng audio để nhận xét phát âm hoặc độ trôi chảy.',
    ],
];
