<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Board Invitation</title>
    <style>
        body { margin:0; padding:0; width:100% !important; height:100% !important; }
        body, .wrapper-bg { background-color:#eef1f6; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .container { max-width:560px; }
        .btn { display:inline-block; background-color:#2563eb; color:#ffffff !important; text-decoration:none; padding:14px 36px; border-radius:10px; font-weight:600; font-size:15px; }
        .btn:hover { background-color:#1d4ed8; }
        @media only screen and (max-width:600px) {
            .container { width:100% !important; border-radius:0 !important; }
            .content-pad { padding:28px 24px !important; }
            .header-pad { padding:28px 24px !important; }
            .btn { display:block !important; width:100% !important; box-sizing:border-box; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef1f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="wrapper-bg">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" class="container" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(15,23,42,0.08);">
                    <tr>
                        <td class="header-pad" align="center" style="background-color:#2563eb; padding:36px 40px; color:#ffffff;">
                            <h1 style="margin:0; font-size:20px; font-weight:600; color:#ffffff;">Student Followup System</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content-pad" style="padding:40px; color:#1e293b;">
                            <h2 style="margin:0 0 16px; font-size:18px; font-weight:600; color:#0f172a;">You're Invited!</h2>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#475569;">
                                <strong>{{ $invitedBy->name }}</strong> has invited you to join the board
                                <strong>{{ $board->title }}</strong>.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:32px 0;">
                                        <a href="{{ $url }}" class="btn" target="_blank" rel="noopener">Accept Invitation</a>
                                    </td>
                                </tr>
                            </table>
                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:32px 0 24px;">
                            <p style="font-size:13px; color:#94a3b8; margin:0; line-height:1.6;">
                                If you didn't expect this invitation, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; width:100%;">
                    <tr>
                        <td align="center" style="color:#94a3b8; padding:24px 20px; font-size:12px; line-height:1.6;">
                            &copy; {{ date('Y') }} Student Follow-up System &middot; All rights reserved
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
