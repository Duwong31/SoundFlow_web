<?php
namespace Modules\Feedback\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Feedback\Models\Feedback;
use Modules\Media\Models\MediaFile;

class FeedbackController extends AdminController
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $this->checkPermission('feedback_view');
        
        $query = Feedback::query()->with(['user']);
        
        if (!empty($s = $request->input('s'))) {
            $query->where(function($query) use ($s) {
                $query->where('content', 'LIKE', '%' . $s . '%')
                      ->orWhereHas('user', function($query) use ($s) {
                          $query->where('name', 'LIKE', '%' . $s . '%')
                                ->orWhere('email', 'LIKE', '%' . $s . '%');
                      });
            });
        }
        
        $data = [
            'rows' => $query->orderBy('id', 'desc')->paginate(20),
            'statuses' => [
                'pending' => __('Pending'),
                'progress' => __('In Progress'),
                'resolved' => __('Resolved'),
                'rejected' => __('Rejected'),
                'completed' => __('Completed')
            ],
            'page_title' => __('Feedback Management'),
            'breadcrumbs' => [
                [
                    'name' => __('Feedbacks'),
                    'url' => route('feedback.admin.index')
                ],
                [
                    'name' => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Feedback::admin.index', $data);
    }

    public function view(Request $request, $id)
    {
        $this->setActiveMenu('admin/feedback');
        $this->checkPermission('feedback_view');
        
        $row = Feedback::with(['user', 'responder'])->find($id);
        
        if (empty($row)) {
            return redirect(route('feedback.admin.index'));
        }
        
        $mediaItems = [];
        if (!empty($row->media_ids)) {
            $mediaIds = json_decode($row->media_ids, true);
            foreach ($mediaIds as $mediaId) {
                $mediaFile = MediaFile::find($mediaId);
                if ($mediaFile) {
                    $mediaItems[] = [
                        'id' => $mediaId,
                        'url' => get_file_url($mediaId),
                        'name' => $mediaFile->file_name,
                        'type' => strtolower($mediaFile->file_extension) == 'mp4' ? 'video' : 'image',
                        'file_type' => $mediaFile->file_type,
                        'extension' => $mediaFile->file_extension
                    ];
                }
            }
        }
        
        $data = [
            'row' => $row,
            'media_items' => $mediaItems,
            'statuses' => [
                'pending' => __('Pending'),
                'progress' => __('In Progress'),
                'resolved' => __('Resolved'),
                'rejected' => __('Rejected'),
                'completed' => __('Completed')
            ],
            'breadcrumbs' => [
                [
                    'name' => __('Feedbacks'),
                    'url' => route('feedback.admin.index')
                ],
                [
                    'name' => __('View feedback: #:id', ['id' => $row->id]),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Feedback::admin.detail', $data);
    }

    public function bulkEdit(Request $request)
    {
        $this->setActiveMenu('admin/feedback');
        $this->checkPermission('feedback_update');
        
        $ids = $request->input('ids');
        $action = $request->input('action');
        
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', __('No items selected'));
        }
        
        if (empty($action)) {
            return redirect()->back()->with('error', __('Please select an action'));
        }
        
        if ($action == 'delete') {
            $this->checkPermission('feedback_delete');
            foreach ($ids as $id) {
                $query = Feedback::where("id", $id);
                $query->delete();
            }
        } else {
            foreach ($ids as $id) {
                $feedback = Feedback::find($id);
                if($feedback) {
                    $feedback->status = $action;
                    
                    if (in_array($action, ['resolved', 'rejected', 'progress']) && !$feedback->responded_by) {
                        $feedback->responded_by = Auth::id();
                        $feedback->responded_at = now();
                    }
                    
                    $feedback->save();
                }
            }
        }
        
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function response(Request $request, $id)
    {
        $this->setActiveMenu('admin/feedback');
        $this->checkPermission('feedback_update');
        
        $feedback = Feedback::find($id);
        
        if (!$feedback) {
            return redirect()->back()->with('error', __('Feedback not found'));
        }
        
        $feedback->admin_response = $request->input('admin_response');
        $feedback->status = $request->input('status');
        $feedback->responded_by = Auth::id();
        $feedback->responded_at = now();
        
        if ($feedback->save()) {
            return redirect()->back()->with('success', __('Response sent successfully'));
        }
        
        return redirect()->back()->with('error', __('Error responding to feedback'));
    }

    public function settings(Request $request)
    {
        $this->setActiveMenu('admin/feedback');
        $this->checkPermission('feedback_manage');
        
        $settings = setting_item_with_lang('feedback_settings', request()->query('lang'));
        
        if (!empty($settings)) {
            $settings = json_decode($settings, true);
        }
        
        $data = [
            'settings' => $settings,
            'breadcrumbs' => [
                [
                    'name' => __('Feedbacks'),
                    'url' => route('feedback.admin.index')
                ],
                [
                    'name' => __('Settings'),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Feedback::admin.settings', $data);
    }

    public function settingsStore(Request $request)
    {
        $this->setActiveMenu('admin/feedback');
        $this->checkPermission('feedback_manage');
        
        $data = $request->all();
        
        setting_update_item('feedback_settings', json_encode($data));
        
        return redirect()->back()->with('success', __('Settings updated'));
    }
} 