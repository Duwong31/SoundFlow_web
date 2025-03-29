<?php
namespace Modules\Feedback\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Api\Controllers\FeedbackController as ApiFeedbackController;

class FeedbackController extends Controller
{
    public function submit(Request $request)
    {
        $apiController = new ApiFeedbackController();
        return $apiController->submit($request);
    }
} 