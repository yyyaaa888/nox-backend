<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Language;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page'  => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ], [
            'page.integer'  => __('参数 page 不正确'),
            'page.min'      => __('参数 page 不正确'),
            'limit.integer' => __('参数 limit 不正确'),
            'limit.min'     => __('参数 limit 不正确'),
            'limit.max'     => __('参数 limit 不正确'),
        ]);
        if ($validator->fails()) {
            return apiError($validator->errors()->first());
        }

        $limit = $request->input('limit', 15);

        $lang = Language::getLang();
        if ($lang != Language::DEFAULT) {
            $langs = [$lang, Language::DEFAULT];
        } else {
            $langs = [$lang];
        }

        $paginate = Article::published()
            ->with(['attrs' => function ($query) use ($langs) {
                $query->whereIn('lang', $langs);
            }, 'cate.attrs' => function ($query) use ($langs) {
                $query->whereIn('lang', $langs);
            }])
            ->orderBy('is_top', 'desc')
            ->orderBy('publish_time', 'desc')
            ->orderBy('order', 'desc')
            ->paginate($limit);
        $collection = collect();
        foreach ($paginate as $item) {
            $attr = $item->attrs->where('lang', $lang)->first();
            if (! $attr && $lang != Language::DEFAULT) {
                $attr = $item->attrs->where('lang', Language::DEFAULT)->first();
            }
            $cate      = $item->cate;
            $cate_attr = $cate->attrs->where('lang', $lang)->first();
            if (! $cate_attr && $lang != Language::DEFAULT) {
                $cate_attr = $cate->attrs->where('lang', Language::DEFAULT)->first();
            }
            $collection->push([
                'id'        => $item->id,
                'cate_name' => $cate_attr?->name,
                // 'cate_color' => $cate?->color,
                'title'        => $attr?->title,
                'content'      => $attr?->content,
                'mini_img'     => $item->mini_img ? asset('/storage/'.$item->mini_img) : null,
                'publish_time' => $item->publish_time,
            ]);
        }
        $paginate->setCollection($collection);

        return apiPage($paginate);
    }

    public function show(Request $request)
    {
        $id = $request->input('id');
        if (! $id) {
            return apiError(__('参数不正确'));
        }

        $lang    = Language::getLang();
        $article = Article::published()->with(['attrs' => function ($query) use ($lang) {
            $query->where('lang', $lang);
        }])->find($id);
        if (! $article) {
            return apiError(__('文章不存在'));
        }
        $attr = $article->attrs->where('lang', $lang)->first();
        if (! $attr && $lang != Language::DEFAULT) {
            $attr = $article->attrs->where('lang', Language::DEFAULT)->first();
        }

        return apiSuccess([
            'id'           => $article->id,
            'title'        => $attr?->title,
            'content'      => $attr?->content,
            'publish_time' => $article->publish_time,
        ]);
    }
}
