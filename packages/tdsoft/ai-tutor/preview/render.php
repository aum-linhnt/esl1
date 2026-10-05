<?php
// Standalone view renderer. Never load bootstrap/app.php, Dotenv, database or package providers.
require dirname(__DIR__, 4).'/vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ViewServiceProvider;

$page = $argv[1] ?? 'widget';
$port = (int) ($argv[2] ?? 9013);
$cache = $argv[3] ?? '';
if (! in_array($page, ['widget', 'tutor', 'knowledge'], true) || ! is_dir($cache)
    || ! str_starts_with(realpath($cache), realpath(sys_get_temp_dir()).'/ai-tutor-preview-')) {
    exit(1);
}
$app = new Application(dirname(__DIR__, 4));
$app->instance('env', 'testing');
$app->instance('config', new Repository([
    'view' => ['paths' => [__DIR__.'/../resources/views'], 'compiled' => $cache],
    'ai-tutor' => ['theme' => ['default' => 'dark', 'allow_user_switch' => true],
        'ui' => ['asset_entries' => []], 'license' => [
            'admin_layout' => 'ai-tutor::layouts.admin', 'admin_section' => 'content',
            'admin_theme' => 'dark', 'asset_entries' => [],
        ]],
]));
$app->instance('files', new Filesystem);
$app->instance('events', new Dispatcher($app));
$app->instance('request', Request::create('http://127.0.0.1:'.$port));
$app->instance('url', new UrlGenerator(new RouteCollection, $app['request']));
$app->instance('session', new Store('preview', new ArraySessionHandler(60)));
$app['session']->put('_token', 'preview-only-no-real-auth');
Facade::setFacadeApplication($app);
$app->register(ViewServiceProvider::class);
$app['view']->addNamespace('ai-tutor', __DIR__.'/../resources/views');
$data = [
    'actorId' => 'preview-learner-'.preg_replace('/[^a-zA-Z0-9-]/', '', $argv[4] ?? 'isolated'),
    'lesson' => new \TDSoft\AiTutor\Core\LessonContext('course-1', 'lesson-1', 'Present simple'),
    'settings' => ['position' => 'bottom-right', 'mode' => 'drawer', 'width' => 420, 'expand' => true],
];
if ($page === 'widget') {
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="preview-only-no-real-auth"><title>Preview bài học</title></head><body data-ai-tutor-root data-ai-tutor-theme="dark">';
    echo '<main class="tai-license"><h1>Bài học mẫu: Present simple</h1><section><h2>She works every day.</h2><p>Đây là nội dung mẫu, không lấy từ LMS. Bấm Gia sư AI ở góc dưới để mở widget.</p><p>Thử gửi câu hỏi, đóng/mở và mở rộng khung trong khi câu trả lời đang chạy.</p></section></main>';
    echo $app['view']->make('ai-tutor::widget', $data)->render();
    echo '</body></html>';
} else {
    echo $app['view']->make('ai-tutor::'.$page, $data)->render();
}
