<?php
namespace Modules\Media\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Admin\MediaController;
use Modules\Media\Helpers\FileHelper;

class MediaFile extends BaseModel
{
    use SoftDeletes;
    protected $table = 'media_files';

    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'file_extension',
        'file_width',
        'file_height',
        'driver',
        'folder_id',
        'create_user',
        'update_user',
        'is_private'
    ];

    public static function findMediaByName($name)
    {
        return MediaFile::where("file_name", $name)->first();
    }

    public function cacheKey()
    {
        return sprintf("%s/%s", $this->getTable(), $this->getKey());
    }
    public function getThumbIcon(){
        if(preg_match("/image/i", $this->file_type)){
            return get_file_url($this->id);
        }else{
            return asset('images/file_icon.png');
        }
    }

    public function getEditPath(){
        return route('media.admin.edit.image', ['id' => $this->id]);
    }

    /**
     * Helper method to save uploaded media files (images and videos) for reviews
     * @param \Illuminate\Http\UploadedFile|array $file
     * @return array
     */
    public static function saveUploadedImage($file)
    {
        try {
            // Fix for when $file is an array
            if (is_array($file)) {
                if (empty($file)) {
                    return [
                        'status' => false,
                        'message' => 'No file provided'
                    ];
                }
                // Get the first item if it's an array
                $file = is_object($file[0]) ? $file[0] : $file;
            }

            // Ensure we have a valid file object
            if (!is_object($file) || !method_exists($file, 'getClientOriginalExtension')) {
                return [
                    'status' => false,
                    'message' => 'Invalid file format'
                ];
            }
            
            $driver = config('filesystems.default', 'public');
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Determine if this is a video or image
            $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', '3gp']);
            $fileType = $isVideo ? 'videos' : 'reviews';
            
            // Set upload path
            $uploadPath = $fileType . '/' . date('Y/m');
            $folderPath = public_path('uploads/' . $uploadPath);
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            
            // Generate unique filename
            $filename = Str::random(10) . '.' . $extension;
            $filePath = $uploadPath . '/' . $filename;
            
            // Store file
            if ($driver == 'public' || $driver == 'local') {
                $file->move($folderPath, $filename);
            } else {
                Storage::disk($driver)->put($filePath, file_get_contents($file));
            }
            
            // Get image dimensions if it's an image
            $width = $height = 0;
            if (!$isVideo && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                try {
                    $imageInfo = getimagesize($folderPath . '/' . $filename);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                } catch (\Exception $e) {
                    // Just continue without dimensions
                }
            }
            
            // Create media file record
            $media = new self();
            $media->file_name = $filename;
            $media->file_path = $filePath;
            $media->file_size = $file->getSize();
            $media->file_type = $file->getMimeType();
            $media->file_extension = $extension;
            $media->file_width = $width;
            $media->file_height = $height;
            $media->driver = $driver;
            $media->create_user = auth()->id() ?? 0;
            $media->save();
            
            return [
                'status' => true,
                'data' => $media,
                'is_video' => $isVideo
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editImage($image_data){
        $img = str_replace('data:image/jpeg;base64,', '', $image_data);
        $fileData = base64_decode($img);

        $storage = Storage::disk($this->driver);
        if(!$storage or !in_array($this->driver,['uploads']))
        {
            throw new \Exception(__("Can not edit non-local images"));
        }

        // Check Old file
        $ex_file = explode('/',$this->file_path);
        $fileName = array_pop($ex_file);
        $oldPath = implode('/',$ex_file).'/old/';
        if (!$storage->exists($oldPath)){
            $storage->makeDirectory($oldPath, 0775, true); //creates directory
        }

        // Move file to old
        if (!$storage->exists($oldPath.$fileName)){
            $storage->copy($this->file_path, $oldPath.$fileName);
        }

        // Put file
        $storage->put($this->file_path, $fileData,'public');

        // Clear thumb image
        $size_mores = FileHelper::list_size();
        if(!empty($size_mores)){
            foreach ($size_mores as $size){
                $file_size = substr($this->file_path, 0, strrpos($this->file_path, '.')) . '-' . $size . '.' . $this->file_extension;
                if($storage->exists($file_size)){
                    $storage->delete($file_size);
                }
            }
        }

        $result = [
            'src'     => get_file_url($this->id,'large'),
            'old'     =>  asset("uploads/".$oldPath.$fileName),
            'message' => __('Update Successful'),
            'status'=>0
        ];
        return $result;
    }

    public function scopeInFolder($query,$folder_id){
        return $query->where('folder_id',$folder_id);
    }

    public function forceDelete()
    {
        Cache::forget($this->cacheKey() . ':' . $this->id);
        return parent::forceDelete();
    }


    public function viewUrl(): Attribute
    {
        return Attribute::make(
            get:function($value){
                switch ($this->driver){
                    case "s3":
                    case "gcs":
                        return $this->generateUrl($this->file_path);
                        break;
                    default:
                        return asset('uploads/' . $this->file_path);
                        break;
                }
            }
        );
    }

    public function getViewUrl($size = 'thumb'){

        return config('bc.preview_media_link') ? url('media/preview/'.$this->id.'/'.$size) : get_file_url($this,$size);
    }

    /**
     * @param $file_path
     * @param float|int $mins Minutes, default 1 day
     * @return string
     */
    public function generateUrl($file_path,$mins = 24 * 60){
        return Storage::disk($this->driver)->temporaryUrl(
            $file_path, now()->addMinutes($mins)
        );
    }

    public function download($name = '',$headers = []){
        return Storage::disk($this->driver)->download($this->file_path,$name,$headers);
    }
}
