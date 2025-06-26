<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Post Published</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your post has been published!</h1>
        </div>
        
        <p>Hi {{ $author }},</p>
        
        <p>Your blog post, <strong>"{{ $post->title }}"</strong>, has been successfully published.</p>
        
        <p>You can view it here:</p>
        
        <a href="{{ $postUrl }}" class="button">View Post</a>
        
        <div class="footer">
            <p>Thanks,<br>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html> 