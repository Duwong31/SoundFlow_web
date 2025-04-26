<?php

namespace Modules\User\Emails; // Adjust namespace if needed

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\User; // Or your User model namespace

class ResetPasswordOtpEmail extends Mailable implements ShouldQueue // Implement ShouldQueue for background sending
{
    use Queueable, SerializesModels;

    public $otp;
    public $user; // Optional: Pass user if needed in the template

    /**
     * Create a new message instance.
     *
     * @param string $otp
     * @param User $user
     * @return void
     */
    public function __construct(string $otp, User $user)
    {
        $this->otp = $otp;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Get subject and from address/name from config or set defaults
        $subject = config('app.name') . ' - Mã OTP Đặt lại mật khẩu'; // Or use translations __()
        $fromAddress = config('mail.from.address', 'noreply@example.com');
        $fromName = config('mail.from.name', config('app.name'));

        return $this->from($fromAddress, $fromName)
                    ->subject($subject)
                    ->markdown('emails.auth.reset_password_otp'); // Reference the Blade view
                    // ->with([ // Data passed to the view
                    //     'otp' => $this->otp,
                    //     'userName' => $this->user->getDisplayName(), // Example user data
                    //     'appName' => config('app.name'),
                    // ]);
                    // Simpler alternative using public properties:
                    // The view can directly access $otp and $user public properties
    }
}