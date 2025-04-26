<?php

namespace Modules\Playlist\Models; // Hoặc namespace tương ứng

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\User\Models\User; // Đảm bảo đường dẫn User model đúng
use Illuminate\Database\Eloquent\Casts\Attribute; // Thêm dòng này để dùng Casts mới (Laravel 9+)

class Playlist extends Model
{
    use HasFactory;

    /**
     * Các thuộc tính có thể gán hàng loạt.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'metadata', // Thêm metadata vào fillable
    ];

    /**
     * Các thuộc tính nên được ép kiểu.
     * Giúp Laravel tự động encode/decode JSON khi đọc/ghi cột metadata.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array', // Hoặc 'object' nếu bạn muốn truy cập như $playlist->metadata->key
        // 'created_at' => 'datetime', // Không cần thiết nếu bạn không tùy chỉnh nhiều
        // 'updated_at' => 'datetime',
    ];

    /**
     * Lấy thông tin người dùng đã tạo playlist này.
     */
    public function user()
    {
        return $this->belongsTo(User::class); // Đảm bảo User::class đúng
    }

    /**
     * (Ví dụ) Phương thức để lấy danh sách track IDs từ metadata.
     * Bạn sẽ cần định nghĩa cấu trúc JSON trong metadata trước.
     * Ví dụ: metadata = {"track_ids": ["id1", "id2"], "cover_image": "url"}
     */
    public function getTrackIds(): array
    {
        // Đảm bảo metadata là array và có key 'track_ids'
        return $this->metadata['track_ids'] ?? [];
    }

     /**
     * (Ví dụ) Phương thức để thêm một track ID vào metadata.
     *
     * @param string|int $trackId ID của bài hát từ API nguồn khác
     * @return bool True nếu thêm thành công, False nếu đã tồn tại hoặc lỗi
     */
    public function addTrackId(string|int $trackId): bool
    {
        $meta = $this->metadata ?? []; // Lấy metadata hiện tại hoặc tạo array rỗng
        $trackIds = $meta['track_ids'] ?? []; // Lấy danh sách track_ids hoặc tạo array rỗng

        if (!in_array($trackId, $trackIds)) {
            $trackIds[] = $trackId;
            $meta['track_ids'] = $trackIds; // Cập nhật lại key track_ids
            $this->metadata = $meta; // Gán lại toàn bộ metadata đã cập nhật
            return $this->save(); // Lưu thay đổi vào database
        }
        return false; // Track ID đã tồn tại
    }

    /**
     * (Ví dụ) Phương thức để xóa một track ID khỏi metadata.
     *
     * @param string|int $trackId ID của bài hát cần xóa
     * @return bool True nếu xóa thành công, False nếu không tìm thấy hoặc lỗi
     */
    public function removeTrackId(string|int $trackId): bool
    {
        $meta = $this->metadata ?? [];
        $trackIds = $meta['track_ids'] ?? [];

        $initialCount = count($trackIds);
        $trackIds = array_filter($trackIds, fn($id) => $id != $trackId); // Lọc bỏ ID cần xóa

        if (count($trackIds) < $initialCount) { // Nếu số lượng giảm (đã xóa)
            $meta['track_ids'] = array_values($trackIds); // Cập nhật và re-index array
             // Nếu track_ids rỗng, bạn có thể xóa luôn key này khỏi metadata
            if (empty($meta['track_ids'])) {
                unset($meta['track_ids']);
            }
            $this->metadata = $meta;
            return $this->save();
        }
        return false; // Không tìm thấy track ID để xóa
    }

    // protected static function newFactory()
    // {
    //     // return \Modules\Playlist\Database\factories\PlaylistFactory::new(); // Nếu có Factory
    // }
}