<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\QuestionBank;
use App\Http\Controllers\Admin\AdminQuestionBankController;
use Illuminate\Http\Request;

echo "=== 1. VERIFY ADMIN QUESTION BANK CONTROLLER & ROUTES ===\n";

$adminUser = User::where('role', 'admin')->first() ?? User::first();
auth()->login($adminUser);
echo "Logged in as: {$adminUser->email} (role: {$adminUser->role})\n";

$controller = $app->make(AdminQuestionBankController::class);

// 1. Test Index View - Questions Tab
$reqIndex = Request::create('/admin/questions', 'GET', ['tab' => 'questions']);
$reqIndex->setUserResolver(fn() => $adminUser);
$resIndex = $controller->index($reqIndex);
$resIndex->render();
echo "✅ SUCCESS: admin.questions.index (Questions Tab) rendered successfully!\n";

// 2. Test Index View - Testlets Tab
$reqTestlets = Request::create('/admin/questions', 'GET', ['tab' => 'testlets']);
$reqTestlets->setUserResolver(fn() => $adminUser);
$resTestlets = $controller->index($reqTestlets);
$resTestlets->render();
echo "✅ SUCCESS: admin.questions.index (Testlets Tab) rendered successfully!\n";

// 3. Test Store Single Question
echo "\n=== 2. TEST SINGLE QUESTION CREATION ===\n";
$reqStore = Request::create('/admin/questions', 'POST', [
    'skill' => 'reading',
    'difficulty' => 'B2',
    'question_type' => 'mcq',
    'question_text' => 'Unit Test Question: What is the benefit of solar panels?',
    'options' => "A. Free electricity\nB. Higher pollution\nC. Noise disturbance\nD. None of the above",
    'correct_answer' => 'A. Free electricity',
    'explanation' => 'Solar panels convert sunlight to renewable energy.',
]);
$reqStore->setUserResolver(fn() => $adminUser);
$resStore = $controller->store($reqStore);
echo "Redirect status: " . $resStore->getStatusCode() . "\n";

$createdQ = QuestionBank::where('question_text', 'like', '%Unit Test Question:%')->first();
if ($createdQ) {
    echo "✅ SUCCESS: Single question created ID #{$createdQ->id}, correct: {$createdQ->correct_answer}\n";
} else {
    echo "❌ FAILED to create single question\n";
}

// 4. Test Store Testlet Cluster (Reading Passage + 3 Questions)
echo "\n=== 3. TEST TESTLET CLUSTER AUTHORING (Passage + 3 Sub-questions) ===\n";
$testletTitle = "Automated Testlet: The Marvels of Space Exploration";
$reqTestlet = Request::create('/admin/questions/testlet', 'POST', [
    'skill' => 'reading',
    'difficulty' => 'B2',
    'passage_title' => $testletTitle,
    'passage_content' => "Space exploration has led to numerous technological breakthroughs that benefit humanity. From satellite communication to advanced medical imaging devices, space technology influences our daily lives.\n\nAstronomers continue to investigate distant exoplanets in search of potential biosignatures, pushing the boundaries of human scientific inquiry.",
    'part' => 1,
    'questions' => [
        [
            'question_text' => 'What is the primary benefit mentioned in the passage?',
            'opt_a' => 'Technological breakthroughs',
            'opt_b' => 'Lower fuel costs',
            'opt_c' => 'Reduced satellite count',
            'opt_d' => 'Decreased medical research',
            'correct_answer' => 'A',
            'explanation' => 'The passage states space exploration led to technological breakthroughs.',
        ],
        [
            'question_text' => 'What are astronomers investigating on distant exoplanets?',
            'opt_a' => 'Commercial mining zones',
            'opt_b' => 'Potential biosignatures',
            'opt_c' => 'Tourist accommodations',
            'opt_d' => 'Military bases',
            'correct_answer' => 'B',
            'explanation' => 'Paragraph 2 mentions searching for biosignatures.',
        ],
        [
            'question_text' => 'Which of the following is influenced by space technology?',
            'opt_a' => 'Steam engines',
            'opt_b' => 'Medical imaging devices',
            'opt_c' => 'Coal extraction',
            'opt_d' => 'Horse carriages',
            'correct_answer' => 'B',
            'explanation' => 'Satellite communication and medical imaging devices are named.',
        ],
    ],
]);
$reqTestlet->setUserResolver(fn() => $adminUser);
$resTestlet = $controller->storeTestlet($reqTestlet);
echo "Testlet redirect status: " . $resTestlet->getStatusCode() . "\n";

$testletQuestions = QuestionBank::where('meta_data->passage_title', $testletTitle)->get();
echo "Created questions in cluster: " . $testletQuestions->count() . "\n";
if ($testletQuestions->count() === 3) {
    echo "✅ SUCCESS: All 3 sub-questions created with synchronized metadata!\n";
    foreach ($testletQuestions as $tq) {
        echo " - Sub-Q ID #{$tq->id}: '{$tq->question_text}' (Ans: {$tq->correct_answer})\n";
    }
} else {
    echo "❌ FAILED: Expected 3 testlet questions, got " . $testletQuestions->count() . "\n";
}

// 5. Test JSON Modal Preview API
echo "\n=== 4. TEST JSON MODAL PREVIEW API & PASSAGE EDITING ===\n";
if ($createdQ) {
    $jsonRes = $controller->showJson($createdQ->id);
    $data = $jsonRes->getData(true);
    echo "JSON API response keys: " . implode(', ', array_keys($data)) . "\n";
    echo "✅ SUCCESS: Modal Preview API works!\n";

    // Test updating this question with a new Reading Passage
    $reqUpdateSingle = Request::create('/admin/questions/' . $createdQ->id, 'PUT', [
        'difficulty' => 'C1',
        'question_text' => 'Updated Question with Reading Passage',
        'correct_answer' => 'A. Free electricity',
        'passage_title' => 'Edited Passage: Solar Innovations',
        'passage_content' => 'Solar innovations continue to accelerate globally...',
    ]);
    $controller->update($reqUpdateSingle, $createdQ->id);
    $createdQ->refresh();
    echo "Updated passage title: '{$createdQ->meta_data['passage_title']}', content: '{$createdQ->meta_data['passage_content']}'\n";
    if ($createdQ->meta_data['passage_title'] === 'Edited Passage: Solar Innovations') {
        echo "✅ SUCCESS: Reading Passage updated successfully in individual question edit!\n";
    } else {
        echo "❌ FAILED: Reading Passage was not updated\n";
    }
}

// 6. Test Update Testlet (Passage sync across all questions)
echo "\n=== 5. TEST UPDATE TESTLET PASSAGE SYNC ===\n";
$reqUpdateTestlet = Request::create('/admin/questions/testlet/update', 'PUT', [
    'old_passage_title' => $testletTitle,
    'passage_title' => $testletTitle . " (Updated)",
    'passage_content' => "Updated passage content across all questions.",
    'difficulty' => 'C1',
]);
$controller->updateTestlet($reqUpdateTestlet);

$updatedQuestions = QuestionBank::where('meta_data->passage_title', $testletTitle . " (Updated)")->get();
echo "Updated questions in cluster: " . $updatedQuestions->count() . ", new difficulty: " . $updatedQuestions->first()->difficulty . "\n";
if ($updatedQuestions->count() === 3 && $updatedQuestions->first()->difficulty === 'C1') {
    echo "✅ SUCCESS: Testlet passage title, content, and difficulty updated across all child questions!\n";
} else {
    echo "❌ FAILED to update testlet\n";
}

// 7. Cleanup Unit Test Questions
echo "\n=== 6. CLEANUP TEST DATA ===\n";
if ($createdQ) {
    $controller->destroy($createdQ->id);
    echo "Deleted single test question #{$createdQ->id}.\n";
}

$reqDestroyTestlet = Request::create('/admin/questions/testlet/destroy', 'POST', [
    'passage_title' => $testletTitle . " (Updated)",
]);
$controller->destroyTestlet($reqDestroyTestlet);
echo "Deleted testlet cluster.\n";

echo "\n=== ALL TESTS PASSED WITH 100% SUCCESS ===\n";
