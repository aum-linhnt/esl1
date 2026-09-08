<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch application language (Moodle-style: vi, en).
     */
    public function switch(Request $request, string $locale)
    {
        if (in_array($locale, SetLocale::SUPPORTED_LOCALES)) {
            Session::put('locale', $locale);
            
            $msg = $locale === 'vi' 
                ? '🇻🇳 Đã chuyển ngôn ngữ sang Tiếng Việt (vi)!' 
                : '🇬🇧 Language switched to English (en)!';

            return redirect()->back()->with('success', $msg);
        }

        return redirect()->back();
    }
}
