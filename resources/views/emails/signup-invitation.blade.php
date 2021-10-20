@component('mail::message')
# Inviation For Signup

You can signup by clicking the below button.

@component('mail::button', ['url' => $url])
Signup Now
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
