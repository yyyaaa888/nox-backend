<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Language;
use App\Models\Database;

class DatabaseController extends Controller
{
    public function index()
    {
        $lang = Language::getLang();
        $info = Database::query()->where('type', Database::TYPE_INTRO)->first();
        if (! $info || ! $info->data) {
            return apiError(__('介绍未配置'));
        }

        $intros = $info->data;

        $title   = null;
        $content = null;
        foreach ($intros as $intro) {
            if ($intro['lang'] == $lang) {
                $title   = $intro['title'] ?? null;
                $content = $intro['content'] ?? null;
                break;
            } elseif ($intro['lang'] == Language::DEFAULT) {
                $title   = $intro['title'] ?? null;
                $content = $intro['content'] ?? null;
            }
        }

        $lang      = Language::getLang();
        $documents = Database::query()->where('type', Database::TYPE_DOCUMENT)->first();
        $list1     = [];
        if ($documents && $documents->data) {
            foreach ($documents->data as $document) {
                if (isset($document['title_'.$lang])) {
                    $title = $document['title_'.$lang] ?? null;
                } else {
                    $title = $document['title_'.Language::DEFAULT] ?? null;
                }
                $file    = $document['document'] ?? null;
                $list1[] = [
                    'title' => $title,
                    'file'  => $file ? asset('/storage/'.$file) : null,
                    'time'  => $document['time'] ?? null,
                ];
            }
        }

        $posters = Database::query()->where('type', Database::TYPE_POSTER)->first();
        $list2   = [];
        if ($posters && $posters->data) {
            foreach ($posters->data as $poster) {
                if (isset($poster['title_'.$lang])) {
                    $title = $poster['title_'.$lang] ?? null;
                } else {
                    $title = $poster['title_'.Language::DEFAULT] ?? null;
                }
                $file    = $poster['poster'] ?? null;
                $list2[] = [
                    'title' => $title,
                    'file'  => $file ? asset('/storage/'.$file) : null,
                    'time'  => $poster['time'] ?? null,
                ];
            }
        }

        $videos = Database::query()->where('type', Database::TYPE_VIDEO)->first();
        $list3  = [];
        if ($videos && $videos->data) {
            foreach ($videos->data as $video) {
                if (isset($video['title_'.$lang])) {
                    $title = $video['title_'.$lang] ?? null;
                } else {
                    $title = $video['title_'.Language::DEFAULT] ?? null;
                }
                $file    = $video['video'] ?? null;
                $list3[] = [
                    'title' => $title,
                    'file'  => $file ? asset('/storage/'.$file) : null,
                    'time'  => $video['time'] ?? null,
                ];
            }
        }

        $videoMeetings = Database::query()->where('type', Database::TYPE_VIDEO_MEETING)->first();
        $list4         = [];
        if ($videoMeetings && $videoMeetings->data) {
            foreach ($videoMeetings->data as $videoMeeting) {
                if (isset($videoMeeting['title_'.$lang])) {
                    $title = $videoMeeting['title_'.$lang] ?? null;
                } else {
                    $title = $videoMeeting['title_'.Language::DEFAULT] ?? null;
                }
                $link    = $videoMeeting['link'] ?? null;
                $list4[] = [
                    'title' => $title,
                    'link'  => $link,
                    'time'  => $videoMeeting['time'] ?? null,
                ];
            }
        }

        return apiSuccess([
            'title'         => $title,
            'content'       => $content,
            'documents'     => $list1,
            'posters'       => $list2,
            'videos'        => $list3,
            'videoMeetings' => $list4,
        ]);
    }

    // 相关文档
    public function documents()
    {
        $lang      = Language::getLang();
        $documents = Database::query()->where('type', Database::TYPE_DOCUMENT)->first();
        if (! $documents || ! $documents->data) {
            return apiError(__('相关文档未配置'));
        }

        $list = [];
        foreach ($documents->data as $document) {
            if (isset($document['title_'.$lang])) {
                $title = $document['title_'.$lang] ?? null;
            } else {
                $title = $document['title_'.Language::DEFAULT] ?? null;
            }
            $file   = $document['document'] ?? null;
            $list[] = [
                'title' => $title,
                'file'  => $file ? asset('/storage/'.$file) : null,
                'time'  => $document['time'] ?? null,
            ];
        }

        return apiSuccess($list);
    }

    // 相关海报
    public function posters()
    {
        $lang    = Language::getLang();
        $posters = Database::query()->where('type', Database::TYPE_POSTER)->first();
        if (! $posters || ! $posters->data) {
            return apiError(__('相关海报未配置'));
        }

        $list = [];
        foreach ($posters->data as $poster) {
            if (isset($poster['title_'.$lang])) {
                $title = $poster['title_'.$lang] ?? null;
            } else {
                $title = $poster['title_'.Language::DEFAULT] ?? null;
            }
            $file   = $poster['poster'] ?? null;
            $list[] = [
                'title' => $title,
                'file'  => $file ? asset('/storage/'.$file) : null,
                'time'  => $poster['time'] ?? null,
            ];
        }

        return apiSuccess($list);
    }

    // 相关视频
    public function videos()
    {
        $lang   = Language::getLang();
        $videos = Database::query()->where('type', Database::TYPE_VIDEO)->first();
        if (! $videos || ! $videos->data) {
            return apiError(__('相关视频未配置'));
        }

        $list = [];
        foreach ($videos->data as $video) {
            if (isset($video['title_'.$lang])) {
                $title = $video['title_'.$lang] ?? null;
            } else {
                $title = $video['title_'.Language::DEFAULT] ?? null;
            }
            $file   = $video['video'] ?? null;
            $list[] = [
                'title' => $title,
                'file'  => $file ? asset('/storage/'.$file) : null,
                'time'  => $video['time'] ?? null,
            ];
        }

        return apiSuccess($list);
    }

    // 视频会议
    public function videoMeetings()
    {
        $lang          = Language::getLang();
        $videoMeetings = Database::query()->where('type', Database::TYPE_VIDEO_MEETING)->first();
        if (! $videoMeetings) {
            return apiError(__('视频会议未配置'));
        }

        $list = [];
        foreach ($videoMeetings->data as $videoMeeting) {
            if (isset($videoMeeting['title_'.$lang])) {
                $title = $videoMeeting['title_'.$lang] ?? null;
            } else {
                $title = $videoMeeting['title_'.Language::DEFAULT] ?? null;
            }
            $link   = $videoMeeting['link'] ?? null;
            $list[] = [
                'title' => $title,
                'link'  => $link,
                'time'  => $videoMeeting['time'] ?? null,
            ];
        }

        return apiSuccess($list);
    }
}
