<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;
use Modules\Location\Models\Location;

class LifestyleController extends Controller
{
    public function getList(Request $request)
    {
        $items = News::where('status', 'publish')
            ->with(['translation', 'category', 'author', 'locationInfo'])
            ->when($request->has('is_homepage'), function ($query) {
                $query->where('is_homepage', 1);
            })
            ->when($request->has('category_id'), function ($query) use ($request) {
                $query->where('cat_id', $request->category_id);
            })
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'items' => $items->map(function ($item) {
                    $location = Location::find($item->location);
                    return [
                        'id'          => $item->id,
                        'title'       => $item->translation->title ?? $item->title,
                        'subtitle'    => $item->translation->subtitle ?? $item->subtitle,
                        'location'    => [
                            'id'      => $item->location,
                            'name'    => $location ? $location->name : null,
                            'map_lat' => $item->map_lat ?? null,
                            'map_lng' => $item->map_lng ?? null,
                        ],
                        'slug'        => $item->slug,
                        'content'     => strip_tags($item->translation->content ?? $item->content),
                        'image'       => get_file_url($item->image_id, 'full'),
                        'category'    => [
                            'id'   => $item->category->id ?? null,
                            'name' => $item->category->name ?? null,
                        ],
                        'author'      => [
                            'id'             => $item->author->id ?? null,
                            'name'           => $item->author->getDisplayName() ?? null,
                            'avatar'         => get_file_url($item->author->avatar_id ?? null),
                            'comments_count' => $item->comments_count ?? 0,
                        ],
                        'is_homepage' => (bool)$item->is_homepage,
                        'created_at'  => display_date($item->created_at),
                    ];
                }),
            ],
        ]);
    }

    public function getDetail($id)
    {
        $item = News::where('id', $id)
            ->where('status', 'publish')
            ->with(['translation', 'category', 'author'])
            ->first();

        if (!$item) {
            return response()->json([
                'status'  => 'error',
                'message' => __('Lifestyle not found'),
            ], 404);
        }

        // Lấy các bài viết liên quan cùng danh mục
        $related = News::where('cat_id', $item->cat_id)
            ->where('status', 'publish')
            ->where('id', '!=', $item->id)
            ->with(['translation', 'category', 'author'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'         => $item->id,
                'title'      => $item->translation->title ?? $item->title,
                'subtitle'   => $item->translation->subtitle ?? $item->subtitle,
                'location'   => $item->translation->location ?? $item->location,
                'slug'       => $item->slug,
                'content'    => $item->translation->content ?? $item->content,
                'image'      => get_file_url($item->image_id, 'full'),
                'gallery'    => array_map(function ($image_id) {
                    return get_file_url($image_id, 'full');
                }, explode(',', $item->gallery)),
                'category'   => [
                    'id'   => $item->category->id ?? null,
                    'name' => $item->category->name ?? null,
                    'slug' => $item->category->slug ?? null,
                ],
                'author'     => [
                    'id'             => $item->author->id ?? null,
                    'name'           => $item->author->getDisplayName() ?? null,
                    'avatar'         => get_file_url($item->author->avatar_id ?? null),
                    'comments_count' => $item->comments_count ?? 0,
                ],
                'is_homepage' => (bool)$item->is_homepage,
                'created_at'  => display_date($item->created_at),
                'related'     => $related->map(function ($item) {
                    return [
                        'id'         => $item->id,
                        'title'      => $item->translation->title ?? $item->title,
                        'subtitle'   => $item->translation->subtitle ?? $item->subtitle,
                        'location'   => $item->translation->location ?? $item->location,
                        'slug'       => $item->slug,
                        'image'      => get_file_url($item->image_id, 'full'),
                        'category'   => [
                            'id'   => $item->category->id ?? null,
                            'name' => $item->category->name ?? null,
                        ],
                        'created_at' => display_date($item->created_at),
                    ];
                }),
            ],
        ]);
    }

    public function getCategories()
    {
        $categories = NewsCategory::where('status', 'publish')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'id'         => $category->id,
                        'name'       => $category->translation->name ?? $category->name,
                        'slug'       => $category->slug,
                        'count_news' => $category->news()->where('status', 'publish')->count(),
                    ];
                }),
            ],
        ]);
    }
}
