<?php

namespace Modules\Review\Controllers;

use App\Helpers\ReCaptchaEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Events\CreateReviewEvent;
use Modules\Review\Models\Review;
use Modules\Review\Models\ReviewMeta;
use Validator;
use Illuminate\Support\Facades\Auth;
use Modules\Media\Models\MediaFile;
use Illuminate\Support\Facades\Log;
use App\Helpers\FileHelper;

class ReviewController extends Controller
{
    public function __construct()
    {
    }

    public function addReview(Request $request, $is_return = false)
    {
        $service_type = $request->input('review_service_type');
        $service_id = $request->input('review_service_id');
        
        if(empty($service_type) || empty($service_id)){
            if($is_return){
                return [
                    'status' => 0,
                    'message' => __('Service type or service id is required')
                ];
            }
            return redirect()->back()->with('error', __('Service type or service id is required'));
        }
        
        $reviewScore = $request->input('review_score');
        $reviewContent = $request->input('review_content');
        
        $review_upload_images = [];
        
        // Debug information about the request
        $debug = [
            'has_images_field' => $request->hasFile('images'),
            'has_video_field' => $request->hasFile('video'),
            'file_keys' => array_keys($request->allFiles()),
        ];
        
        // Check for different possible file field names
        $fileField = null;
        $fileArray = null;
        
        if ($request->hasFile('images')) {
            $fileField = 'images';
            $fileArray = $request->file('images');
        } elseif ($request->hasFile('videos')) {
            $fileField = 'videos';
            $fileArray = $request->file('videos');
        } elseif ($request->hasFile('files')) {
            $fileField = 'files';
            $fileArray = $request->file('files');
        } elseif ($request->hasFile('video')) {
            $fileField = 'video';
            $fileArray = $request->file('video');
        } elseif ($request->hasFile('review_upload_image')) {
            $fileField = 'review_upload_image';
            $fileArray = $request->file('review_upload_image');
        }
        
        // Debug file information
        if ($fileField) {
            $debug['selected_field'] = $fileField;
            $debug['is_array'] = is_array($fileArray);
            $debug['file_count'] = is_array($fileArray) ? count($fileArray) : 1;
        }
        
        if ($fileField && $fileArray) {
            // Convert single file to array for consistent processing
            if (!is_array($fileArray)) {
                $fileArray = [$fileArray];
            }
            
            foreach ($fileArray as $index => $file) {
                try {
                    // Debug information for this file
                    $fileDebug = [
                        'index' => $index,
                        'is_object' => is_object($file),
                        'class' => is_object($file) ? get_class($file) : 'not an object',
                    ];
                    
                    if (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
                        $fileDebug['extension'] = $file->getClientOriginalExtension();
                        $fileDebug['size'] = $file->getSize();
                        $fileDebug['mime'] = $file->getMimeType();
                    }
                    
                    $debug['files'][$index] = $fileDebug;
                    
                    // Validate the file
                    $validate = $this->validateUploadImage($file);
                    if ($validate !== true) {
                        $debug['validation_error'] = $validate;
                        if ($is_return) {
                            return [
                                'status' => 0,
                                'message' => $validate,
                                'debug' => $debug
                            ];
                        }
                        return redirect()->back()->with('error', $validate);
                    }
                    
                    // Process image/video upload using MediaFile
                    if (class_exists('\\Modules\\Media\\Models\\MediaFile')) {
                        $upload = \Modules\Media\Models\MediaFile::saveUploadedImage($file);
                        
                        // Debug upload result
                        $fileDebug['upload_result'] = $upload;
                        $debug['files'][$index] = $fileDebug;
                        
                        if (isset($upload['status']) && $upload['status']) {
                            $review_upload_images[] = $upload['data']->id;
                        } else {
                            if ($is_return) {
                                return [
                                    'status' => 0,
                                    'message' => $upload['message'] ?? __('Cannot upload file'),
                                    'debug' => $debug
                                ];
                            }
                            return redirect()->back()->with('error', $upload['message'] ?? __('Cannot upload file'));
                        }
                    }
                } catch (\Exception $e) {
                    $debug['exception'] = $e->getMessage();
                    if ($is_return) {
                        return [
                            'status' => 0,
                            'message' => $e->getMessage(),
                            'debug' => $debug
                        ];
                    }
                    return redirect()->back()->with('error', $e->getMessage());
                }
            }
        }
        
        $review_id = $request['review_id'];

        $allServices = get_reviewable_services();

        if (empty($allServices[$service_type])) {
            if ($is_return) {
                return $this->sendError(__("Service type not found"));
            } else {
                return redirect()->to(url()->previous() . '#review-form')->with('error', __('Service type not found'));
            }
        }

        $module_class = $allServices[$service_type];
        $module = $module_class::find($service_id);
        if (empty($module)) {
            if ($is_return) {
                return $this->sendError(__("Service not found"));
            } else {
                return redirect()->to(url()->previous() . '#review-form')->with('error', __('Service not found'));
            }
        }
        $reviewEnable = $module->getReviewEnable();
        if (!$reviewEnable) {
            if ($is_return) {
                return $this->sendError(__("Review not enable"));
            } else {
                return redirect()->to(url()->previous() . '#review-form')->with('error', __('Review not enable'));
            }
        }

        if (ReCaptchaEngine::isEnable() and setting_item("review_enable_recaptcha")) {
            $codeCapcha = $request->input('g-recaptcha-response');
            if (!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)) {
                if ($is_return) {
                    return $this->sendError(__("Please verify the captcha"));
                } else {
                    return redirect()->to(url()->previous() . '#review-form')->with('error', __('Please verify the captcha'));
                }
            }
        }

        if ($module->review_after_booking()) {
            if (!$module->count_remain_review()) {
                if ($is_return) {
                    return $this->sendError(__("You need to make a booking or the Orders must be confirmed before writing a review"));
                } else {
                    return redirect()->to(url()->previous() . '#review-form')->with('error', __('You need to make a booking or the Orders must be confirmed before writing a review'));
                }
            }
        }

        if ($module->author_id == Auth::id()) {
            if ($is_return) {
                return $this->sendError(__("You cannot review your service"));
            } else {
                return redirect()->to(url()->previous() . '#review-form')->with('error', __('You cannot review your service'));
            }
        }

        $rules = [
            'review_content' => 'required|min:10'
        ];
        $messages = [
            'review_content.required' => __('Review Content is required field'),
            'review_content.min'      => __('Review Content has at least 10 character'),
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            if ($is_return) {
                return $this->sendError($validator->errors());
            } else {
                return redirect()->to(url()->previous() . '#review-form')->withErrors($validator->errors());
            }
        }

        if ($review_id > 0 and Auth::id() and setting_item("enable_edit_review",false)) {
            $review = Review::find($review_id);
            if (empty($review)) {
                $msg = __("Review not found");
                if ($is_return) {
                    return $this->sendSuccess($msg);
                } else {
                    return redirect()->to(url()->previous() . '#review-form')->with('error', $msg);
                }
            }
            if ($review->author_id != Auth::id()) {
                $msg = __("You don't have permission review");
                if ($is_return) {
                    return $this->sendError($msg);
                } else {
                    return redirect()->to(url()->previous() . '#review-form')->withErrors($msg);
                }
            }
        } else {
            $review = new Review();
        }

        $all_stats = setting_item($service_type . "_review_stats");
        $review_stats = $request->input('review_stats');
        $metaReview = [];
        if (!empty($all_stats)) {
            $all_stats = json_decode($all_stats, true);
            $total_point = 0;
            foreach ($all_stats as $key => $value) {
                if (isset($review_stats[$value['title']])) {
                    $total_point += $review_stats[$value['title']];
                }
                $metaReview[] = [
                    "object_id"    => $service_id,
                    "object_model" => $service_type,
                    "name"         => $value['title'],
                    "val"          => $review_stats[$value['title']] ?? 0,
                ];
            }
            $rate = round($total_point / count($all_stats), 1);
            if ($rate > 5) {
                $rate = 5;
            }
        } else {
            $rate = $request->input('review_rate');
        }
        if (setting_item('review_upload_picture') && !empty($review_upload_images)) {
            $metaReview[] = [
                "object_id"    => $service_id,
                "object_model" => $service_type,
                "name"         => 'upload_picture',
                "val"          => json_encode($review_upload_images)
            ];
        }

        $review->fill([
            "object_id"    => $service_id,
            "object_model" => $service_type,
            "content"      => $reviewContent,
            "rate_number"  => $rate ?? 0,
            "author_ip"    => $request->ip(),
            "status"       => !$module->getReviewApproved() ? "approved" : "pending",
            'vendor_id'    => $module->author_id,
            'author_id'    => Auth::id(),
        ]);

        if ($review->save()) {
            if (!empty($metaReview)) {
                foreach ($metaReview as $meta) {
                    $meta['review_id'] = $review->id;
                    $reviewMeta = new ReviewMeta($meta);
                    $reviewMeta->save();
                }
            }
            $images = $request->input('review_upload');
            if (is_array($images) and !empty($images)) {
                foreach ($images as $image) {
                    if (!$this->validateUploadImage($image)) continue;
                    $review->addMeta('review_image', $image, true);
                }
            }

            $msg = __('Review success!');
            if ($module->getReviewApproved()) {
                $msg = __("Review success! Please wait for admin approved!");
            }
            event(new CreateReviewEvent($module, $review));
            $module->update_service_rate();
            if ($is_return) {
                return [
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'review_upload_images' => $review_upload_images
                    ],
                    'debug' => $debug
                ];
            } else {
                return redirect()->to(url()->previous() . '#bravo-reviews')->with('success', $msg);
            }
        }
        if ($is_return) {
            return $this->sendError(__('Review error!'));
        } else {
            return redirect()->to(url()->previous() . '#review-form')->with('error', __('Review error!'));
        }
    }

    protected function validateUploadImage($image)
    {
        if(!$image) return true;
        
        // Fix for when $image is an array
        if (is_array($image)) {
            if (empty($image)) {
                return __('No file provided');
            }
            // Get the first item if it's an array
            $image = is_object($image[0]) ? $image[0] : null;
            
            // If we couldn't get a valid object, return error
            if (!is_object($image) || !method_exists($image, 'getClientOriginalExtension')) {
                return __('Invalid file format');
            }
        }

        $extension = strtolower($image->getClientOriginalExtension());
        
        // Allowed file types - now includes video formats
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedVideoTypes = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp'];
        $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);
        
        // Check file type
        if(!in_array($extension, $allowedTypes)) {
            return __('File type is not supported. Allowed types: ') . implode(', ', $allowedTypes);
        }
        
        // Check file size - larger limit for videos
        $maxSizeImage = 5 * 1024 * 1024; // 5MB for images
        $maxSizeVideo = 50 * 1024 * 1024; // 50MB for videos
        
        if(in_array($extension, $allowedImageTypes) && $image->getSize() > $maxSizeImage) {
            return __('Maximum image size allowed is 5MB');
        }
        
        if(in_array($extension, $allowedVideoTypes) && $image->getSize() > $maxSizeVideo) {
            return __('Maximum video size allowed is 50MB');
        }
        
        return true;
    }
}
