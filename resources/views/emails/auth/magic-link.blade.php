@component('mail::message')
# Login to Your Account

Click the button below to log in to your account. This link will expire in 30 minutes.

@component('mail::button', ['url' => route('auth.magic-login', $token)])
    Log In
@endcomponent

If you did not request this login link, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
