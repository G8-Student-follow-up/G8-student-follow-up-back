<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You were mentioned in a comment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2563eb;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .mention-info {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #2563eb;
            margin: 20px 0;
            border-radius: 4px;
        }
        .comment-box {
            background-color: #f1f5f9;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-style: italic;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>You were mentioned!</h1>
    </div>
    
    <div class="content">
        <p>Hello,</p>
        
        <p><strong>{{ $mentionedBy->name }}</strong> mentioned you in a comment on the card <strong>"{{ $card->title }}"</strong> in board <strong>"{{ $board->title }}"</strong>.</p>
        
        <div class="mention-info">
            <p><strong>Board:</strong> {{ $board->title }}</p>
            <p><strong>Card:</strong> {{ $card->title }}</p>
            <p><strong>Mentioned by:</strong> {{ $mentionedBy->name }}</p>
        </div>
        
        <div class="comment-box">
            <p><strong>Comment:</strong></p>
            <p>{{ $commentMessage }}</p>
        </div>
        
        <a href="{{ url('/app/boards/' . $board->id) }}" class="button">View Card</a>
        
        <p>Click the button above to view the card and respond to the comment.</p>
        
        <div class="footer">
            <p>This is an automated notification from {{ config('app.name') }}.</p>
            <p>You received this email because you were mentioned in a comment.</p>
        </div>
    </div>
</body>
</html>