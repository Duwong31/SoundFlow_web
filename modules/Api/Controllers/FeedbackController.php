<?php
namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Media\Models\MediaFile;
use Modules\Feedback\Models\Feedback;

class FeedbackController extends Controller
{
    public function submit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string',
                'images' => 'sometimes',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10000',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10000',
                'media_ids' => 'sometimes|array',
                'media_ids.*' => 'sometimes|integer|exists:media_files,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->errors()->first());
            }

            $mediaIds = [];
            $uploadedImages = [];
            
            if ($request->hasFile('images')) {
                $this->processUploadedFiles($request->file('images'), $mediaIds, $uploadedImages);
            } 
            else if ($request->hasFile('image')) {
                $this->processUploadedFiles([$request->file('image')], $mediaIds, $uploadedImages);
            }
            else {
                foreach ($request->allFiles() as $fieldName => $files) {
                    if (is_array($files)) {
                        $this->processUploadedFiles($files, $mediaIds, $uploadedImages);
                    } else {
                        $this->processUploadedFiles([$files], $mediaIds, $uploadedImages);
                    }
                }
            }

            if ($request->has('media_ids') && is_array($request->input('media_ids'))) {
                $invalidMediaIds = [];
                
                foreach ($request->input('media_ids') as $mediaId) {
                    $mediaFile = MediaFile::find($mediaId);
                    if ($mediaFile && $mediaFile->create_user == Auth::id()) {
                        $mediaIds[] = $mediaId;
                        
                        $fileExtension = strtolower($mediaFile->file_extension);
                        $isVideo = in_array($fileExtension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp']);
                        
                        $uploadedImages[] = [
                            'id' => $mediaId,
                            'url' => get_file_url($mediaId),
                            'name' => $mediaFile->file_name,
                            'type' => $isVideo ? 'video' : 'image',
                            'file_type' => $mediaFile->file_type,
                            'extension' => $fileExtension
                        ];
                    } else {
                        $invalidMediaIds[] = $mediaId;
                    }
                }
                
                if (!empty($invalidMediaIds)) {
                    return $this->sendError(
                        'File media không thuộc về bạn', 
                        ['invalid_media_ids' => $invalidMediaIds]
                    );
                }
            }

            $feedback = new Feedback();
            $feedback->content = $request->input('content');
            $feedback->user_id = Auth::id();
            $feedback->status = 'pending';
            
            if (!empty($mediaIds)) {
                $feedback->media_ids = json_encode($mediaIds);
            }
            
            $feedback->save();

            return response()->json([
                'status' => 1,
                'message' => __('Góp ý của bạn đã được gửi thành công!'),
                'data' => [
                    'id' => $feedback->id,
                    'content' => $feedback->content,
                    'status' => $feedback->status,
                    'created_at' => display_datetime($feedback->created_at),
                    'uploaded_images' => $uploadedImages,
                    'invalid_media_ids' => $invalidMediaIds ?? []
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    private function processUploadedFiles($files, &$mediaIds, &$uploadedImages)
    {
        foreach ($files as $index => $image) {
            if (!$image || !$image->isValid()) {
                continue;
            }

            try {
                // Chỉ sử dụng phương pháp 1: MediaFile class
                $upload = MediaFile::saveUploadedImage($image, 'feedback');
                
                if (isset($upload['status']) && $upload['status']) {
                    $mediaIds[] = $upload['data']->id;
                    $uploadedImages[] = [
                        'id' => $upload['data']->id,
                        'url' => get_file_url($upload['data']->id),
                        'name' => $upload['data']->file_name,
                        'type' => 'image',
                        'file_type' => $upload['data']->file_type,
                        'extension' => $upload['data']->file_extension
                    ];
                }
            } catch (\Exception $e) {
                // Bỏ qua các lỗi nếu có
            }
        }
    }

    public function sendError($message, $data = [], $code = 400)
    {
        return response()->json([
            'status' => 0,
            'message' => $message,
            'data' => $data
        ], $code);
    }
} 