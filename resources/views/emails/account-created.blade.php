{{-- Welcome email for a new account (App\Notifications\AccountCreated).

     Email clients are a different world from the app: tables and inline
     styles only, no Tailwind. The logo is attached inline ($message->embed)
     rather than linked, because the app lives on a private address the
     recipient's mail client can't reach. $message is absent when the view is
     rendered outside a send (tests, previews), so fall back to a URL there. --}}
@php
    $logo = isset($message) ? $message->embed(public_path('logo.png')) : asset('logo.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your {{ config('app.name') }} account</title>
</head>
<body style="margin:0;padding:0;background:#F3F2EE;font-family:Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F2EE;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#FBFAF7;border:1px solid #E5E7EB;border-radius:16px;">
                    <tr>
                        <td style="padding:28px 28px 0 28px;">
                            <img src="{{ $logo }}" alt="{{ config('app.name') }}" width="40" height="40" style="display:block;border:0;width:40px;height:40px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 0 28px;font-size:16px;line-height:1.5;">
                            <p style="margin:0 0 12px 0;font-size:18px;font-weight:600;">Hi {{ $name }},</p>
                            <p style="margin:0 0 12px 0;">
                                {{ $createdBy ? $createdBy.' made you an account on '.config('app.name').'.' : 'An account was made for you on '.config('app.name').'.' }}
                                <br>Your login is <strong>{{ $email }}</strong>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 4px 28px;">
                            <a href="{{ $setPasswordUrl }}" style="display:inline-block;background:#1F5AE0;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 22px;border-radius:10px;">Set your password</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 28px 28px;font-size:13px;line-height:1.5;color:#6B7280;">
                            This link works for {{ $minutes }} minutes.
                            Already have a password from the owner? <a href="{{ $loginUrl }}" style="color:#1F5AE0;">Sign in</a>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
