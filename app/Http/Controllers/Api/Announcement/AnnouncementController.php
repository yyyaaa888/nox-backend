<?php

namespace App\Http\Controllers\Api\Announcement;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);

        $lang  = app()->getLocale() ?: 'en';
        $langs = [
            'zh_CN' => 'cn',
            'en'    => 'en',
            'zh_TW' => 'tw',
        ];
        $lang_key = $langs[$lang] ?? 'en';
        $fields   = [
            'id',
            'title_'.$lang_key.' as title',
            'content_'.$lang_key.' as content',
            'publish_time',
        ];
        $announcements = Announcement::published()
            ->select($fields)
            ->orderBy('order', 'desc')
            ->orderBy('publish_time', 'desc')
            ->paginate($limit);

        return apiPage($announcements);
    }
}
