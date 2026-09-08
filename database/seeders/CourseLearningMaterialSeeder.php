<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use Illuminate\Database\Seeder;

class CourseLearningMaterialSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================================
        // 1. KHÓA HỌC 1: TIẾNG ANH CƠ BẢN A1 (ENGLISH BASICS A1)
        // =========================================================================
        $course1 = Course::firstOrNew(['id' => 1]);
        $course1->fill([
            'title' => 'English Basics - Tiếng Anh Giao Tiếp Cơ Bản A1',
            'slug' => 'english-basics-a1',
            'description' => 'Khóa học nền tảng toàn diện dành cho người mới bắt đầu hoặc mất gốc tiếng Anh. Học viên sẽ được trang bị hệ thống từ vựng cốt lõi, phát âm chuẩn IPA, ngữ pháp nền tảng và các mẫu câu giao tiếp tự tin trong đời sống hàng ngày.',
            'thumbnail' => '/images/course-a1.png',
            'level' => 'A1',
            'order' => 1,
            'is_published' => true,
        ]);
        $course1->save();

        // Xóa các activity rác hoặc cũ của Course 1 để chuẩn hóa
        $lessonIds1 = Lesson::where('course_id', $course1->id)->pluck('id');
        Activity::whereIn('lesson_id', $lessonIds1)->delete();

        // ── Lesson 1.1: Chào hỏi, Làm quen & Tự giới thiệu bản thân ──
        $l1_1 = Lesson::updateOrCreate(
            ['course_id' => $course1->id, 'order' => 1],
            [
                'title' => 'Bài 1: Chào hỏi, Làm quen & Tự giới thiệu bản thân',
                'description' => 'Nắm vững các câu chào hỏi trang trọng và thân mật, cách giới thiệu tên tuổi, nghề nghiệp và quê quán.',
                'unlock_condition_score' => 0,
                'is_free_trial' => true,
            ]
        );

        // Act 1.1.1: Flashcards Từ vựng
        Activity::create([
            'lesson_id' => $l1_1->id,
            'title' => '📖 Từ vựng: Lời chào & Mẫu câu tự giới thiệu',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => true,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Hello', 'phonetic' => '/həˈloʊ/', 'meaning' => 'Xin chào (thân mật)', 'example' => 'Hello, nice to meet you!'],
                ['word' => 'Good morning', 'phonetic' => '/ɡʊd ˈmɔːrnɪŋ/', 'meaning' => 'Chào buổi sáng (trước 12h trưa)', 'example' => 'Good morning, class! Please sit down.'],
                ['word' => 'Good afternoon', 'phonetic' => '/ɡʊd ˌæftərˈnuːn/', 'meaning' => 'Chào buổi chiều (12h - 18h)', 'example' => 'Good afternoon, Mr. David.'],
                ['word' => 'Good evening', 'phonetic' => '/ɡʊd ˈiːvnɪŋ/', 'meaning' => 'Chào buổi tối (sau 18h)', 'example' => 'Good evening, ladies and gentlemen.'],
                ['word' => 'Nice to meet you', 'phonetic' => '/naɪs tuː miːt juː/', 'meaning' => 'Rất vui được gặp bạn', 'example' => 'I am Sarah. Nice to meet you!'],
                ['word' => 'Introduce', 'phonetic' => '/ˌɪntrəˈdjuːs/', 'meaning' => 'Giới thiệu', 'example' => 'Let me introduce myself. My name is Linh.'],
                ['word' => 'Country', 'phonetic' => '/ˈkʌntri/', 'meaning' => 'Quốc gia, đất nước', 'example' => 'Vietnam is a beautiful country.'],
                ['word' => 'Hometown', 'phonetic' => '/ˈhoʊmtaʊn/', 'meaning' => 'Quê hương, quê nhà', 'example' => 'My hometown is Da Nang city.'],
            ],
        ]);

        // Act 1.1.2: Video bài giảng
        Activity::create([
            'lesson_id' => $l1_1->id,
            'title' => '🎬 Video: Kỹ năng Chào hỏi & Tự giới thiệu chuẩn bản xứ',
            'type' => Activity::TYPE_VIDEO,
            'order' => 2,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => true,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'video_url' => 'https://www.youtube.com/watch?v=Fw0rdhmsdd0',
                'title' => 'English Greetings & Self Introduction Lesson',
                'description' => 'Trong video này, giáo viên bản ngữ sẽ hướng dẫn bạn cách phát âm chuẩn các mẫu câu chào hỏi hàng ngày, sự khác biệt giữa giao tiếp trang trọng (formal) và thân thiện (informal), cùng ngữ điệu tự nhiên khi bắt chuyện với người nước ngoài.',
                'duration' => '08:45',
            ],
        ]);

        // Act 1.1.3: Bài giảng Ngữ pháp
        Activity::create([
            'lesson_id' => $l1_1->id,
            'title' => '📐 Ngữ pháp: Đại từ nhân xưng & Động từ TO BE (am / is / are)',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 3,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Chuyên đề: Đại từ nhân xưng & Động từ TO BE ở Hiện tại đơn',
                'explanation' => 'Động từ TO BE có 3 dạng ở thì Hiện tại đơn: am, is, are. Nó mang nghĩa là "thì, là, ở", dùng để nối chủ ngữ với danh từ (nghề nghiệp, tên gọi) hoặc tính từ (tính chất, cảm xúc, quốc tịch).',
                'rules' => [
                    'Chủ ngữ "I" luôn đi với "am" (Viết tắt: I\'m) → I am a student.',
                    'Chủ ngữ số ít "He / She / It / Danh từ số ít" đi với "is" (Viết tắt: He\'s, She\'s) → She is a doctor.',
                    'Chủ ngữ số nhiều "You / We / They / Danh từ số nhiều" đi với "are" (Viết tắt: You\'re, We\'re) → We are from Vietnam.',
                    'Thể phủ định (Negative): Thêm "NOT" sau TO BE → S + am/is/are + not (isn\'t / aren\'t).',
                    'Thể nghi vấn (Question): Đảo TO BE lên đầu câu → Am/Is/Are + S + ...? (Are you ready? → Yes, I am / No, I\'m not).',
                ],
                'examples' => [
                    'Hello! My name is Linh. I am twenty-two years old and I am from Hanoi.',
                    'This is Peter. He is my English teacher. He is from the United Kingdom.',
                    'Are they your classmates? - Yes, they are. They are very friendly.',
                    'I am not tired today. I am excited to learn English!',
                ],
            ],
        ]);

        // Act 1.1.4: Luyện nghe Audio
        Activity::create([
            'lesson_id' => $l1_1->id,
            'title' => '🎧 Luyện nghe: Cuộc trò chuyện làm quen lần đầu (First Meeting)',
            'type' => Activity::TYPE_AUDIO_LISTENING,
            'order' => 4,
            'estimated_minutes' => 12,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                'title' => 'First Meeting at an International Cafe',
                'transcript' => "David: Excuse me, is this seat taken?\nSarah: No, it isn't. Please have a seat!\nDavid: Thank you! By the way, my name is David. Nice to meet you.\nSarah: Hi David! I'm Sarah. Nice to meet you too.\nDavid: Are you a student here, Sarah?\nSarah: Yes, I am. I study Graphic Design. Where are you from?\nDavid: I'm from Melbourne, Australia. I'm currently traveling and learning Vietnamese.\nSarah: Wow, that's awesome! Welcome to Vietnam!",
            ],
        ]);

        // Act 1.1.5: Quiz CBT kiểm tra bài 1
        Activity::create([
            'lesson_id' => $l1_1->id,
            'title' => '🎯 Quiz CBT: Kiểm tra kiến thức Bài 1',
            'type' => Activity::TYPE_QUIZ,
            'order' => 5,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 10,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Chọn dạng đúng của động từ TO BE để hoàn thành câu: "She _____ an English teacher at my school."',
                        'question_type' => 'mcq',
                        'options' => ['am', 'is', 'are', 'be'],
                        'answer' => 1,
                        'correct_answer' => 'is',
                        'explanation' => 'Chủ ngữ "She" là ngôi thứ 3 số ít nên động từ TO BE tương ứng ở thì hiện tại đơn là "is".',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Khi gặp một người bạn mới vào lúc 9 giờ sáng, câu chào nào sau đây là phù hợp và lịch sự nhất?',
                        'question_type' => 'mcq',
                        'options' => ['Good afternoon', 'Good evening', 'Good morning', 'Good night'],
                        'answer' => 2,
                        'correct_answer' => 'Good morning',
                        'explanation' => '"Good morning" được dùng để chào hỏi từ sáng sớm cho đến 12h trưa.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Đúng hay Sai: Câu "They is from Vietnam." là một câu đúng ngữ pháp tiếng Anh.',
                        'question_type' => 'true_false',
                        'options' => ['True (Đúng)', 'False (Sai)'],
                        'answer' => 1,
                        'correct_answer' => 'False (Sai)',
                        'explanation' => 'Sai. Chủ ngữ "They" là đại từ số nhiều nên phải đi với động từ TO BE là "are" (They are from Vietnam).',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Nối đại từ nhân xưng ở cột bên trái với dạng động từ TO BE tương ứng ở cột bên phải:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['I' => 'I', 'You' => 'You', 'He' => 'He'],
                            'right' => ['am', 'are', 'is'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['I' => 'am', 'You' => 'are', 'He' => 'is']),
                        'explanation' => 'Quy tắc chia TO BE: I am, You are, He is.',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Điền một từ thích hợp vào chỗ trống để hoàn thành câu chào quen thuộc: "Nice to _____ you!"',
                        'question_type' => 'fill_blank',
                        'options' => ['meet'],
                        'answer' => 0,
                        'correct_answer' => 'meet',
                        'explanation' => 'Cụm từ cố định trong tiếng Anh: "Nice to meet you" có nghĩa là "Rất vui được gặp bạn".',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A1',
                    ],
                ],
            ],
        ]);

        // ── Lesson 1.2: Gia đình & Nghề nghiệp (Family & Occupations) ──
        $l1_2 = Lesson::updateOrCreate(
            ['course_id' => $course1->id, 'order' => 2],
            [
                'title' => 'Bài 2: Gia đình & Nghề nghiệp (Family & Occupations)',
                'description' => 'Mở rộng vốn từ vựng về các mối quan hệ gia đình, các ngành nghề thông dụng và cách sử dụng tính từ sở hữu.',
                'unlock_condition_score' => 60,
                'is_free_trial' => false,
            ]
        );

        // Act 1.2.1: Từ vựng Gia đình
        Activity::create([
            'lesson_id' => $l1_2->id,
            'title' => '📖 Từ vựng: Các thành viên trong Gia đình (Family Members)',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Parents', 'phonetic' => '/ˈperənts/', 'meaning' => 'Bố mẹ (phụ huynh)', 'example' => 'My parents live in Da Nang.'],
                ['word' => 'Father', 'phonetic' => '/ˈfɑːðər/', 'meaning' => 'Bố, cha', 'example' => 'My father is an engineer.'],
                ['word' => 'Mother', 'phonetic' => '/ˈmʌðər/', 'meaning' => 'Mẹ', 'example' => 'My mother cooks very well.'],
                ['word' => 'Brother', 'phonetic' => '/ˈbrʌðər/', 'meaning' => 'Anh / Em trai', 'example' => 'I have an older brother.'],
                ['word' => 'Sister', 'phonetic' => '/ˈsɪstər/', 'meaning' => 'Chị / Em gái', 'example' => 'My sister is a high school student.'],
                ['word' => 'Grandfather', 'phonetic' => '/ˈɡrænfɑːðər/', 'meaning' => 'Ông nội / Ông ngoại', 'example' => 'My grandfather loves reading newspapers.'],
                ['word' => 'Grandmother', 'phonetic' => '/ˈɡrænmʌðər/', 'meaning' => 'Bà nội / Bà ngoại', 'example' => 'My grandmother is 75 years old.'],
                ['word' => 'Daughter', 'phonetic' => '/ˈdɔːtər/', 'meaning' => 'Con gái', 'example' => 'They have one son and one daughter.'],
            ],
        ]);

        // Act 1.2.2: Từ vựng Nghề nghiệp
        Activity::create([
            'lesson_id' => $l1_2->id,
            'title' => '📖 Từ vựng: Các ngành nghề phổ biến (Jobs & Occupations)',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 2,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Teacher', 'phonetic' => '/ˈtiːtʃər/', 'meaning' => 'Giáo viên', 'example' => 'She is an elementary school teacher.'],
                ['word' => 'Doctor', 'phonetic' => '/ˈdɑːktər/', 'meaning' => 'Bác sĩ', 'example' => 'The doctor is examining the patient.'],
                ['word' => 'Nurse', 'phonetic' => '/nɜːrs/', 'meaning' => 'Y tá, điều dưỡng', 'example' => 'The nurse is very attentive and kind.'],
                ['word' => 'Engineer', 'phonetic' => '/ˌendʒɪˈnɪr/', 'meaning' => 'Kỹ sư', 'example' => 'He works as a civil engineer.'],
                ['word' => 'Chef', 'phonetic' => '/ʃef/', 'meaning' => 'Đầu bếp', 'example' => 'The head chef prepares delicious Italian pasta.'],
                ['word' => 'Police officer', 'phonetic' => '/pəˈliːs ˌɔːfɪsər/', 'meaning' => 'Cảnh sát, công an', 'example' => 'My uncle is a police officer.'],
            ],
        ]);

        // Act 1.2.3: Ngữ pháp Tính từ sở hữu
        Activity::create([
            'lesson_id' => $l1_2->id,
            'title' => '📐 Ngữ pháp: Tính từ sở hữu (my, your, his, her, their, our) & Sở hữu cách (\'s)',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 3,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Quy tắc sử dụng Tính từ sở hữu và Sở hữu cách',
                'explanation' => 'Tính từ sở hữu luôn đứng trước danh từ để chỉ sự sở hữu hoặc mối quan hệ. Sở hữu cách (\'s) được dùng sau danh từ chỉ người.',
                'rules' => [
                    'I → my (của tôi) | You → your (của bạn) | We → our (của chúng tôi) | They → their (của họ)',
                    'He → his (của anh ấy) | She → her (của cô ấy) | It → its (của nó)',
                    'Sở hữu cách: Danh từ số ít + \'s + Danh từ sở hữu → Mary\'s cat (Con mèo của Mary), My brother\'s car (Xe ô tô của anh tôi).',
                    'Lưu ý: Không dùng mạo từ (a, an, the) trước tính từ sở hữu.',
                ],
                'examples' => [
                    'This is my mother. Her name is Mai.',
                    'Peter is an engineer. His company is in Ho Chi Minh City.',
                    'What is your father\'s job? - He is a doctor.',
                    'Our family has four members: my parents, my sister, and me.',
                ],
            ],
        ]);

        // Act 1.2.4: Quiz Bài 2
        Activity::create([
            'lesson_id' => $l1_2->id,
            'title' => '🎯 Quiz CBT: Gia đình & Tính từ sở hữu',
            'type' => Activity::TYPE_QUIZ,
            'order' => 4,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 10,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Chọn tính từ sở hữu thích hợp để điền vào chỗ trống: "John loves reading. _____ favorite hobby is books."',
                        'question_type' => 'mcq',
                        'options' => ['Her', 'His', 'My', 'Their'],
                        'answer' => 1,
                        'correct_answer' => 'His',
                        'explanation' => 'John là nam (ngôi thứ 3 số ít giống đực) nên tính từ sở hữu tương ứng là "His".',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Từ nào sau đây có nghĩa là "Bố mẹ" trong tiếng Anh?',
                        'question_type' => 'mcq',
                        'options' => ['Grandparents', 'Children', 'Parents', 'Cousins'],
                        'answer' => 2,
                        'correct_answer' => 'Parents',
                        'explanation' => '"Parents" là danh từ số nhiều chỉ cả bố và mẹ.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Nối nghề nghiệp bằng tiếng Anh với nghĩa tiếng Việt tương ứng:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['Teacher' => 'Teacher', 'Doctor' => 'Doctor', 'Chef' => 'Chef'],
                            'right' => ['Giáo viên', 'Bác sĩ', 'Đầu bếp'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['Teacher' => 'Giáo viên', 'Doctor' => 'Bác sĩ', 'Chef' => 'Đầu bếp']),
                        'explanation' => 'Teacher: Giáo viên, Doctor: Bác sĩ, Chef: Đầu bếp.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Điền từ sở hữu cách phù hợp: "This is my _____ (brother) laptop."',
                        'question_type' => 'fill_blank',
                        'options' => ["brother's"],
                        'answer' => 0,
                        'correct_answer' => "brother's",
                        'explanation' => 'Thêm \'s vào sau danh từ số ít "brother" để tạo sở hữu cách: brother\'s.',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                ],
            ],
        ]);

        // ── Lesson 1.3: Số đếm, Thời gian & Cuộc sống hàng ngày ──
        $l1_3 = Lesson::updateOrCreate(
            ['course_id' => $course1->id, 'order' => 3],
            [
                'title' => 'Bài 3: Số đếm, Thời gian & Thói quen hàng ngày',
                'description' => 'Học cách đọc số đếm, ngày tháng, cách nói giờ và các giới từ chỉ thời gian thông dụng (in, on, at).',
                'unlock_condition_score' => 60,
                'is_free_trial' => false,
            ]
        );

        // Act 1.3.1: Từ vựng Số đếm & Ngày tháng
        Activity::create([
            'lesson_id' => $l1_3->id,
            'title' => '📖 Từ vựng: Số đếm, Ngày trong tuần & Các tháng trong năm',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Monday', 'phonetic' => '/ˈmʌndeɪ/', 'meaning' => 'Thứ Hai', 'example' => 'I have an English class on Monday.'],
                ['word' => 'Friday', 'phonetic' => '/ˈfraɪdeɪ/', 'meaning' => 'Thứ Sáu', 'example' => 'Friday is my favorite day of the week.'],
                ['word' => 'Weekend', 'phonetic' => '/ˈwiːkend/', 'meaning' => 'Cuối tuần (Thứ Bảy & Chủ Nhật)', 'example' => 'We usually relax on the weekend.'],
                ['word' => 'Morning', 'phonetic' => '/ˈmɔːrnɪŋ/', 'meaning' => 'Buổi sáng', 'example' => 'I wake up early in the morning.'],
                ['word' => 'Noon', 'phonetic' => '/nuːn/', 'meaning' => 'Buổi trưa (chính xác 12h)', 'example' => 'Let\'s have lunch together at noon.'],
                ['word' => 'Midnight', 'phonetic' => '/ˈmɪdnaɪt/', 'meaning' => 'Nửa đêm (24h đêm)', 'example' => 'He rarely stays up past midnight.'],
                ['word' => 'Clock', 'phonetic' => '/klɑːk/', 'meaning' => 'Đồng hồ treo tường / để bàn', 'example' => 'The clock on the wall shows 3 PM.'],
                ['word' => 'O\'clock', 'phonetic' => '/əˈklɑːk/', 'meaning' => 'Giờ đúng (chẵn giờ)', 'example' => 'The meeting begins at seven o\'clock.'],
            ],
        ]);

        // Act 1.3.2: Video Cách đọc giờ
        Activity::create([
            'lesson_id' => $l1_3->id,
            'title' => '🎬 Video: Cách hỏi và trả lời về giờ giấc trong tiếng Anh',
            'type' => Activity::TYPE_VIDEO,
            'order' => 2,
            'estimated_minutes' => 12,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'video_url' => 'https://www.youtube.com/watch?v=fq2tRfHMFbI',
                'title' => 'Telling Time in English - Easy Rules & Examples',
                'description' => 'Hướng dẫn đầy đủ 2 cách đọc giờ trong tiếng Anh: Cách đọc đơn giản (Giờ + Phút) và cách đọc truyền thống sử dụng "Past" (hơn) và "To" (kém).',
                'duration' => '07:15',
            ],
        ]);

        // Act 1.3.3: Ngữ pháp Giới từ In / On / At
        Activity::create([
            'lesson_id' => $l1_3->id,
            'title' => '📐 Ngữ pháp: Giới từ chỉ thời gian (In, On, At) thần tốc',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 3,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Quy tắc tam giác giới từ chỉ thời gian: AT - ON - IN',
                'explanation' => 'Hãy ghi nhớ quy tắc từ hẹp đến rộng: AT (cụ thể nhất: giờ giấc, thời điểm), ON (ngày, thứ trong tuần), IN (khoảng thời gian rộng: tháng, năm, mùa, thế kỷ).',
                'rules' => [
                    'AT + Giờ cụ thể / Dịp lễ ngắn: at 6:30 AM, at noon, at midnight, at Christmas.',
                    'ON + Ngày cụ thể / Thứ trong tuần / Ngày lễ có từ "Day": on Monday, on July 4th, on my birthday, on Christmas Day.',
                    'IN + Tháng / Năm / Mùa / Các buổi trong ngày: in July, in 2026, in summer, in the morning, in the evening.',
                ],
                'examples' => [
                    'The class starts at 8:00 AM sharp.',
                    'We don\'t go to school on Sundays.',
                    'Vietnam is very beautiful in the spring.',
                    'He was born in 2002.',
                ],
            ],
        ]);

        // Act 1.3.4: Đề thi Tổng kết Khóa học A1
        Activity::create([
            'lesson_id' => $l1_3->id,
            'title' => '🏆 Đề thi Tổng kết Khóa học A1 (Comprehensive Final Exam)',
            'type' => Activity::TYPE_QUIZ,
            'order' => 4,
            'estimated_minutes' => 20,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 15,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Chọn giới từ chính xác để điền vào chỗ trống: "The concert takes place _____ 8:00 PM tonight."',
                        'question_type' => 'mcq',
                        'options' => ['in', 'on', 'at', 'to'],
                        'answer' => 2,
                        'correct_answer' => 'at',
                        'explanation' => 'Dùng giới từ "at" trước mốc giờ cụ thể trong ngày (at 8:00 PM).',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Chọn giới từ chính xác: "My birthday is _____ October 15th."',
                        'question_type' => 'mcq',
                        'options' => ['at', 'on', 'in', 'by'],
                        'answer' => 1,
                        'correct_answer' => 'on',
                        'explanation' => 'Có ngày cụ thể trong tháng (October 15th) nên phải dùng giới từ "on".',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Đúng hay Sai: Cụm từ "in Monday" là cách diễn đạt chính xác trong tiếng Anh.',
                        'question_type' => 'true_false',
                        'options' => ['True (Đúng)', 'False (Sai)'],
                        'answer' => 1,
                        'correct_answer' => 'False (Sai)',
                        'explanation' => 'Sai. Với các thứ trong tuần ta phải dùng giới từ "on" (on Monday).',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Nối cụm thời gian ở cột bên trái với giới từ chính xác ở cột bên phải:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['7:30 AM' => '7:30 AM', 'Sunday' => 'Sunday', 'the morning' => 'the morning'],
                            'right' => ['at', 'on', 'in'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['7:30 AM' => 'at', 'Sunday' => 'on', 'the morning' => 'in']),
                        'explanation' => 'at 7:30 AM (giờ cụ thể), on Sunday (thứ trong tuần), in the morning (buổi trong ngày).',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                    [
                        'question' => 'Điền từ còn thiếu vào chỗ trống: "She always drinks coffee _____ the morning."',
                        'question_type' => 'fill_blank',
                        'options' => ['in'],
                        'answer' => 0,
                        'correct_answer' => 'in',
                        'explanation' => 'Cụm từ chỉ buổi trong ngày dùng giới từ "in": in the morning.',
                        'skill' => 'grammar',
                        'difficulty' => 'A1',
                    ],
                ],
            ],
        ]);


        // =========================================================================
        // 2. KHÓA HỌC 2: TIẾNG ANH SƠ CẤP A2 (ENGLISH ELEMENTARY A2)
        // =========================================================================
        $course2 = Course::firstOrNew(['id' => 2]);
        $course2->fill([
            'title' => 'English Elementary - Tiếng Anh Sơ Cấp A2',
            'slug' => 'english-elementary-a2',
            'description' => 'Khóa học nâng cao vốn từ vựng học thuật và giao tiếp theo tiêu chuẩn CEFR A2. Bạn sẽ học cách diễn đạt sở thích, kể lại các trải nghiệm, miêu tả thời tiết và tự tin mua sắm, gọi món ăn tại nhà hàng quốc tế.',
            'thumbnail' => '/images/course-a2.png',
            'level' => 'A2',
            'order' => 2,
            'is_published' => true,
        ]);
        $course2->save();

        // Xóa các activity rác hoặc cũ của Course 2 để chuẩn hóa
        $lessonIds2 = Lesson::where('course_id', $course2->id)->pluck('id');
        Activity::whereIn('lesson_id', $lessonIds2)->delete();

        // ── Lesson 2.1: Sở thích & Hoạt động giải trí (Hobbies & Free Time) ──
        $l2_1 = Lesson::updateOrCreate(
            ['course_id' => $course2->id, 'order' => 1],
            [
                'title' => 'Bài 1: Sở thích, Thể thao & Hoạt động giải trí',
                'description' => 'Khám phá các từ vựng về sở thích, cách sử dụng thì Hiện tại đơn và các trạng từ chỉ tần suất.',
                'unlock_condition_score' => 0,
                'is_free_trial' => true,
            ]
        );

        // Act 2.1.1: Flashcards Sở thích
        Activity::create([
            'lesson_id' => $l2_1->id,
            'title' => '📖 Từ vựng: Sở thích & Các môn thể thao phổ biến',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => true,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Photography', 'phonetic' => '/fəˈtɑːɡrəfi/', 'meaning' => 'Nhiếp ảnh, chụp ảnh', 'example' => 'His main hobby is landscape photography.'],
                ['word' => 'Swimming', 'phonetic' => '/ˈswɪmɪŋ/', 'meaning' => 'Bơi lội', 'example' => 'I go swimming twice a week to stay fit.'],
                ['word' => 'Playing guitar', 'phonetic' => '/ˈpleɪɪŋ ɡɪˈtɑːr/', 'meaning' => 'Chơi đàn ghi-ta', 'example' => 'He loves playing acoustic guitar in the evening.'],
                ['word' => 'Hiking', 'phonetic' => '/ˈhaɪkɪŋ/', 'meaning' => 'Đi bộ leo núi', 'example' => 'We are planning a hiking trip to Sapa.'],
                ['word' => 'Gardening', 'phonetic' => '/ˈɡɑːrdnɪŋ/', 'meaning' => 'Làm vườn, chăm cây', 'example' => 'My grandmother finds gardening very relaxing.'],
                ['word' => 'Traveling', 'phonetic' => '/ˈtrævəlɪŋ/', 'meaning' => 'Du lịch, khám phá', 'example' => 'Traveling around the world is her dream.'],
                ['word' => 'Cookery', 'phonetic' => '/ˈkʊkəri/', 'meaning' => 'Nghệ thuật nấu ăn', 'example' => 'She attends a weekend cookery workshop.'],
                ['word' => 'Board game', 'phonetic' => '/ˈbɔːrd ɡeɪm/', 'meaning' => 'Trò chơi cờ bàn (cờ vua, cờ tỉ phú)', 'example' => 'We enjoy playing board games with friends.'],
            ],
        ]);

        // Act 2.1.2: Ngữ pháp Thì Hiện tại đơn
        Activity::create([
            'lesson_id' => $l2_1->id,
            'title' => '📐 Ngữ pháp: Thì Hiện tại đơn (Present Simple) & Trạng từ tần suất',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 2,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => true,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Thì Hiện tại đơn & Vị trí của Trạng từ chỉ tần suất',
                'explanation' => 'Thì Hiện tại đơn dùng để diễn đạt thói quen hàng ngày, sở thích, sự thật hiển nhiên hoặc thời gian biểu cố định.',
                'rules' => [
                    'Khẳng định: S (I/You/We/They) + V(nguyên thể) | S (He/She/It) + V(s/es). Ví dụ: I play football. He plays tennis.',
                    'Phủ định: S + do not / does not (don\'t / doesn\'t) + V(nguyên thể). Ví dụ: She doesn\'t like coffee.',
                    'Nghi vấn: Do / Does + S + V(nguyên thể)? Ví dụ: Do you live in Hanoi? → Yes, I do.',
                    'Trạng từ chỉ tần suất (always, usually, often, sometimes, rarely, never) đứng TRƯỚC động từ thường và đứng SAU động từ TO BE.',
                ],
                'examples' => [
                    'I always read a book before going to sleep.',
                    'He is never late for his English morning class.',
                    'She often goes swimming with her brother on weekends.',
                    'Water freezes at zero degrees Celsius.',
                ],
            ],
        ]);

        // Act 2.1.3: Audio Nghe hiểu
        Activity::create([
            'lesson_id' => $l2_1->id,
            'title' => '🎧 Luyện nghe: Phỏng vấn sở thích cuối tuần (Weekend Free Time Interview)',
            'type' => Activity::TYPE_AUDIO_LISTENING,
            'order' => 3,
            'estimated_minutes' => 12,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
                'title' => 'Interview with Emma about her Favorite Hobbies',
                'transcript' => "Interviewer: Good morning Emma! Thank you for joining us today. What do you usually do in your free time?\nEmma: Good morning! Well, I have quite a busy work schedule during the week, so on weekends, I always make time for my hobbies. My biggest passion is landscape photography.\nInterviewer: That sounds exciting! Do you travel a lot for your photography?\nEmma: Yes, I often go hiking in the mountains with my camera. Being close to nature helps me recharge. Occasionally, if the weather is rainy, I stay home and bake bread with my sister.\nInterviewer: Sounds like a wonderful and balanced lifestyle!",
            ],
        ]);

        // Act 2.1.4: Quiz CBT Bài 1 A2
        Activity::create([
            'lesson_id' => $l2_1->id,
            'title' => '🎯 Quiz CBT: Luyện tập Thì hiện tại đơn & Sở thích',
            'type' => Activity::TYPE_QUIZ,
            'order' => 4,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 10,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Chia động từ trong ngoặc ở thì Hiện tại đơn: "My brother _____ (play) guitar very well."',
                        'question_type' => 'mcq',
                        'options' => ['play', 'plays', 'playing', 'is play'],
                        'answer' => 1,
                        'correct_answer' => 'plays',
                        'explanation' => 'Chủ ngữ "My brother" là ngôi thứ 3 số ít nên động từ "play" thêm "s" thành "plays".',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Xác định vị trí chính xác của trạng từ "usually" trong câu sau: "He _____ late for work."',
                        'question_type' => 'mcq',
                        'options' => ['is usually', 'usually is', 'is usually not', 'usually are'],
                        'answer' => 0,
                        'correct_answer' => 'is usually',
                        'explanation' => 'Quy tắc: Trạng từ chỉ tần suất đứng SAU động từ TO BE (He is usually late).',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Nối từ chỉ sở thích tiếng Anh với định nghĩa tương ứng:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['Photography' => 'Photography', 'Hiking' => 'Hiking', 'Gardening' => 'Gardening'],
                            'right' => ['Chụp ảnh nghệ thuật', 'Đi bộ leo núi', 'Làm vườn chăm cây'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['Photography' => 'Chụp ảnh nghệ thuật', 'Hiking' => 'Đi bộ leo núi', 'Gardening' => 'Làm vườn chăm cây']),
                        'explanation' => 'Photography: Chụp ảnh; Hiking: Leo núi; Gardening: Làm vườn.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Điền dạng phủ định đúng của động từ: "They _____ (not / like) eating fast food."',
                        'question_type' => 'fill_blank',
                        'options' => ["don't like", "do not like"],
                        'answer' => 0,
                        'correct_answer' => "don't like",
                        'explanation' => 'Chủ ngữ "They" dùng trợ động từ "do not" (don\'t) + động từ nguyên thể like.',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                ],
            ],
        ]);

        // ── Lesson 2.2: Thời tiết & Kế hoạch tương lai (Weather & Future Plans) ──
        $l2_2 = Lesson::updateOrCreate(
            ['course_id' => $course2->id, 'order' => 2],
            [
                'title' => 'Bài 2: Thời tiết, Khí hậu & Kế hoạch tương lai',
                'description' => 'Miêu tả các hiện tượng thời tiết, 4 mùa trong năm và cách diễn đạt dự định tương lai với cấu trúc "Be going to".',
                'unlock_condition_score' => 60,
                'is_free_trial' => false,
            ]
        );

        // Act 2.2.1: Từ vựng Thời tiết & 4 Mùa
        Activity::create([
            'lesson_id' => $l2_2->id,
            'title' => '📖 Từ vựng: Hiện tượng Thời tiết & Các mùa trong năm',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Sunny', 'phonetic' => '/ˈsʌni/', 'meaning' => 'Có nắng, trời nắng', 'example' => 'It is a bright and sunny morning.'],
                ['word' => 'Rainy', 'phonetic' => '/ˈreɪni/', 'meaning' => 'Có mưa, nhiều mưa', 'example' => 'Take an umbrella, it is rainy outside.'],
                ['word' => 'Cloudy', 'phonetic' => '/ˈklaʊdi/', 'meaning' => 'Nhiều mây, râm mát', 'example' => 'The sky is cloudy, it might rain soon.'],
                ['word' => 'Windy', 'phonetic' => '/ˈwɪndi/', 'meaning' => 'Nhiều gió', 'example' => 'It is too windy to play badminton in the park.'],
                ['word' => 'Forecast', 'phonetic' => '/ˈfɔːrkæst/', 'meaning' => 'Bản tin dự báo (thời tiết)', 'example' => 'According to the weather forecast, tomorrow will be hot.'],
                ['word' => 'Temperature', 'phonetic' => '/ˈtemprətʃər/', 'meaning' => 'Nhiệt độ', 'example' => 'The temperature today is around 28 degrees Celsius.'],
                ['word' => 'Spring', 'phonetic' => '/sprɪŋ/', 'meaning' => 'Mùa xuân', 'example' => 'Flowers blossom beautifully in spring.'],
                ['word' => 'Autumn', 'phonetic' => '/ˈɔːtəm/', 'meaning' => 'Mùa thu', 'example' => 'Leaves turn golden yellow in autumn.'],
            ],
        ]);

        // Act 2.2.2: Video Dự báo thời tiết
        Activity::create([
            'lesson_id' => $l2_2->id,
            'title' => '🎬 Video: Cách nghe và hiểu bản tin Dự báo thời tiết tiếng Anh',
            'type' => Activity::TYPE_VIDEO,
            'order' => 2,
            'estimated_minutes' => 12,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'video_url' => 'https://www.youtube.com/watch?v=D1Z_v7A_6zY',
                'title' => 'Talking About the Weather in English',
                'description' => 'Học các mẫu câu giao tiếp tự nhiên về thời tiết của người bản xứ: hỏi thăm nhiệt độ, nhận xét về thời tiết và thảo luận trang phục phù hợp.',
                'duration' => '09:20',
            ],
        ]);

        // Act 2.2.3: Ngữ pháp Tương lai gần 'Be going to'
        Activity::create([
            'lesson_id' => $l2_2->id,
            'title' => '📐 Ngữ pháp: Thì Tương lai gần (Be going to) & Phân biệt với "Will"',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 3,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Cấu trúc "Be going to" diễn tả Kế hoạch & Dự đoán có căn cứ',
                'explanation' => '"Be going to" dùng để nói về những kế hoạch, dự định đã được chuẩn bị trước thời điểm nói, hoặc dự đoán điều chắc chắn xảy ra dựa trên dấu hiệu hiện tại.',
                'rules' => [
                    'Công thức: S + am/is/are + going to + V(nguyên thể).',
                    'Dự định trước: I am going to visit my grandparents this weekend.',
                    'Dự đoán có bằng chứng: Look at those dark clouds! It is going to rain.',
                    'Phân biệt với "Will": "Will" dùng cho quyết định tức thời nảy ra tại lúc nói, hoặc lời hứa/dự đoán không có bằng chứng rõ ràng.',
                ],
                'examples' => [
                    'We are going to buy a new car next month.',
                    'Are you going to attend the seminar tomorrow?',
                    'She is not going to travel this summer because of work.',
                    'Watch out! You are going to drop that glass.',
                ],
            ],
        ]);

        // Act 2.2.4: Quiz Bài 2 A2
        Activity::create([
            'lesson_id' => $l2_2->id,
            'title' => '🎯 Quiz CBT: Thời tiết & Dự định tương lai',
            'type' => Activity::TYPE_QUIZ,
            'order' => 4,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 10,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Nhìn những đám mây đen kịt trên bầu trời: "Look at those dark clouds! It _____ rain soon."',
                        'question_type' => 'mcq',
                        'options' => ['is going to', 'will', 'is going', 'shall'],
                        'answer' => 0,
                        'correct_answer' => 'is going to',
                        'explanation' => 'Có bằng chứng trực tiếp ở hiện tại (dark clouds) nên dùng cấu trúc dự đoán "is going to rain".',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Từ vựng nào mô tả thời tiết có nhiều gió mạnh?',
                        'question_type' => 'mcq',
                        'options' => ['Sunny', 'Windy', 'Foggy', 'Rainy'],
                        'answer' => 1,
                        'correct_answer' => 'Windy',
                        'explanation' => '"Windy" xuất phát từ danh từ "wind" (gió), có nghĩa là trời nhiều gió.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Nối từ chỉ mùa trong năm với đặc trưng khí hậu:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['Summer' => 'Summer', 'Winter' => 'Winter', 'Spring' => 'Spring'],
                            'right' => ['Nắng nóng gay gắt', 'Lạnh giá buốt', 'Ấm áp hoa nở'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['Summer' => 'Nắng nóng gay gắt', 'Winter' => 'Lạnh giá buốt', 'Spring' => 'Ấm áp hoa nở']),
                        'explanation' => 'Summer: Nóng; Winter: Lạnh; Spring: Ấm áp.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Điền từ còn thiếu: "They are _____ to visit Ha Long Bay next Sunday."',
                        'question_type' => 'fill_blank',
                        'options' => ['going'],
                        'answer' => 0,
                        'correct_answer' => 'going',
                        'explanation' => 'Cấu trúc dự định: are going to visit.',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                ],
            ],
        ]);

        // ── Lesson 2.3: Mua sắm & Ăn uống tại nhà hàng (Shopping & Dining) ──
        $l2_3 = Lesson::updateOrCreate(
            ['course_id' => $course2->id, 'order' => 3],
            [
                'title' => 'Bài 3: Mua sắm, Ăn uống & Gọi món tại Nhà hàng',
                'description' => 'Học các mẫu câu giao tiếp tự tin khi đi mua sắm, hỏi giá cả, gọi món ăn và cách phân biệt danh từ đếm được / không đếm được.',
                'unlock_condition_score' => 60,
                'is_free_trial' => false,
            ]
        );

        // Act 2.3.1: Từ vựng Mua sắm & Ăn uống
        Activity::create([
            'lesson_id' => $l2_3->id,
            'title' => '📖 Từ vựng: Đi siêu thị, Giá cả & Dịch vụ nhà hàng',
            'type' => Activity::TYPE_VOCABULARY,
            'order' => 1,
            'estimated_minutes' => 10,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                ['word' => 'Discount', 'phonetic' => '/ˈdɪskaʊnt/', 'meaning' => 'Chiết khấu, giảm giá', 'example' => 'Is there any student discount available?'],
                ['word' => 'Receipt', 'phonetic' => '/rɪˈsiːt/', 'meaning' => 'Hóa đơn thanh toán', 'example' => 'Please keep your receipt for returns.'],
                ['word' => 'Cash', 'phonetic' => '/kæʃ/', 'meaning' => 'Tiền mặt', 'example' => 'Would you like to pay by card or in cash?'],
                ['word' => 'Customer', 'phonetic' => '/ˈkʌstəmər/', 'meaning' => 'Khách hàng', 'example' => 'The shop staff is always polite to customers.'],
                ['word' => 'Menu', 'phonetic' => '/ˈmenjuː/', 'meaning' => 'Thực đơn món ăn', 'example' => 'Could we please see the dessert menu?'],
                ['word' => 'Delicious', 'phonetic' => '/dɪˈlɪʃəs/', 'meaning' => 'Ngon miệng, thơm ngon', 'example' => 'This grilled salmon is absolutely delicious.'],
                ['word' => 'Affordable', 'phonetic' => '/əˈfɔːrdəbl/', 'meaning' => 'Giá cả phải chăng, hợp túi tiền', 'example' => 'The restaurant offers high quality food at affordable prices.'],
                ['word' => 'Order', 'phonetic' => '/ˈɔːrdər/', 'meaning' => 'Gọi món, đặt hàng', 'example' => 'Are you ready to order your main course?'],
            ],
        ]);

        // Act 2.3.2: Ngữ pháp How much vs How many
        Activity::create([
            'lesson_id' => $l2_3->id,
            'title' => '📐 Ngữ pháp: Danh từ Đếm được / Không đếm được & How Much / How Many',
            'type' => Activity::TYPE_GRAMMAR,
            'order' => 2,
            'estimated_minutes' => 15,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_MANUAL,
            'content' => [
                'title' => 'Phân biệt Danh từ Đếm được & Không đếm được và Câu hỏi số lượng / giá cả',
                'explanation' => 'Danh từ đếm được có thể đếm bằng số (1 quả táo, 2 quả táo). Danh từ không đếm được là chất lỏng, hạt nhỏ, khái niệm trừu tượng (nước, tiền, đường, thông tin).',
                'rules' => [
                    'HOW MANY + Danh từ đếm được số nhiều: How many apples do you want? (Hỏi về số lượng)',
                    'HOW MUCH + Danh từ không đếm được: How much water do you drink every day? (Hỏi về lượng)',
                    'HOW MUCH + Động từ TO BE / COST: How much is this shirt? / How much does it cost? (Hỏi về giá tiền)',
                    'Một số danh từ không đếm được điển hình: money, water, milk, coffee, information, rice, bread, furniture.',
                ],
                'examples' => [
                    'How much is that cup of cappuccino? - It is 45,000 VND.',
                    'How many cups of coffee do you drink a day? - Two cups.',
                    'How much money do you need for shopping?',
                    'There are many people at the supermarket today.',
                ],
            ],
        ]);

        // Act 2.3.3: Luyện nghe Gọi món
        Activity::create([
            'lesson_id' => $l2_3->id,
            'title' => '🎧 Luyện nghe: Gọi món tại Nhà hàng (Ordering at an Italian Restaurant)',
            'type' => Activity::TYPE_AUDIO_LISTENING,
            'order' => 3,
            'estimated_minutes' => 12,
            'is_visible' => true,
            'is_free_trial' => false,
            'completion_type' => Activity::COMPLETION_AUTO_VIEW,
            'content' => [
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
                'title' => 'Ordering Dinner at Luigi\'s Bistro',
                'transcript' => "Waiter: Good evening! Welcome to Luigi's Bistro. Do you have a table reserved?\nCustomer: Yes, a table for two under the name of Thomas, please.\nWaiter: Ah yes, right this way sir! Here are your menus. Would you like to start with some drinks?\nCustomer: Just sparkling water for now, thank you.\nWaiter: Are you ready to order or would you like a few more minutes?\nCustomer: We're ready! Could we please have one Seafood Pasta and one Margherita Pizza?\nWaiter: Excellent choice. And would you like any salad or appetizers to share?\nCustomer: Yes, a Caesar salad would be wonderful, thank you!",
            ],
        ]);

        // Act 2.3.4: Đề thi Tổng kết Khóa học A2
        Activity::create([
            'lesson_id' => $l2_3->id,
            'title' => '🏆 Đề thi Đánh giá Năng lực Cuối khóa A2 (Comprehensive Final Exam)',
            'type' => Activity::TYPE_QUIZ,
            'order' => 4,
            'estimated_minutes' => 20,
            'is_visible' => true,
            'is_free_trial' => false,
            'passing_grade' => 80.00,
            'time_limit_minutes' => 15,
            'completion_type' => Activity::COMPLETION_AUTO_GRADE,
            'content' => [
                'questions' => [
                    [
                        'question' => 'Chọn cụm từ chính xác để hỏi giá của chiếc áo khoác: "_____ is this winter jacket?"',
                        'question_type' => 'mcq',
                        'options' => ['How many', 'How much', 'How long', 'How often'],
                        'answer' => 1,
                        'correct_answer' => 'How much',
                        'explanation' => 'Dùng "How much" để hỏi giá tiền của một món đồ.',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Chọn từ thích hợp để điền vào câu hỏi số lượng: "How _____ students are there in your class?"',
                        'question_type' => 'mcq',
                        'options' => ['much', 'many', 'more', 'lot'],
                        'answer' => 1,
                        'correct_answer' => 'many',
                        'explanation' => '"Students" là danh từ đếm được số nhiều nên dùng "How many".',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Từ nào sau đây là Danh từ KHÔNG đếm được (Uncountable Noun)?',
                        'question_type' => 'mcq',
                        'options' => ['Table', 'Apple', 'Water', 'Student'],
                        'answer' => 2,
                        'correct_answer' => 'Water',
                        'explanation' => '"Water" (nước) là chất lỏng, danh từ không đếm được.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Nối từ vựng mua sắm với ý nghĩa tương ứng:',
                        'question_type' => 'matching',
                        'options' => [
                            'left' => ['Receipt' => 'Receipt', 'Discount' => 'Discount', 'Customer' => 'Customer'],
                            'right' => ['Hóa đơn thanh toán', 'Mức chiết khấu giảm giá', 'Khách hàng mua sắm'],
                        ],
                        'answer' => 0,
                        'correct_answer' => json_encode(['Receipt' => 'Hóa đơn thanh toán', 'Discount' => 'Mức chiết khấu giảm giá', 'Customer' => 'Khách hàng mua sắm']),
                        'explanation' => 'Receipt: Hóa đơn; Discount: Giảm giá; Customer: Khách hàng.',
                        'skill' => 'vocabulary',
                        'difficulty' => 'A2',
                    ],
                    [
                        'question' => 'Điền từ còn thiếu vào mẫu câu hỏi giá tiền: "How much _____ this book cost?"',
                        'question_type' => 'fill_blank',
                        'options' => ['does'],
                        'answer' => 0,
                        'correct_answer' => 'does',
                        'explanation' => 'Chủ ngữ "this book" là số ít, động từ thường "cost" nên trợ động từ là "does".',
                        'skill' => 'grammar',
                        'difficulty' => 'A2',
                    ],
                ],
            ],
        ]);
    }
}
