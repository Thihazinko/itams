<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your {{ $appName }} account</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2 style="color: #0d6efd; margin-bottom: .25rem;">Welcome to {{ $appName }}</h2>
    <p>Hello {{ $user->name }},</p>
    <p>An account has been created for you. You can sign in with the credentials below:</p>

    <table cellpadding="10" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ccc; margin: 12px 0;">
        <tr>
            <td style="background:#f8fafc;"><strong>Email</strong></td>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <td style="background:#f8fafc;"><strong>Temporary Password</strong></td>
            <td><code style="font-size: 14px;">{{ $plainPassword }}</code></td>
        </tr>
        <tr>
            <td style="background:#f8fafc;"><strong>Role</strong></td>
            <td>{{ ucfirst($user->role) }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ $loginUrl }}"
           style="display:inline-block; padding: 10px 18px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px;">
            Sign in to {{ $appName }}
        </a>
    </p>

    <p style="color: #b91c1c;">
        <strong>Security tip:</strong> please sign in and change your password as soon as possible.
        Do not share this email with anyone.
    </p>

    <p style="color: #6b7280; font-size: 12px;">— {{ $appName }}</p>
</body>
</html>
