<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password reset for {{ $appName }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2 style="color: #0d6efd; margin-bottom: .25rem;">Reset your {{ $appName }} password</h2>
    <p>Hello {{ $user->name }},</p>
    <p>
        We received a request to reset the password for the admin account
        <strong>{{ $user->email }}</strong>. Click the button below to choose a new password:
    </p>

    <p style="margin: 20px 0;">
        <a href="{{ $resetUrl }}"
           style="display:inline-block; padding: 10px 22px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Reset password
        </a>
    </p>

    <p style="color: #475569; font-size: 13px;">
        This link is valid for <strong>{{ $expireMinutes }} minute(s)</strong>. After that, you'll need to request a new reset link.
    </p>

    <p style="color: #475569; font-size: 13px;">
        If the button doesn't work, copy and paste this URL into your browser:<br>
        <span style="word-break: break-all;">{{ $resetUrl }}</span>
    </p>

    <p style="color: #b91c1c; font-size: 13px;">
        <strong>Didn't request this?</strong> You can safely ignore this email &mdash; your password will not change unless you click the link above.
    </p>

    <p style="color: #6b7280; font-size: 12px;">— {{ $appName }}</p>
</body>
</html>
