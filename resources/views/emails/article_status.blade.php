<!DOCTYPE html>
<html>

<head>
    <title>{{ $subject }}</title>
</head>

<body>
    <h1>{{ $subject }}</h1>
    <p>Hi {{ $article->author_name }},</p>
    @if ($status === 'published')
    <p>Your article "{{ $article->title }}" has been published.</p>
    @elseif ($status === 'reject')
    <p>Your article "{{ $article->title }}" has been rejected.</p>
    @if (!empty($reason))
    <p>Reason for rejection: {{ $reason }}</p>
    @endif
    @endif
</body>

</html>