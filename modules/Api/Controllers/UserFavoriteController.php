<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Modules\Api\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserFavoriteController extends Controller
{
    public function index()
{
    $userId = Auth::id();
    $favorite = UserFavorite::where('user_id', $userId)->first();

    // Kiểm tra dữ liệu trước khi trả về
    if (!$favorite) {
        return response()->json([
            'success' => true,
            'track_ids' => [] // Trả về mảng rỗng nếu không có bản ghi
        ]);
    }

    // Đảm bảo $favorite->song_ids là array hoặc null
    $songIds = $favorite->song_ids ?? [];

    return response()->json([
        'success' => true,
        'track_ids' => $songIds
    ]);
}

    public function store(Request $request)
{
    $request->validate([
        'song_id' => 'required|string',
    ]);

    $userId = Auth::id();
    $newSongId = trim($request->input('song_id'));

    // Kiểm tra song_id hợp lệ
    if (empty($newSongId)) {
        return response()->json([
            'success' => false,
            'message' => 'Song ID không hợp lệ'
        ], 400);
    }

    // Tìm hoặc tạo bản ghi
    $favorite = UserFavorite::firstOrCreate(
        ['user_id' => $userId],
        ['song_ids' => []]
    );

    // Kiểm tra trùng lặp
    if (in_array($newSongId, $favorite->song_ids)) {
        return response()->json([
            'success' => false,
            'message' => 'Bài hát đã có trong danh sách yêu thích'
        ], 400);
    }

    // Thêm song_id mới vào mảng
    $songIds = $favorite->song_ids;
    $songIds[] = $newSongId;
    $favorite->song_ids = $songIds;
    $favorite->save();

    return response()->json([
        'success' => true,
        'message' => 'Đã thêm bài hát yêu thích',
    ], 201);
}

public function destroy($songId)
{
    $userId = Auth::id();
    $favorite = UserFavorite::where('user_id', $userId)->first();

    if (!$favorite) {
        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy danh sách yêu thích',
        ], 404);
    }

    // Lọc bỏ song_id cần xóa
    $songIds = array_diff($favorite->song_ids, [$songId]);
    $favorite->song_ids = array_values($songIds); // Reset array keys
    $favorite->save();

    return response()->json([
        'success' => true,
        'message' => 'Đã xóa bài hát khỏi danh sách yêu thích',
    ]);
}
}