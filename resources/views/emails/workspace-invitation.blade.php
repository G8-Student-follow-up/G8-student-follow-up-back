<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Workspace Invitation</title>
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
            background-color: #f4f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .container {
            max-width: 520px;
        }
        .logo-badge {
            display: inline-block;
            width: 44px;
            height: 44px;
            background: #7c3aed;
            border-radius: 12px;
            text-align: center;
            line-height: 44px;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .btn {
            display: inline-block;
            background: #7c3aed;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 48px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.2px;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        }
        .btn:hover {
            background: #6d28d9;
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.45);
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                border-radius: 0 !important;
            }
            .content-pad {
                padding: 32px 28px !important;
            }
            .logo-area {
                padding-top: 32px !important;
            }
            .btn {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                text-align: center !important;
            }
        }
        @media (prefers-color-scheme: dark) {
            .wrapper-bg {
                background-color: #0b1120 !important;
            }
            .card-bg {
                background-color: #111827 !important;
            }
            .heading-text {
                color: #f1f5f9 !important;
            }
            .body-text {
                color: #cbd5e1 !important;
            }
            .card-section-bg {
                background-color: #0d1321 !important;
                border-color: #1e293b !important;
            }
            .card-section-text {
                color: #cbd5e1 !important;
            }
            .badge-bg {
                background-color: #0d1321 !important;
                border-color: #4c1d95 !important;
            }
            .badge-text {
                color: #cbd5e1 !important;
            }
            .footer-text {
                color: #475569 !important;
            }
            .divider {
                border-color: #1e293b !important;
            }
            .muted-link {
                color: #a78bfa !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f4f5f7;">

    <!-- Preheader -->
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f4f5f7;">
        {{ $invitedBy->name }} has invited you to join "{{ $workspace->name }}"
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="wrapper-bg">
        <tr>
            <td align="center" style="padding: 48px 24px;">

                <table role="presentation" width="520" cellpadding="0" cellspacing="0" border="0" class="container card-bg" style="max-width:520px; width:100%; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04),0 8px 32px rgba(0,0,0,0.06);">

                    <!-- Top accent line -->
                    <tr>
                        <td style="background:#7c3aed;height:4px;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- Logo + Title -->
                    <tr>
                        <td class="logo-area" align="center" style="padding:44px 48px 0 48px;">
                            <span class="logo-badge" style="display:inline-block;width:44px;height:44px;background:#7c3aed;border-radius:12px;text-align:center;line-height:44px;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">SFS</span>
                            <h1 class="heading-text" style="margin:20px 0 0;font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-0.3px;">You're invited</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="content-pad" style="padding:24px 48px 40px 48px;">

                            <p class="body-text" style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                                <strong style="color:#0f172a;">{{ $invitedBy->name }}</strong> has invited you to collaborate in the workspace:
                            </p>

                            <!-- Workspace name badge -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
                                <tr>
                                    <td class="badge-bg badge-text" align="center" style="padding:20px 24px;background:#f8fafc;border:1px solid #e8ecf1;border-radius:12px;font-size:16px;font-weight:600;color:#0f172a;letter-spacing:-0.2px;">
                                        <span style="display:inline-block;width:8px;height:8px;background:#7c3aed;border-radius:50%;margin-right:10px;vertical-align:middle;"></span>
                                        {{ $workspace->name }}
                                    </td>
                                </tr>
                            </table>

                            <p class="body-text" style="margin:0 0 32px;font-size:15px;line-height:1.7;color:#475569;">
                                Accept the invitation to start collaborating with your team on this workspace.
                            </p>

                            <!-- Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:0 0 36px 0;">
                                        <!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $acceptUrl }}" style="height:50px;v-text-anchor:middle;width:240px;" arcsize="60%" strokecolor="#7c3aed" fillcolor="#7c3aed">
<w:anchorlock/>
<center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:600;">Accept Invitation</center>
</v:roundrect>
<![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $acceptUrl }}" class="btn" target="_blank" rel="noopener">Accept Invitation</a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <hr class="divider" style="border:none;border-top:1px solid #e8ecf1;margin:0 0 20px;">

                            <p class="footer-text" style="font-size:12px;color:#94a3b8;margin:0;line-height:1.6;word-break:break-all;">
                                If you didn't expect this invitation, you can safely ignore this email.<br><br>
                                Or open directly:
                                <a class="muted-link" href="{{ $acceptUrl }}" style="color:#7c3aed;text-decoration:underline;">{{ $acceptUrl }}</a>
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- Footer -->
                <table role="presentation" width="520" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;width:100%;">
                    <tr>
                        <td align="center" class="footer-text" style="color:#94a3b8;padding:28px 24px;font-size:12px;line-height:1.6;">
                            Student Follow-up System
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>
