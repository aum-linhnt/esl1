<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use App\Models\QuestionBank;
use App\Models\LearnerSkill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── ROLES & PERMISSIONS ───
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view courses', 'create courses', 'edit courses', 'delete courses',
            'view lessons', 'create lessons', 'edit lessons', 'delete lessons',
            'manage users', 'manage roles',
            'view question bank', 'create questions', 'edit questions', 'delete questions',
            'view reports', 'access marketplace',
        ];

        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }

        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'web']);
        $studentRole->syncPermissions(['view courses', 'view lessons', 'view question bank', 'access marketplace']);

        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        $teacherRole->syncPermissions(['view courses', 'create courses', 'edit courses', 'view lessons', 'create lessons', 'edit lessons', 'view question bank', 'create questions', 'edit questions', 'view reports', 'access marketplace']);

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // ─── USERS ───
        $admin = User::create([
            'name' => 'Admin ESL',
            'username' => 'admin',
            'email' => 'admin@fsel.vn',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'coins' => 999,
            'current_level' => 'C2',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $student = User::create([
            'name' => 'Tuấn Linh',
            'username' => 'tuanlinh',
            'email' => 'tuanlinh@fsel.vn',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'trial_expired',
            'coins' => 24,
            'current_level' => 'A2',
            'trial_ends_at' => Carbon::now()->subDays(3),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');

        $teacher = User::create([
            'name' => 'Nguyễn Văn Giáo',
            'username' => 'giaovien1',
            'email' => 'giaovien1@fsel.vn',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'status' => 'active',
            'coins' => 150,
            'current_level' => 'C1',
            'email_verified_at' => now(),
        ]);
        $teacher->assignRole('teacher');

        // Initial Diagnostic Matrix for student
        $skillsData = [
            ['skill_type' => 'vocabulary', 'mastery_score' => 75, 'assessed_level' => 'B1'],
            ['skill_type' => 'grammar', 'mastery_score' => 60, 'assessed_level' => 'A2'],
            ['skill_type' => 'reading', 'mastery_score' => 70, 'assessed_level' => 'B1'],
            ['skill_type' => 'listening', 'mastery_score' => 50, 'assessed_level' => 'A2'],
        ];
        foreach ($skillsData as $sk) {
            LearnerSkill::create([
                'user_id' => $student->id,
                'skill_type' => $sk['skill_type'],
                'mastery_score' => $sk['mastery_score'],
                'assessed_level' => $sk['assessed_level'],
                'last_assessed_at' => now(),
            ]);
        }

        // ─── COURSE 1: ENGLISH BASICS A1 ───
        $course1 = Course::create([
            'title' => 'English Basics - Tiếng Anh Cơ Bản A1',
            'slug' => 'english-basics-a1',
            'description' => 'Khóa học tiếng Anh cơ bản dành cho người mới bắt đầu. Bạn sẽ học cách chào hỏi, giới thiệu bản thân, và các mẫu câu giao tiếp hàng ngày.',
            'thumbnail' => '/images/course-a1.png',
            'level' => 'A1',
            'order' => 1,
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        // Course 1 - Lesson 1
        $lesson1_1 = Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Bài 1: Chào hỏi & Giới thiệu bản thân',
            'order' => 1,
            'unlock_condition_score' => 0,
            'is_free_trial' => true,
        ]);

        Activity::create([
            'lesson_id' => $lesson1_1->id,
            'title' => 'Từ vựng: Lời chào cơ bản',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'Hello', 'meaning' => 'Xin chào', 'phonetic' => '/həˈloʊ/', 'example' => 'Hello, how are you?'],
                ['word' => 'Good morning', 'meaning' => 'Chào buổi sáng', 'phonetic' => '/ɡʊd ˈmɔːrnɪŋ/', 'example' => 'Good morning, teacher!'],
                ['word' => 'Good afternoon', 'meaning' => 'Chào buổi chiều', 'phonetic' => '/ɡʊd ˌæftərˈnuːn/', 'example' => 'Good afternoon, everyone.'],
                ['word' => 'Good evening', 'meaning' => 'Chào buổi tối', 'phonetic' => '/ɡʊd ˈiːvnɪŋ/', 'example' => 'Good evening, sir.'],
                ['word' => 'Goodbye', 'meaning' => 'Tạm biệt', 'phonetic' => '/ɡʊdˈbaɪ/', 'example' => 'Goodbye, see you tomorrow!'],
                ['word' => 'Nice to meet you', 'meaning' => 'Rất vui được gặp bạn', 'phonetic' => '/naɪs tuː miːt juː/', 'example' => 'Nice to meet you, I am Linh.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson1_1->id,
            'title' => 'Ngữ pháp: Đại từ nhân xưng & Động từ TO BE',
            'type' => 'grammar',
            'order' => 2,
            'content' => [
                'title' => 'Đại từ nhân xưng (Personal Pronouns) & Động từ TO BE',
                'explanation' => 'Đại từ nhân xưng dùng để thay thế cho danh từ chỉ người hoặc vật. Động từ TO BE (am/is/are) được chia theo chủ ngữ.',
                'rules' => [
                    'I + am (I\'m) → I am a student.',
                    'You/We/They + are → You are my friend.',
                    'He/She/It + is → She is a teacher.',
                ],
                'examples' => [
                    'I am Tuấn Linh. I am from Vietnam.',
                    'She is my teacher. She is very kind.',
                    'They are students. They are in class 10A.',
                ],
            ],
        ]);

        // Course 1 - Lesson 2
        $lesson1_2 = Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Bài 2: Gia đình & Nghề nghiệp',
            'order' => 2,
            'unlock_condition_score' => 60,
            'is_free_trial' => true,
        ]);

        Activity::create([
            'lesson_id' => $lesson1_2->id,
            'title' => 'Từ vựng: Thành viên gia đình',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'Father', 'meaning' => 'Bố', 'phonetic' => '/ˈfɑːðər/', 'example' => 'My father is a doctor.'],
                ['word' => 'Mother', 'meaning' => 'Mẹ', 'phonetic' => '/ˈmʌðər/', 'example' => 'My mother is a teacher.'],
                ['word' => 'Brother', 'meaning' => 'Anh/Em trai', 'phonetic' => '/ˈbrʌðər/', 'example' => 'My brother is 10 years old.'],
                ['word' => 'Sister', 'meaning' => 'Chị/Em gái', 'phonetic' => '/ˈsɪstər/', 'example' => 'My sister studies at university.'],
                ['word' => 'Grandfather', 'meaning' => 'Ông', 'phonetic' => '/ˈɡrænfɑːðər/', 'example' => 'My grandfather is 80 years old.'],
                ['word' => 'Grandmother', 'meaning' => 'Bà', 'phonetic' => '/ˈɡrænmʌðər/', 'example' => 'My grandmother makes delicious cakes.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson1_2->id,
            'title' => 'Ngữ pháp: Sở hữu cách & Tính từ sở hữu',
            'type' => 'grammar',
            'order' => 2,
            'content' => [
                'title' => 'Tính từ sở hữu (Possessive Adjectives)',
                'explanation' => 'Tính từ sở hữu đứng trước danh từ để chỉ sự sở hữu.',
                'rules' => [
                    'I → my: My name is Linh.',
                    'You → your: What is your name?',
                    'He → his: His father is a doctor.',
                    'She → her: Her mother is kind.',
                    'We → our: Our school is big.',
                    'They → their: Their house is beautiful.',
                ],
                'examples' => [
                    'This is my family. My family has 4 people.',
                    'His name is Minh. His job is engineer.',
                ],
            ],
        ]);

        // Course 1 - Lesson 3
        $lesson1_3 = Lesson::create([
            'course_id' => $course1->id,
            'title' => 'Bài 3: Số đếm & Thời gian',
            'order' => 3,
            'unlock_condition_score' => 60,
            'is_free_trial' => false,
        ]);

        Activity::create([
            'lesson_id' => $lesson1_3->id,
            'title' => 'Từ vựng: Số đếm 1-100',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'One', 'meaning' => 'Một', 'phonetic' => '/wʌn/', 'example' => 'I have one cat.'],
                ['word' => 'Ten', 'meaning' => 'Mười', 'phonetic' => '/ten/', 'example' => 'There are ten students.'],
                ['word' => 'Twenty', 'meaning' => 'Hai mươi', 'phonetic' => '/ˈtwenti/', 'example' => 'She is twenty years old.'],
                ['word' => 'Hundred', 'meaning' => 'Một trăm', 'phonetic' => '/ˈhʌndrəd/', 'example' => 'I have one hundred coins.'],
                ['word' => 'First', 'meaning' => 'Thứ nhất', 'phonetic' => '/fɜːrst/', 'example' => 'This is my first lesson.'],
                ['word' => 'Second', 'meaning' => 'Thứ hai', 'phonetic' => '/ˈsekənd/', 'example' => 'The second floor is the library.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson1_3->id,
            'title' => 'Video: Cách đọc giờ trong tiếng Anh',
            'type' => 'video',
            'order' => 2,
            'content' => [
                'video_url' => 'https://www.youtube.com/embed/fq2tRfHMFbI',
                'title' => 'Cách đọc giờ trong tiếng Anh',
                'description' => 'Hướng dẫn chi tiết cách đọc giờ, phút, giây trong tiếng Anh với các mẫu câu thông dụng.',
                'duration' => '10:25',
            ],
        ]);

        // ─── COURSE 2: ENGLISH ELEMENTARY A2 ───
        $course2 = Course::create([
            'title' => 'English Elementary - Tiếng Anh Sơ Cấp A2',
            'slug' => 'english-elementary-a2',
            'description' => 'Khóa học nâng cao vốn từ vựng và ngữ pháp. Học cách miêu tả sở thích, công việc hàng ngày, và kế hoạch tương lai.',
            'thumbnail' => '/images/course-a2.png',
            'level' => 'A2',
            'order' => 2,
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        // Course 2 - Lesson 1
        $lesson2_1 = Lesson::create([
            'course_id' => $course2->id,
            'title' => 'Bài 1: Sở thích & Hoạt động giải trí',
            'order' => 1,
            'unlock_condition_score' => 0,
            'is_free_trial' => true,
        ]);

        Activity::create([
            'lesson_id' => $lesson2_1->id,
            'title' => 'Từ vựng: Sở thích phổ biến',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'Reading', 'meaning' => 'Đọc sách', 'phonetic' => '/ˈriːdɪŋ/', 'example' => 'I enjoy reading books.'],
                ['word' => 'Swimming', 'meaning' => 'Bơi lội', 'phonetic' => '/ˈswɪmɪŋ/', 'example' => 'Swimming is good for health.'],
                ['word' => 'Cooking', 'meaning' => 'Nấu ăn', 'phonetic' => '/ˈkʊkɪŋ/', 'example' => 'She likes cooking Vietnamese food.'],
                ['word' => 'Traveling', 'meaning' => 'Du lịch', 'phonetic' => '/ˈtrævəlɪŋ/', 'example' => 'We love traveling to new places.'],
                ['word' => 'Playing guitar', 'meaning' => 'Chơi đàn guitar', 'phonetic' => '/ˈpleɪɪŋ ɡɪˈtɑːr/', 'example' => 'He plays guitar every evening.'],
                ['word' => 'Photography', 'meaning' => 'Nhiếp ảnh', 'phonetic' => '/fəˈtɑːɡrəfi/', 'example' => 'Photography is her passion.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson2_1->id,
            'title' => 'Ngữ pháp: Thì hiện tại đơn (Present Simple)',
            'type' => 'grammar',
            'order' => 2,
            'content' => [
                'title' => 'Thì Hiện tại đơn (Present Simple Tense)',
                'explanation' => 'Dùng để diễn tả thói quen, sự thật hiển nhiên, hoặc lịch trình cố định.',
                'rules' => [
                    'Khẳng định: S + V(s/es) + O → She plays tennis every day.',
                    'Phủ định: S + do/does + not + V → He does not like coffee.',
                    'Nghi vấn: Do/Does + S + V? → Do you speak English?',
                ],
                'examples' => [
                    'I read books every night before bed.',
                    'She doesn\'t watch TV on weekdays.',
                    'Does he play football on Sundays?',
                ],
            ],
        ]);

        // Course 2 - Lesson 2
        $lesson2_2 = Lesson::create([
            'course_id' => $course2->id,
            'title' => 'Bài 2: Thời tiết & Mùa trong năm',
            'order' => 2,
            'unlock_condition_score' => 60,
            'is_free_trial' => false,
        ]);

        Activity::create([
            'lesson_id' => $lesson2_2->id,
            'title' => 'Từ vựng: Thời tiết & Mùa',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'Sunny', 'meaning' => 'Nắng', 'phonetic' => '/ˈsʌni/', 'example' => 'It is sunny today.'],
                ['word' => 'Rainy', 'meaning' => 'Mưa', 'phonetic' => '/ˈreɪni/', 'example' => 'It is rainy in summer.'],
                ['word' => 'Cloudy', 'meaning' => 'Nhiều mây', 'phonetic' => '/ˈklaʊdi/', 'example' => 'The sky is cloudy this morning.'],
                ['word' => 'Winter', 'meaning' => 'Mùa đông', 'phonetic' => '/ˈwɪntər/', 'example' => 'Winter is very cold in Hanoi.'],
                ['word' => 'Spring', 'meaning' => 'Mùa xuân', 'phonetic' => '/sprɪŋ/', 'example' => 'Spring is my favorite season.'],
                ['word' => 'Temperature', 'meaning' => 'Nhiệt độ', 'phonetic' => '/ˈtemprətʃər/', 'example' => 'The temperature is 30 degrees today.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson2_2->id,
            'title' => 'Quiz: Từ vựng thời tiết',
            'type' => 'quiz',
            'order' => 2,
            'content' => [
                'questions' => [
                    ['question' => '"Sunny" nghĩa là gì?', 'options' => ['Mưa', 'Nắng', 'Gió', 'Lạnh'], 'answer' => 1],
                    ['question' => 'Từ nào mô tả trời có nhiều mây?', 'options' => ['Rainy', 'Cloudy', 'Sunny', 'Windy'], 'answer' => 1],
                    ['question' => '"Winter" là mùa gì?', 'options' => ['Mùa xuân', 'Mùa hạ', 'Mùa thu', 'Mùa đông'], 'answer' => 3],
                ],
            ],
        ]);

        // Course 2 - Lesson 3
        $lesson2_3 = Lesson::create([
            'course_id' => $course2->id,
            'title' => 'Bài 3: Đi mua sắm & Hỏi giá',
            'order' => 3,
            'unlock_condition_score' => 65,
            'is_free_trial' => false,
        ]);

        Activity::create([
            'lesson_id' => $lesson2_3->id,
            'title' => 'Từ vựng: Mua sắm & Giá cả',
            'type' => 'vocabulary',
            'order' => 1,
            'content' => [
                ['word' => 'How much', 'meaning' => 'Bao nhiêu tiền', 'phonetic' => '/haʊ mʌtʃ/', 'example' => 'How much is this shirt?'],
                ['word' => 'Expensive', 'meaning' => 'Đắt', 'phonetic' => '/ɪkˈspensɪv/', 'example' => 'This bag is very expensive.'],
                ['word' => 'Cheap', 'meaning' => 'Rẻ', 'phonetic' => '/tʃiːp/', 'example' => 'The market has many cheap items.'],
                ['word' => 'Discount', 'meaning' => 'Giảm giá', 'phonetic' => '/ˈdɪskaʊnt/', 'example' => 'Is there any discount?'],
                ['word' => 'Receipt', 'meaning' => 'Hóa đơn', 'phonetic' => '/rɪˈsiːt/', 'example' => 'Can I have the receipt, please?'],
                ['word' => 'Cash', 'meaning' => 'Tiền mặt', 'phonetic' => '/kæʃ/', 'example' => 'I will pay by cash.'],
            ],
        ]);

        Activity::create([
            'lesson_id' => $lesson2_3->id,
            'title' => 'Ngữ pháp: Câu hỏi How much / How many',
            'type' => 'grammar',
            'order' => 2,
            'content' => [
                'title' => 'How much vs How many',
                'explanation' => 'How much dùng cho danh từ không đếm được, How many dùng cho danh từ đếm được.',
                'rules' => [
                    'How much + uncountable noun → How much water do you need?',
                    'How many + countable noun → How many students are there?',
                    'How much + is/are ... → How much is this book?',
                ],
                'examples' => [
                    'How much money do you have?',
                    'How many brothers and sisters do you have?',
                    'How much does this cost?',
                ],
            ],
        ]);

        // ─── 30 REAL MULTIPLE CHOICE QUESTIONS (10 A1, 10 A2, 10 B1) ───
        $questions = [
            // ── LEVEL A1 (10 câu) ──
            [
                'skill' => 'vocabulary', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Từ nào sau đây mang ý nghĩa là "Chào buổi sáng"?',
                'options' => ['Good evening', 'Good morning', 'Good afternoon', 'Good night'],
                'correct_answer' => 'Good morning',
                'explanation' => '"Good morning" là lời chào buổi sáng (từ lúc thức dậy đến trước 12h trưa).'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Thành viên nào trong gia đình được gọi là "Brother"?',
                'options' => ['Bố', 'Mẹ', 'Anh hoặc em trai', 'Chị hoặc em gái'],
                'correct_answer' => 'Anh hoặc em trai',
                'explanation' => 'Brother = Anh/Em trai. Sister = Chị/Em gái. Father = Bố. Mother = Mẹ.'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Số "15" được viết bằng tiếng Anh là gì?',
                'options' => ['Fifty', 'Fifteen', 'Five', 'Fiveteen'],
                'correct_answer' => 'Fifteen',
                'explanation' => '15 = Fifteen. 50 = Fifty. 5 = Five.'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Chọn dạng đúng của động từ to be: "I ___ a student at ESL."',
                'options' => ['is', 'are', 'am', 'be'],
                'correct_answer' => 'am',
                'explanation' => 'Chủ ngữ "I" luôn đi với động từ to be "am" ở thì hiện tại đơn.'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Điền vào chỗ trống: "This is My. ___ is my classmate."',
                'options' => ['He', 'She', 'They', 'It'],
                'correct_answer' => 'She',
                'explanation' => 'My là tên con gái (nữ), đại từ nhân xưng thay thế là "She".'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Chọn tính từ sở hữu thích hợp: "We love ___ school."',
                'options' => ['our', 'their', 'his', 'her'],
                'correct_answer' => 'our',
                'explanation' => 'Chủ ngữ "We" (chúng tôi) tương ứng với tính từ sở hữu "our" (của chúng tôi).'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Đọc câu sau: "Tom has a blue backpack and two pens." Tom có cái gì màu xanh nước biển?',
                'options' => ['Hai chiếc bút', 'Một chiếc ba lô', 'Một quyển sách', 'Một đôi giày'],
                'correct_answer' => 'Một chiếc ba lô',
                'explanation' => '"Blue backpack" có nghĩa là chiếc ba lô màu xanh nước biển.'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => 'Đọc đoạn ngắn: "Anna is 9 years old. She lives in Da Nang with her parents." Anna sống ở đâu?',
                'options' => ['Hà Nội', 'Đà Nẵng', 'Hồ Chí Minh', 'Nha Trang'],
                'correct_answer' => 'Đà Nẵng',
                'explanation' => 'Đoạn văn nêu rõ: "She lives in Da Nang".'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => '[Âm thanh] Người nói hỏi: "What time is it?" - Câu trả lời phù hợp nhất là:',
                'options' => ['I am fine, thank you.', 'It is 8 o\'clock.', 'My name is John.', 'Yes, I do.'],
                'correct_answer' => 'It is 8 o\'clock.',
                'explanation' => '"What time is it?" dùng để hỏi giờ, câu trả lời là "It is 8 o\'clock" (Bây giờ là 8 giờ).'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => '[Giao tiếp] Khi ai đó nói "Thank you very much!", bạn sẽ đáp lại như thế nào?',
                'options' => ['Goodbye!', 'You\'re welcome!', 'See you later.', 'I am sorry.'],
                'correct_answer' => 'You\'re welcome!',
                'explanation' => '"You\'re welcome!" (Không có gì đâu!) là câu đáp lại phổ biến và lịch sự nhất khi được cảm ơn.'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => '[Âm thanh] Người nói hỏi: "Where are you from?" - Câu trả lời chính xác là:',
                'options' => ['I am from Vietnam.', 'I am 20 years old.', 'I like coffee.', 'I am a student.'],
                'correct_answer' => 'I am from Vietnam.',
                'explanation' => '"Where are you from?" hỏi về quê quán / xuất xứ, câu trả lời là "I am from Vietnam".'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A1', 'question_type' => 'mcq',
                'question_text' => '[Âm thanh] Nghe đoạn hội thoại: "How do you spell your name?" - "It\'s J-O-H-N." Tên người nói là gì?',
                'options' => ['John', 'Jane', 'Jack', 'James'],
                'correct_answer' => 'John',
                'explanation' => 'Đoạn đánh vần rõ ràng J-O-H-N là John.'
            ],

            // ── LEVEL A2 (10 câu) ──
            [
                'skill' => 'vocabulary', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Từ nào đồng nghĩa với từ "Delicious" (ngon miệng)?',
                'options' => ['Tasty', 'Terrible', 'Expensive', 'Boring'],
                'correct_answer' => 'Tasty',
                'explanation' => 'Delicious và Tasty đều có nghĩa là thơm ngon. Terrible = tồi tệ, Boring = nhàm chán.'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Người làm công việc chăm sóc răng miệng được gọi là:',
                'options' => ['Architect', 'Dentist', 'Engineer', 'Journalist'],
                'correct_answer' => 'Dentist',
                'explanation' => 'Dentist = Nha sĩ. Architect = Kiến trúc sư. Engineer = Kỹ sư.'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Trái nghĩa với từ "Crowded" (đông đúc) là từ nào?',
                'options' => ['Busy', 'Noisy', 'Quiet', 'Empty'],
                'correct_answer' => 'Empty',
                'explanation' => 'Crowded = đông đúc, chật kín. Empty = vắng vẻ, trống trải.'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Chọn đáp án đúng ở thì Quá khứ đơn: "Yesterday, we ___ to the national museum."',
                'options' => ['go', 'went', 'gone', 'going'],
                'correct_answer' => 'went',
                'explanation' => 'Dấu hiệu "Yesterday" chỉ quá khứ đơn, quá khứ của "go" là "went".'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Chọn liên từ thích hợp: "She was tired, ___ she finished her homework before sleeping."',
                'options' => ['because', 'but', 'so', 'although'],
                'correct_answer' => 'but',
                'explanation' => '"But" (nhưng) thể hiện sự tương phản: Dù mệt nhưng cô ấy vẫn hoàn thành bài tập.'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Chọn dạng so sánh hơn: "Travelling by train is ___ than travelling by bus."',
                'options' => ['more comfortable', 'comfortabler', 'most comfortable', 'as comfortable'],
                'correct_answer' => 'more comfortable',
                'explanation' => 'Comfortable là tính từ dài (3 âm tiết), so sánh hơn dùng "more + adj + than".'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Đọc thông báo: "Library will be closed this Friday for maintenance." Bạn không thể làm gì vào thứ Sáu?',
                'options' => ['Mượn sách tại thư viện', 'Đi học thể dục', 'Ăn trưa tại căng tin', 'Đi xe buýt'],
                'correct_answer' => 'Mượn sách tại thư viện',
                'explanation' => 'Thư viện (Library) đóng cửa để bảo trì nên học sinh không thể đến mượn sách.'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => 'Đọc đoạn văn: "Peter gets up at 6 AM, exercises for 30 minutes, then takes a shower." Peter làm gì sau khi tập thể dục?',
                'options' => ['Ăn sáng', 'Thức dậy', 'Đi tắm', 'Đi học'],
                'correct_answer' => 'Đi tắm',
                'explanation' => '"then takes a shower" = sau đó anh ấy đi tắm.'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => '[Chỉ đường] Khi được hướng dẫn "Take the second turning on your left", bạn cần rẽ ở đâu?',
                'options' => ['Rẽ trái ở ngã rẽ thứ nhất', 'Rẽ trái ở ngã rẽ thứ hai', 'Rẽ phải ở ngã rẽ thứ hai', 'Đi thẳng qua hai ngã tư'],
                'correct_answer' => 'Rẽ trái ở ngã rẽ thứ hai',
                'explanation' => 'Second turning on your left = ngã rẽ thứ hai bên tay trái.'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'mcq',
                'question_text' => '[Đặt phòng] "I would like to book a double room for two nights." Khách muốn đặt phòng cho mấy đêm?',
                'options' => ['1 đêm', '2 đêm', '3 đêm', '1 tuần'],
                'correct_answer' => '2 đêm',
                'explanation' => '"for two nights" nghĩa là đặt phòng trong 2 đêm.'
            ],

            // ── LEVEL B1 (10 câu) ──
            [
                'skill' => 'vocabulary', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Chọn từ thích hợp: "Regular exercise is extremely ___ for both mental and physical health."',
                'options' => ['harmful', 'beneficial', 'useless', 'dangerous'],
                'correct_answer' => 'beneficial',
                'explanation' => 'Beneficial = có lợi, hữu ích. Harmful = có hại. Useless = vô dụng.'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Cụm động từ "Look forward to" có nghĩa là gì?',
                'options' => ['Chăm sóc ai đó', 'Tìm kiếm thông tin', 'Háo hức mong đợi điều gì', 'Xem thường ai đó'],
                'correct_answer' => 'Háo hức mong đợi điều gì',
                'explanation' => 'Look forward to + V-ing / Noun mang nghĩa là mong chờ, háo hức chờ đợi.'
            ],
            [
                'skill' => 'vocabulary', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Thuật ngữ "Renewable energy" chỉ loại năng lượng nào?',
                'options' => ['Năng lượng hóa thạch', 'Năng lượng tái tạo', 'Năng lượng hạt nhân', 'Khí đốt tự nhiên'],
                'correct_answer' => 'Năng lượng tái tạo',
                'explanation' => 'Renewable energy = Năng lượng tái tạo (mặt trời, gió, thủy triều).'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Chọn thì Hiện tại hoàn thành phù hợp: "She ___ in this company since 2018."',
                'options' => ['has worked', 'worked', 'is working', 'works'],
                'correct_answer' => 'has worked',
                'explanation' => 'Dấu hiệu "since + mốc thời gian" dùng thì Hiện tại hoàn thành (have/has + V3/ed).'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Câu điều kiện loại 2: "If I ___ a million dollars, I would travel around the world."',
                'options' => ['have', 'had', 'will have', 'would have'],
                'correct_answer' => 'had',
                'explanation' => 'Cấu trúc câu điều kiện loại 2: If + S + V-ed/V2 (were), S + would + V-inf.'
            ],
            [
                'skill' => 'grammar', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Mệnh đề quan hệ: "The man ___ wrote this book won an international award."',
                'options' => ['which', 'who', 'whom', 'whose'],
                'correct_answer' => 'who',
                'explanation' => '"The man" là danh từ chỉ người đóng vai trò chủ ngữ trong mệnh đề, dùng đại từ quan hệ "who".'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Đọc câu: "Despite the heavy rain, the soccer match continued without interruption." Ý chính của câu là gì?',
                'options' => ['Trận đấu bị hoãn do mưa lớn', 'Trận đấu vẫn tiếp tục dù trời mưa to', 'Khán giả bỏ về vì mưa', 'Trận đấu bị hủy bỏ'],
                'correct_answer' => 'Trận đấu vẫn tiếp tục dù trời mưa to',
                'explanation' => '"Despite the heavy rain... continued without interruption" = Mặc cho mưa lớn, trận đấu vẫn diễn ra liên tục.'
            ],
            [
                'skill' => 'reading', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => 'Đọc đoạn: "Online learning offers great flexibility, allowing students to study at their own pace anywhere." Lợi ích chính của học trực tuyến ở đây là gì?',
                'options' => ['Miễn phí hoàn toàn học phí', 'Sự linh hoạt và tự chủ về thời gian, địa điểm', 'Được gặp trực tiếp giảng viên', 'Không cần làm bài tập về nhà'],
                'correct_answer' => 'Sự linh hoạt và tự chủ về thời gian, địa điểm',
                'explanation' => '"Flexibility, allowing students to study at their own pace anywhere" = Tính linh hoạt, học theo tốc độ bản thân ở bất kỳ đâu.'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => '[Thông báo sân bay] "Flight VN123 to Da Nang is delayed by 45 minutes due to adverse weather conditions." Chuyến bay bị hoãn vì lý do gì?',
                'options' => ['Sự cố kỹ thuật máy bay', 'Điều kiện thời tiết xấu', 'Thiếu phi hành đoàn', 'Quá tải đường băng'],
                'correct_answer' => 'Điều kiện thời tiết xấu',
                'explanation' => '"Adverse weather conditions" nghĩa là điều kiện thời tiết bất lợi / xấu.'
            ],
            [
                'skill' => 'listening', 'difficulty' => 'B1', 'question_type' => 'mcq',
                'question_text' => '[Phỏng vấn] "Could you tell me about a challenge you faced and how you overcame it?" Người phỏng vấn muốn biết về điều gì?',
                'options' => ['Mức lương mong muốn của bạn', 'Một thử thách và cách bạn vượt qua nó', 'Bằng cấp học tập của bạn', 'Gia đình và người thân của bạn'],
                'correct_answer' => 'Một thử thách và cách bạn vượt qua nó',
                'explanation' => '"A challenge you faced and how you overcame it" = thử thách bạn gặp phải và cách bạn đã vượt qua nó.'
            ],

            // ─── MODERN DIGITAL QUESTION TYPES ───
            // Multiple Select
            [
                'skill' => 'vocabulary', 'difficulty' => 'A2', 'question_type' => 'multiple_select',
                'question_text' => 'Chọn 2 từ đồng nghĩa hoặc liên quan trực tiếp đến "Healthy" (Lành mạnh / Khỏe khoắn):',
                'options' => ['Fit', 'Strong', 'Tired', 'Sick'],
                'correct_answer' => json_encode(['Fit', 'Strong']),
                'explanation' => '"Fit" (cân đối, khỏe mạnh) và "Strong" (mạnh mẽ) là 2 từ liên quan trực tiếp đến sức khỏe tốt.'
            ],
            // Fill in the Blank
            [
                'skill' => 'grammar', 'difficulty' => 'A2', 'question_type' => 'fill_blank',
                'question_text' => 'Điền dạng Quá khứ đơn (Past Simple) của động từ "buy": "Yesterday, I ___ a new English dictionary."',
                'options' => [],
                'correct_answer' => 'bought',
                'explanation' => 'Động từ bất quy tắc của "buy" ở quá khứ là "bought".'
            ],
            // Word Ordering / Sentence Reordering
            [
                'skill' => 'grammar', 'difficulty' => 'A1', 'question_type' => 'word_ordering',
                'question_text' => 'Bấm chọn các từ bên dưới để sắp xếp thành câu hoàn chỉnh đúng ngữ pháp:',
                'options' => ['English', 'every', 'I', 'study', 'day'],
                'correct_answer' => 'I study English every day',
                'explanation' => 'Cấu trúc câu khẳng định hiện tại đơn: Chủ ngữ (I) + Động từ (study) + Tân ngữ (English) + Trạng từ chỉ thời gian (every day).'
            ],
            // Matching Pairs
            [
                'skill' => 'vocabulary', 'difficulty' => 'B1', 'question_type' => 'matching',
                'question_text' => 'Nối các thuật ngữ môi trường sau với định nghĩa tiếng Việt tương ứng:',
                'options' => [
                    'left' => ['Ecosystem' => 'Ecosystem', 'Biodiversity' => 'Biodiversity', 'Conservation' => 'Conservation'],
                    'right' => ['Hệ sinh thái', 'Đa dạng sinh học', 'Bảo tồn thiên nhiên']
                ],
                'correct_answer' => json_encode(['Ecosystem' => 'Hệ sinh thái', 'Biodiversity' => 'Đa dạng sinh học', 'Conservation' => 'Bảo tồn thiên nhiên']),
                'explanation' => 'Ecosystem = Hệ sinh thái, Biodiversity = Đa dạng sinh học, Conservation = Sự bảo tồn.'
            ],
            // True / False / Not Given
            [
                'skill' => 'reading', 'difficulty' => 'B1', 'question_type' => 'true_false',
                'question_text' => 'Đọc câu sau: "Solar panels convert sunlight directly into clean electricity without producing greenhouse gases." Nhận định: "Năng lượng mặt trời không tạo ra khí nhà kính."',
                'options' => ['True', 'False', 'Not Given'],
                'correct_answer' => 'True',
                'explanation' => 'Đoạn văn nêu rõ "without producing greenhouse gases" nghĩa là không sinh ra khí thải nhà kính, do đó nhận định là True (Đúng).'
            ],
            // Audio Listening Drill
            [
                'skill' => 'listening', 'difficulty' => 'A2', 'question_type' => 'audio_listening',
                'question_text' => 'Nghe câu thông báo: "The next flight to Tokyo departs from Gate 14." Chuyến bay đi Tokyo khởi hành tại cổng nào?',
                'options' => ['Gate 4', 'Gate 14', 'Gate 40', 'Gate 24'],
                'correct_answer' => 'Gate 14',
                'explanation' => 'Trong thông báo nêu rõ "Gate 14" (Cổng số 14).'
            ],
            // Pronunciation Speech Drill
            [
                'skill' => 'speaking', 'difficulty' => 'A1', 'question_type' => 'pronunciation_speech',
                'question_text' => "Luyện phát âm chuẩn câu giao tiếp sau bằng micro:\n\n\"Good morning, nice to meet you\"",
                'options' => [],
                'correct_answer' => 'Good morning, nice to meet you',
                'explanation' => 'Phát âm chuẩn /ɡʊd ˈmɔːrnɪŋ naɪs tuː miːt juː/.'
            ],
        ];

        foreach ($questions as $q) {
            QuestionBank::create([
                'skill' => $q['skill'],
                'difficulty' => $q['difficulty'],
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'explanation' => $q['explanation'],
            ]);
        }

        // ─── EXAM SETS (FULL MOCK & SKILL EXAMS) ───
        $examSets = [
            // Full 4-Skill Mock Tests
            [
                'key' => 'mock_test_a1',
                'title' => 'Đề Thi Thử Toàn Diện 4 Kỹ Năng A1 - Sơ Cấp (Full Simulation)',
                'difficulty' => 'A1',
                'skill' => 'full_mock',
                'question_count' => 10,
                'duration_minutes' => 15,
                'reward_coins' => 35,
                'description' => 'Mô phỏng bài thi thực tế chuẩn CEFR A1 gồm đầy đủ 4 phần: Từ vựng (3 câu), Ngữ pháp (3 câu), Đọc hiểu (2 câu), Nghe hiểu (2 câu).',
                'sections' => ['vocabulary' => 3, 'grammar' => 3, 'reading' => 2, 'listening' => 2],
                'is_published' => true,
                'created_by' => $admin->id,
            ],
            [
                'key' => 'mock_test_a2',
                'title' => 'Đề Thi Thử Toàn Diện 4 Kỹ Năng A2 - Tiền Trung Cấp (Full Simulation)',
                'difficulty' => 'A2',
                'skill' => 'full_mock',
                'question_count' => 10,
                'duration_minutes' => 20,
                'reward_coins' => 45,
                'description' => 'Mô phỏng bài thi thực tế chuẩn CEFR A2 gồm đầy đủ 4 phần: Từ vựng (3 câu), Ngữ pháp (3 câu), Đọc hiểu (2 câu), Nghe hiểu (2 câu).',
                'sections' => ['vocabulary' => 3, 'grammar' => 3, 'reading' => 2, 'listening' => 2],
                'is_published' => true,
                'created_by' => $admin->id,
            ],
            [
                'key' => 'mock_test_b1',
                'title' => 'Đề Thi Thử Toàn Diện 4 Kỹ Năng B1 - Trung Cấp (Full Simulation)',
                'difficulty' => 'B1',
                'skill' => 'full_mock',
                'question_count' => 10,
                'duration_minutes' => 25,
                'reward_coins' => 60,
                'description' => 'Mô phỏng bài thi thực tế chuẩn CEFR B1 gồm đầy đủ 4 phần: Từ vựng (3 câu), Ngữ pháp (3 câu), Đọc hiểu (2 câu), Nghe hiểu (2 câu).',
                'sections' => ['vocabulary' => 3, 'grammar' => 3, 'reading' => 2, 'listening' => 2],
                'is_published' => true,
                'created_by' => $admin->id,
            ],
            [
                'key' => 'mock_test_national',
                'title' => 'Đề Khảo Thí Quốc Tế ESL Full 4 Kỹ Năng (Standard CEFR Placement)',
                'difficulty' => 'Mixed',
                'skill' => 'full_mock',
                'question_count' => 16,
                'duration_minutes' => 30,
                'reward_coins' => 80,
                'description' => 'Đề thi phân định năng lực chuẩn hóa quốc tế với 16 câu hỏi phân bố đều cho cả 4 kỹ năng (4 câu x 4 kỹ năng).',
                'sections' => ['vocabulary' => 4, 'grammar' => 4, 'reading' => 4, 'listening' => 4],
                'is_published' => true,
                'created_by' => $admin->id,
            ],

            // Vocabulary Exams
            [
                'key' => 'vocab_test_01',
                'title' => 'Đề 01: Từ vựng Cơ bản & Giao tiếp Hàng ngày',
                'difficulty' => 'A1',
                'skill' => 'vocabulary',
                'question_count' => 3,
                'duration_minutes' => 5,
                'reward_coins' => 10,
                'description' => 'Kiểm tra nhận biết lời chào, thành viên gia đình, số đếm và đồ vật cơ bản.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'vocab_test_02',
                'title' => 'Đề 02: Từ vựng Sơ cấp - Nghề nghiệp, Ẩm thực & Địa điểm',
                'difficulty' => 'A2',
                'skill' => 'vocabulary',
                'question_count' => 3,
                'duration_minutes' => 8,
                'reward_coins' => 15,
                'description' => 'Phản xạ từ vựng mô tả đặc điểm, từ đồng nghĩa và nghề nghiệp trong cuộc sống.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'vocab_test_03',
                'title' => 'Đề 03: Từ vựng Trung cấp - Môi trường & Năng lượng',
                'difficulty' => 'B1',
                'skill' => 'vocabulary',
                'question_count' => 3,
                'duration_minutes' => 10,
                'reward_coins' => 20,
                'description' => 'Kiểm tra thuật ngữ học thuật, collocations nâng cao và phrasal verbs.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'vocab_test_full',
                'title' => 'Đề Tổng Hợp: Mini-Test 10 Câu Từ Vựng Toàn Diện',
                'difficulty' => 'Mixed',
                'skill' => 'vocabulary',
                'question_count' => 10,
                'duration_minutes' => 15,
                'reward_coins' => 30,
                'description' => 'Đề thi tổng hợp đầy đủ từ vựng A1 - B1 để đánh giá mức độ thuần thục toàn diện.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],

            // Grammar Exams
            [
                'key' => 'grammar_test_01',
                'title' => 'Đề 01: Động từ TO BE, Đại từ & Tính từ sở hữu',
                'difficulty' => 'A1',
                'skill' => 'grammar',
                'question_count' => 3,
                'duration_minutes' => 5,
                'reward_coins' => 10,
                'description' => 'Rèn luyện chia động từ to be ở hiện tại đơn và đại từ nhân xưng chuẩn xác.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'grammar_test_02',
                'title' => 'Đề 02: Thì Quá khứ đơn, Liên từ & So sánh hơn',
                'difficulty' => 'A2',
                'skill' => 'grammar',
                'question_count' => 3,
                'duration_minutes' => 8,
                'reward_coins' => 15,
                'description' => 'Luyện phân biệt thì quá khứ đơn, tính từ so sánh hơn và liên từ tương phản.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'grammar_test_03',
                'title' => 'Đề 03: Câu điều kiện Loại 2, Mệnh đề quan hệ & Hiện tại hoàn thành',
                'difficulty' => 'B1',
                'skill' => 'grammar',
                'question_count' => 3,
                'duration_minutes' => 10,
                'reward_coins' => 20,
                'description' => 'Kiểm tra cấu trúc câu điều kiện loại 2, đại từ quan hệ Who/Which/Whom và thì HTHT.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'grammar_test_full',
                'title' => 'Đề Tổng Hợp: Mini-Test 10 Câu Ngữ Pháp Toàn Diện',
                'difficulty' => 'Mixed',
                'skill' => 'grammar',
                'question_count' => 10,
                'duration_minutes' => 15,
                'reward_coins' => 30,
                'description' => 'Bộ đề thi ngữ pháp toàn diện từ cơ bản đến nâng cao theo chuẩn CEFR.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],

            // Reading Exams
            [
                'key' => 'reading_test_01',
                'title' => 'Đề 01: Đọc hiểu Đoạn văn Ngắn & Nhận diện Chi tiết',
                'difficulty' => 'A1',
                'skill' => 'reading',
                'question_count' => 2,
                'duration_minutes' => 6,
                'reward_coins' => 10,
                'description' => 'Đọc đoạn văn ngắn về nhân vật, đồ vật và tìm thông tin cụ thể.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'reading_test_02',
                'title' => 'Đề 02: Phân tích Bảng thông báo & Thói quen hàng ngày',
                'difficulty' => 'A2',
                'skill' => 'reading',
                'question_count' => 2,
                'duration_minutes' => 8,
                'reward_coins' => 15,
                'description' => 'Hiểu nội dung thông báo công cộng, bảo trì thư viện và thời gian biểu sinh hoạt.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'reading_test_03',
                'title' => 'Đề 03: Đọc hiểu Học thuật & Lợi ích Học trực tuyến',
                'difficulty' => 'B1',
                'skill' => 'reading',
                'question_count' => 2,
                'duration_minutes' => 10,
                'reward_coins' => 20,
                'description' => 'Phân tích văn bản tin tức, ý chính bài luận và suy luận logic.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'reading_test_full',
                'title' => 'Đề Tổng Hợp: Mini-Test Đọc hiểu Chuyên sâu',
                'difficulty' => 'Mixed',
                'skill' => 'reading',
                'question_count' => 6,
                'duration_minutes' => 15,
                'reward_coins' => 25,
                'description' => 'Kiểm tra kỹ năng Skimming & Scanning qua nhiều thể loại văn bản đa dạng.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],

            // Listening Exams
            [
                'key' => 'listening_test_01',
                'title' => 'Đề 01: Giao tiếp Cơ bản - Lời cảm ơn & Hỏi giờ',
                'difficulty' => 'A1',
                'skill' => 'listening',
                'question_count' => 2,
                'duration_minutes' => 5,
                'reward_coins' => 10,
                'description' => 'Phản xạ với câu hỏi thời gian và các lời chào giao tiếp lịch sự.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'listening_test_02',
                'title' => 'Đề 02: Tình huống Thực tế - Chỉ đường & Đặt phòng',
                'difficulty' => 'A2',
                'skill' => 'listening',
                'question_count' => 2,
                'duration_minutes' => 8,
                'reward_coins' => 15,
                'description' => 'Nghe hiểu hướng dẫn rẽ đường và thông tin số đêm khi đặt phòng khách sạn.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'listening_test_03',
                'title' => 'Đề 03: Tình huống Nâng cao - Thông báo Sân bay & Phỏng vấn',
                'difficulty' => 'B1',
                'skill' => 'listening',
                'question_count' => 2,
                'duration_minutes' => 10,
                'reward_coins' => 20,
                'description' => 'Nghe thông báo hoãn chuyến bay sân bay và câu hỏi trong phỏng vấn tuyển dụng.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
            [
                'key' => 'listening_test_full',
                'title' => 'Đề Tổng Hợp: Mini-Test Nghe hiểu Tình huống Toàn diện',
                'difficulty' => 'Mixed',
                'skill' => 'listening',
                'question_count' => 6,
                'duration_minutes' => 15,
                'reward_coins' => 25,
                'description' => 'Bộ đề nghe hiểu tổng hợp đủ các tình huống đời sống và học thuật.',
                'is_published' => true,
                'created_by' => $teacher->id,
            ],
        ];

        foreach ($examSets as $es) {
            \App\Models\ExamSet::create($es);
        }
    }
}
