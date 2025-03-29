<?php

namespace App\Notifications;

use App\Events\PusherNotificationPrivateEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PrivateChannelServices extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;
    public $id;

    /**
     * PrivateChannelServices constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->id = Str::uuid();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        event(new PusherNotificationPrivateEvent($this->id, $this->data, $notifiable));
        return [
            'id' => $this->id,
            'notification' => $this->data,
        ];
    }
}
