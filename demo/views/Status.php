@model (array<string, string>)
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="10">
    <title>Radio</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem">
<h1>Radio</h1>
@if ($model === [])
    <p>Nothing on air. Run /radio in a voice channel.</p>
@else
    <ul>
        @foreach ($model as $guild => $station)
            <li>{{ $station }} <small>in guild {{ $guild }}</small></li>
        @endforeach
    </ul>
@endif
</body>
</html>
