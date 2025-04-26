@component('mail::message')
# Đặt lại mật khẩu {{ config('app.name') }}

Xin chào {{ $user->getDisplayName() }},

Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình.

Mã OTP của bạn là: **{{ $otp }}**

Mã OTP này sẽ hết hạn sau 10 phút.

Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.

Trân trọng,<br>
{{ config('app.name') }}

{{-- Optional Footer --}}
@slot('subcopy')
Nếu bạn gặp sự cố khi sử dụng mã OTP, vui lòng liên hệ bộ phận hỗ trợ.
@endslot
@endcomponent