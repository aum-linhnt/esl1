<?php

namespace App\Support;

use Illuminate\Http\Request;

final class LessonLayout
{
    public static function resolve(Request $request): string
    {
        $data = $request->validate(['layout' => 'nullable|in:classic,tutor']);
        $key = 'lesson_layout.'.($request->user()?->getAuthIdentifier() ?? 'guest');
        if (isset($data['layout'])) {
            $request->session()->put($key, $data['layout']);
        }

        return $request->session()->get($key, 'classic') === 'tutor'
            ? 'lessons.tutor' : 'lessons.show';
    }
}
