<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Password Reset Request</title>
    <!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
    <style>
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
        }

        body,
        .wrapper-bg {
            background-color: #eef1f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .container {
            max-width: 560px;
        }

        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
        }

        .btn:hover {
            background-color: #1d4ed8;
        }

        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .content-pad {
                padding: 28px 24px !important;
            }

            .header-pad {
                padding: 28px 24px !important;
            }

            .btn {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
            }
        }

        @media (prefers-color-scheme: dark) {
            .wrapper-bg {
                background-color: #0f172a !important;
            }

            .card-bg {
                background-color: #1e293b !important;
            }

            .heading-text {
                color: #f1f5f9 !important;
            }

            .body-text {
                color: #cbd5e1 !important;
            }

            .notice-bg {
                background-color: #0f172a !important;
                border-color: #334155 !important;
            }

            .notice-text {
                color: #94a3b8 !important;
            }

            .footer-text {
                color: #64748b !important;
            }

            .divider {
                border-color: #334155 !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#eef1f6;">

    <!-- Preheader (hidden preview text) -->
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#eef1f6;">
        Reset your Student Follow-up System password — this link expires in 60 minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="wrapper-bg">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" class="container card-bg" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(15,23,42,0.08);">

                    <!-- Header -->
                    <tr>
                        <td class="header-pad" align="center" style="background-color:#2563eb; background-image:linear-gradient(135deg,#2563eb,#1d4ed8); padding:36px 40px; color:#ffffff;">
                            <h1 style="margin:0; font-size:20px; font-weight:600; letter-spacing:0.2px; color:#ffffff;">Student Followup System</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="content-pad" style="padding:40px; color:#1e293b;">
                            <h2 class="heading-text" style="margin:0 0 16px; font-size:18px; font-weight:600; color:#0f172a;">
                                Hello {{ $user->name }},
                            </h2>

                            <p class="body-text" style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#475569;">
                                We received a request to reset the password for your Student Follow-up System account.
                                Click the button below to choose a new one.
                            </p>

                            <!-- Button (bulletproof for Outlook) -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 32px 0;">
                                        <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:48px;v-text-anchor:middle;width:220px;" arcsize="20%" strokecolor="#2563eb" fillcolor="#2563eb">
                    <w:anchorlock/>
                    <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:600;">Reset Password</center>
                    </v:roundrect>
                    <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $url }}" class="btn" target="_blank" rel="noopener">Reset Password</a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <div class="notice-bg notice-text" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-left:3px solid #2563eb; border-radius:8px; padding:14px 16px; font-size:13px; color:#64748b; margin:0 0 16px;">
                                ⏱ This link will expire in <strong>60 minutes</strong> for your security.
                            </div>

                            <hr class="divider" style="border:none; border-top:1px solid #e2e8f0; margin:32px 0 24px;">

                            <p class="footer-text" style="font-size:13px; color:#94a3b8; margin-bottom:8px; line-height:1.6;">
                                If you didn't request a password reset, you can safely ignore this email — your password will remain unchanged.
                            </p>

                            <p class="footer-text" style="font-size:12px; color:#94a3b8; margin:0; line-height:1.6; word-break:break-all;">
                                Or copy and paste this link into your browser:<br>
                                <a href="{{ $url }}" style="color:#2563eb;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Footer -->
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; width:100%;">
                    <tr>
                        <td align="center" class="footer-text" style="color:#94a3b8; padding:24px 20px; font-size:12px; line-height:1.6;">
                            © {{ date('Y') }} Student Follow-up System · All rights reserved
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>