<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Media\Models\MediaFile;
use Illuminate\Support\Facades\DB;
use Modules\Review\Models\Review;

class ReviewController extends \Modules\Review\Controllers\ReviewController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth:sanctum');
    }
    
    public function writeReview(Request $request, $type = '', $id = '') {
        try {
            if (!empty($type) && !empty($id)) {
                $request->merge([
                    'review_service_type' => $type,
                    'review_service_id' => $id,
                ]);
            }
            
            if ($type == 'car') {
                $reviewStatsSettings = setting_item('car_review_stats');
                $reviewStats = [];
                
                if (!empty($reviewStatsSettings)) {
                    $reviewStatsSettings = json_decode($reviewStatsSettings, true);
                    
                    $totalPoints = 0;
                    $criteriaCount = 0;
                    
                    foreach ($reviewStatsSettings as $stat) {
                        $criteriaName = $stat['title'];
                        $criteriaKey = 'review_stats_' . \Illuminate\Support\Str::slug($criteriaName, '_');
                        
                        $rating = $request->input($criteriaKey);
                        
                        // Validate và đảm bảo rating hợp lệ (1-5)
                        if (is_numeric($rating) && $rating >= 1 && $rating <= 5) {
                            $reviewStats[$criteriaName] = (int)$rating;
                            $totalPoints += (int)$rating;
                            $criteriaCount++;
                        } else {
                            $reviewStats[$criteriaName] = 5;
                            $totalPoints += 5;
                            $criteriaCount++;
                        }
                    }
                    
                    $avgRating = $criteriaCount > 0 ? $totalPoints / $criteriaCount : 0;
                    $avgRating = round($avgRating, 1); 
                    
                    $request->merge([
                        'review_stats' => $reviewStats,
                        'review_rate' => $avgRating
                    ]);
                }
            }
            
            // Debug request để xem dữ liệu gửi lên
            \Illuminate\Support\Facades\Log::info('Review request data', [
                'request' => $request->all(),
                'files' => $request->file('images') 
            ]);
            
            $result = parent::addReview($request, true);
            
            // Debug kết quả từ parent::addReview
            \Illuminate\Support\Facades\Log::info('Result from parent::addReview', [
                'result' => $result
            ]);
            
            if (is_object($result) && method_exists($result, 'getStatusCode')) {
                return $result;
            }
            
            if (is_array($result) && isset($result['status']) && $result['status'] == 0) {
                return $this->sendError($result['message'] ?? 'Error submitting review');
            }
            
            $uploadedFiles = [];
            $review_upload_files = [];
            $has_videos = false;
            
            // BƯỚC 1: Tìm Review ID
            $reviewId = null;
            
            // Tìm review_id từ nhiều nguồn có thể
            if (isset($result['data']['review'])) {
                $reviewId = $result['data']['review']->id;
            } elseif (isset($result['data']['id'])) {
                $reviewId = $result['data']['id'];
            } elseif (isset($result['data']['review_id'])) {
                $reviewId = $result['data']['review_id'];
            }
            
            // Nếu vẫn không tìm thấy, thử tìm review vừa tạo trong database
            if (!$reviewId) {
                $latestReview = \Modules\Review\Models\Review::where('object_id', $id)
                    ->where('object_model', $type)
                    ->where('author_id', Auth::id())
                    ->orderBy('id', 'desc')
                    ->first();
                    
                if ($latestReview) {
                    $reviewId = $latestReview->id;
                }
            }
            
            \Illuminate\Support\Facades\Log::info('Review ID found', [
                'review_id' => $reviewId
            ]);
            
            // BƯỚC 2: Xử lý media files
            if (isset($result['data']['review_upload_images']) && is_array($result['data']['review_upload_images'])) {
                $review_upload_files = $result['data']['review_upload_images'];
                
                if ($reviewId) {
                    try {
                        // In ra debug log đầy đủ thông tin
                        \Illuminate\Support\Facades\Log::info('Processing review media', [
                            'review_id' => $reviewId,
                            'media_ids' => $review_upload_files
                        ]);
                        
                        // Xóa các bản ghi cũ nếu cần
                        $deleted = DB::table('bravo_review_media')->where('review_id', $reviewId)->delete();
                        \Illuminate\Support\Facades\Log::info('Deleted old media records', ['count' => $deleted]);
                        
                        // Xử lý từng file
                        foreach ($result['data']['review_upload_images'] as $fileId) {
                            $mediaFile = \Modules\Media\Models\MediaFile::find($fileId);
                            if ($mediaFile) {
                                $fileExtension = strtolower($mediaFile->file_extension);
                                $isVideo = in_array($fileExtension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp']);
                                
                                // Lưu trực tiếp vào database thay vì sử dụng model
                                $inserted = DB::table('bravo_review_media')->insert([
                                    'review_id' => $reviewId,
                                    'media_id' => $fileId,
                                    'media_type' => $isVideo ? 'video' : 'image',
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                
                                \Illuminate\Support\Facades\Log::info('Inserted media record', [
                                    'review_id' => $reviewId,
                                    'media_id' => $fileId,
                                    'success' => $inserted
                                ]);
                                
                                if ($isVideo) {
                                    $has_videos = true;
                                }
                                
                                $uploadedFiles[] = [
                                    'id' => $fileId,
                                    'url' => get_file_url($fileId),
                                    'name' => $mediaFile->file_name,
                                    'type' => $isVideo ? 'video' : 'image',
                                    'file_type' => $mediaFile->file_type,
                                    'extension' => $fileExtension
                                ];
                            } else {
                                \Illuminate\Support\Facades\Log::error('Media file not found', [
                                    'file_id' => $fileId
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error processing review media', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        // Nếu có lỗi, đảm bảo uploadedFiles không rỗng
                        if (empty($uploadedFiles)) {
                            foreach ($review_upload_files as $fileId) {
                                $mediaFile = \Modules\Media\Models\MediaFile::find($fileId);
                                if ($mediaFile) {
                                    $fileExtension = strtolower($mediaFile->file_extension);
                                    $isVideo = in_array($fileExtension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp']);
                                    
                                    if ($isVideo) {
                                        $has_videos = true;
                                    }
                                    
                                    $uploadedFiles[] = [
                                        'id' => $fileId,
                                        'url' => get_file_url($fileId),
                                        'name' => $mediaFile->file_name,
                                        'type' => $isVideo ? 'video' : 'image',
                                        'file_type' => $mediaFile->file_type,
                                        'extension' => $fileExtension
                                    ];
                                }
                            }
                        }
                    }
                } else {
                    \Illuminate\Support\Facades\Log::error('Cannot save media - review ID not found');
                    
                    // Nếu không tìm thấy reviewId, vẫn hiển thị thông tin ảnh
                    foreach ($review_upload_files as $fileId) {
                        $mediaFile = \Modules\Media\Models\MediaFile::find($fileId);
                        if ($mediaFile) {
                            $fileExtension = strtolower($mediaFile->file_extension);
                            $isVideo = in_array($fileExtension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp']);
                            
                            if ($isVideo) {
                                $has_videos = true;
                            }
                            
                            $uploadedFiles[] = [
                                'id' => $fileId,
                                'url' => get_file_url($fileId),
                                'name' => $mediaFile->file_name,
                                'type' => $isVideo ? 'video' : 'image',
                                'file_type' => $mediaFile->file_type,
                                'extension' => $fileExtension
                            ];
                        }
                    }
                }
                
                // Trả về response với thông tin đầy đủ
                $responseData = [
                    'review_upload_images' => $review_upload_files,
                    'uploaded_images' => $uploadedFiles,
                    'has_videos' => $has_videos,
                    'review_content' => $reviewId ? Review::find($reviewId)->content : $request->input('review_content')
                ];
                
                if ($type == 'car' && isset($reviewStats)) {
                    $responseData['ratings'] = $reviewStats;
                    $responseData['average_rating'] = $avgRating;
                }
                
                if ($reviewId) {
                    // Lưu media vào bảng bravo_review_meta
                    \Modules\Review\Models\ReviewMeta::where('review_id', $reviewId)
                        ->where('name', 'upload_picture')
                        ->delete();
                    
                    $reviewMeta = new \Modules\Review\Models\ReviewMeta([
                        'review_id' => $reviewId,
                        'object_id' => $id,
                        'object_model' => $type,
                        'name' => 'upload_picture',
                        'val' => json_encode($review_upload_files),
                        'create_user' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $reviewMeta->save();
                    
                    \Illuminate\Support\Facades\Log::info('Saved media to review_meta', [
                        'review_id' => $reviewId,
                        'media_ids' => $review_upload_files
                    ]);
                }
                
                return response()->json([
                    'status' => 1,
                    'message' => __('Đánh giá thành công!'),
                    'data' => $responseData
                ]);
            }
            
            $responseData = [
                'review_upload_images' => [],
                'uploaded_images' => [],
                'has_videos' => false,
                'review_content' => $reviewId ? Review::find($reviewId)->content : $request->input('review_content')
            ];
            
            // Add ratings if car review
            if ($type == 'car' && isset($reviewStats)) {
                $responseData['ratings'] = $reviewStats;
                $responseData['average_rating'] = $avgRating;
            }
            
            return response()->json([
                'status' => 1,
                'message' => __('Đánh giá thành công!'),
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception in writeReview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError($e->getMessage());
        }
    }
    
    public function sendError($message, $data = [], $code = 400) {
        return response()->json([
            'status' => 0,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    public function sendSuccess($data = [], $message = 'Success') {
        return response()->json([
            'status' => 1,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Lấy danh sách đánh giá theo ID của xe
     *
     * @param Request $request
     * @param int $id ID của xe
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCarReviews(Request $request, $id)
    {
        try {
            $car_id = (int)$id;
            
            // check car 
            $allServices = get_reviewable_services();
            $module_class = $allServices['car'] ?? '';
            if (empty($module_class) || !class_exists($module_class)) {
                return $this->sendError(__('Service type không hợp lệ'));
            }
            
            $car = $module_class::find($car_id);
            if (empty($car)) {
                return $this->sendError(__('Không tìm thấy xe'));
            }
            
            $query = \Modules\Review\Models\Review::query()
                ->where('object_id', $car_id)
                ->where('object_model', 'car')
                ->where('status', 'approved')
                ->orderBy('id', 'desc');
                
            // Thêm tìm kiếm theo search
            if ($request->has('search') && !empty($request->input('search'))) {
                $search = $request->input('search');
                $query->where('content', 'like', '%' . $search . '%');
            }
            
            // Phân trang
            $limit = $request->input('limit', 10);
            $reviews = $query->with(['author'])->paginate($limit);
            
            $results = [];
            if (!empty($reviews)) {
                foreach ($reviews as $review) {
                    $reviewMeta = $review->getReviewMeta();
                    $item = [
                        'id' => $review->id,
                        'content' => $review->content,
                        'rate_number' => $review->rate_number,
                        'author' => [
                            'id' => $review->author->id,
                            'name' => $review->author->name,
                            'avatar' => get_file_url($review->author->avatar_id),
                        ],
                        'created_at' => display_datetime($review->created_at),
                        'ratings' => [],
                        'images' => [],
                        'videos' => [],
                        'has_video' => false
                    ];
                    
                    // Lấy ratings
                    foreach ($reviewMeta as $meta) {
                        if ($meta->name !== 'upload_picture') {
                            $item['ratings'][$meta->name] = (int)$meta->val;
                        }
                    }
                    
                    // Lấy media từ bảng bravo_review_media
                    $reviewMedia = \Modules\Review\Models\ReviewMedia::where('review_id', $review->id)->get();
                    
                    foreach ($reviewMedia as $media) {
                        $mediaFile = \Modules\Media\Models\MediaFile::find($media->media_id);
                        if ($mediaFile) {
                            $fileExtension = strtolower($mediaFile->file_extension);
                            
                            $mediaItem = [
                                'id' => $media->media_id,
                                'url' => get_file_url($media->media_id),
                                'type' => $media->media_type,
                                'name' => $mediaFile->file_name,
                                'extension' => $fileExtension,
                                'file_type' => $mediaFile->file_type,
                                'size' => $mediaFile->file_size ?? 0
                            ];
                            
                            if ($media->media_type === 'video') {
                                $item['has_video'] = true;
                                $item['videos'][] = $mediaItem;
                            } else {
                                $item['images'][] = $mediaItem;
                            }
                        }
                    }
                    
                    // Tính điểm trung bình từ ratings
                    $totalPoints = array_sum($item['ratings']);
                    $ratingCount = count($item['ratings']);
                    
                    if ($ratingCount > 0) {
                        $item['rating_average'] = round($totalPoints / $ratingCount, 1);
                    } else {
                        $item['rating_average'] = 0;
                    }
                    
                    $results[] = $item;
                }
            }
            
            return $this->sendSuccess([
                'total' => $reviews->total(),
                'total_pages' => $reviews->lastPage(),
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'reviews' => $results
            ]);
            
        } catch (\Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }
}