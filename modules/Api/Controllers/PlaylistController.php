<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Playlist\Models\Playlist;
// *** THÊM LẠI CÁC DÒNG NÀY ***
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Log;

class PlaylistController extends Controller
{
    // ... (constructor nếu có) ...

    /**
     * Lưu một playlist mới được tạo.
     *
     * @param  Request  $request // *** SỬA LẠI THÀNH Request ***
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request) // *** SỬA LẠI THÀNH Request ***
    {
        $user = $request->user(); // Lấy user từ request đã được xác thực

        // *** THỰC HIỆN VALIDATION THỦ CÔNG ***
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:191'], // Các quy tắc validation
            // Thêm các rule khác nếu cần
        ]);

        // Kiểm tra nếu validation thất bại
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', ['errors' => $validator->errors()], 422);
            // Hoặc có thể dùng response()->json(...) nếu không có sendError helper
            // return response()->json([
            //     'status' => 0,
            //     'message' => 'Validation Error.',
            //     'errors' => $validator->errors()
            // ], 422);
        }

        // Lấy dữ liệu đã được validate thành công
        $validatedData = $validator->validated();

        // *** TIẾP TỤC LOGIC TẠO PLAYLIST ***
        try {
            $playlist = Playlist::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'], // Lấy name từ dữ liệu đã validate
                'metadata' => null,
            ]);

            // --- Chuẩn bị Response ---
            return $this->sendSuccess([
                'data' => [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                    'metadata' => $playlist->metadata,
                    'created_at' => $playlist->created_at->toIso8601String(),
                    'updated_at' => $playlist->updated_at->toIso8601String(),
                    'track_count' => count($playlist->getTrackIds()),
                ],
                'message' => 'Playlist created successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('API Playlist Creation Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return $this->sendError('An error occurred while creating the playlist.', [], 500);
        }
    }

    /**
     * Xóa một track cụ thể khỏi playlist.
     *
     * @param  Request  $request
     * @param  Playlist $playlist  (Route model binding tự động tìm playlist theo ID từ URL)
     * @param  string|int $trackId  ID của track cần xóa từ URL
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeTrack(Request $request, Playlist $playlist, $trackId)
    {
        $user = $request->user();

        // --- BƯỚC QUAN TRỌNG: KIỂM TRA QUYỀN SỞ HỮU ---
        if ($playlist->user_id !== $user->id) {
             Log::warning("API Playlist Remove Track: User {$user->id} attempted to modify playlist {$playlist->id} owned by {$playlist->user_id}");
            return $this->sendError('Forbidden. You do not own this playlist.', [], 403);
        }
        // --- KẾT THÚC KIỂM TRA QUYỀN ---

        Log::info("API Playlist Remove Track: User {$user->id} attempting to remove track '{$trackId}' from playlist '{$playlist->name}' ({$playlist->id})");

        try {
            // Gọi phương thức trong Model để xóa track ID
            $removed = $playlist->removeTrackId($trackId);

            if ($removed) {
                Log::info("API Playlist Remove Track: Successfully removed track '{$trackId}' from playlist {$playlist->id}");
                // Trả về 204 No Content là phù hợp cho DELETE thành công không cần trả về dữ liệu
                // Hoặc trả về 200 OK với message nếu muốn
                return response()->json([
                     'status' => 1, // Hoặc true
                     'message' => 'Track removed successfully from playlist.',
                ], 200);
                // return response()->noContent(); // Cách khác trả về 204
            } else {
                // Track ID không tồn tại trong playlist này
                Log::warning("API Playlist Remove Track: Track '{$trackId}' not found in playlist {$playlist->id}");
                return $this->sendError('Track not found in this playlist.', [], 404);
            }

        } catch (\Exception $e) {
            Log::error('API Playlist Remove Track Error: ' . $e->getMessage() . ' for playlist ' . $playlist->id);
            Log::error($e->getTraceAsString());
            return $this->sendError('An error occurred while removing the track.', [], 500);
        }
    }

    /**
     * Xóa một playlist cụ thể.
     *
     * @param  Request  $request
     * @param  Playlist $playlist  (Route model binding tự động inject Playlist dựa vào ID trên URL)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function destroy(Request $request, Playlist $playlist)
    {
        $user = $request->user(); 

        if ($playlist->user_id !== $user->id) {
            Log::warning("API Playlist Delete: User {$user->id} attempted to delete playlist {$playlist->id} owned by {$playlist->user_id}");
            // Sử dụng helper sendError nếu có, hoặc trả về JSON trực tiếp
            return $this->sendError('Forbidden. You do not own this playlist.', [], 403);
        }
        // --- KẾT THÚC KIỂM TRA QUYỀN ---

        Log::info("API Playlist Delete: User {$user->id} attempting to delete playlist '{$playlist->name}' ({$playlist->id})");

        try {
            // Thực hiện xóa playlist khỏi database
            $deleted = $playlist->delete(); // Phương thức delete() của Eloquent

            if ($deleted) {
                Log::info("API Playlist Delete: Successfully deleted playlist {$playlist->id}");
                return response()->noContent();
                // return $this->sendSuccess([
                //     'message' => 'Playlist deleted successfully.'
                // ]);

            } else {
                // Trường hợp hiếm gặp khi delete() trả về false mà không ném Exception
                 Log::error("API Playlist Delete: Failed to delete playlist {$playlist->id} for unknown reasons.");
                return $this->sendError('Failed to delete the playlist.', [], 500);
            }

        } catch (\Exception $e) {
            Log::error('API Playlist Delete Error: ' . $e->getMessage() . ' for playlist ' . $playlist->id);
            Log::error($e->getTraceAsString()); // Ghi lại stack trace để debug
            // Trả về lỗi server
            return $this->sendError('An error occurred while deleting the playlist.', [], 500);
        }
    }

    /**
     * Thêm một track vào playlist cụ thể.
     *
     * @param  Request  $request
     * @param  Playlist $playlist (Route model binding tự động tìm playlist)
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTrack(Request $request, Playlist $playlist)
    {
        $user = $request->user();

        // --- BƯỚC QUAN TRỌNG: KIỂM TRA QUYỀN SỞ HỮU ---
        if ($playlist->user_id !== $user->id) {
            Log::warning("API Playlist Add Track: User {$user->id} attempted to add track to playlist {$playlist->id} owned by {$playlist->user_id}");
            return $this->sendError('Forbidden. You do not own this playlist.', [], 403); // Hoặc response()->json(...) nếu không có helper sendError
        }
        // --- KẾT THÚC KIỂM TRA QUYỀN ---

        // --- VALIDATE DỮ LIỆU ĐẦU VÀO ---
        $validator = Validator::make($request->all(), [
            'track_id' => ['required', /* 'string' or 'integer' tùy định dạng ID */ 'max:255'], // ID của track cần thêm
            // Thêm các validation khác nếu cần cho track (ví dụ: track_source,...)
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', ['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $trackId = $validatedData['track_id']; // Lấy track_id đã được validate

        Log::info("API Playlist Add Track: User {$user->id} attempting to add track '{$trackId}' to playlist '{$playlist->name}' ({$playlist->id})");

        try {
            // Gọi phương thức trong Model để thêm track ID
            // Phương thức addTrackId trong Model của bạn đã xử lý việc kiểm tra trùng lặp
            $added = $playlist->addTrackId($trackId);

            if ($added) {
                Log::info("API Playlist Add Track: Successfully added track '{$trackId}' to playlist {$playlist->id}");
                // Trả về thành công, có thể kèm theo thông tin playlist đã cập nhật hoặc chỉ message
                return $this->sendSuccess([
                    'message' => 'Track added successfully to playlist.',
                ]);
            } else {
                // Trường hợp track ID đã tồn tại trong playlist (do addTrackId trả về false)
                Log::warning("API Playlist Add Track: Track '{$trackId}' already exists in playlist {$playlist->id}");
                return $this->sendError('Track already exists in this playlist.', [], 409); // 409 Conflict là phù hợp
            }

        } catch (\Exception $e) {
            Log::error('API Playlist Add Track Error: ' . $e->getMessage() . ' for playlist ' . $playlist->id . ' and track ' . $trackId);
            Log::error($e->getTraceAsString());
            return $this->sendError('An error occurred while adding the track.', [], 500);
        }
    }
}