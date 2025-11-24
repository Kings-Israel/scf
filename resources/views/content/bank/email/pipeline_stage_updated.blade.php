<!DOCTYPE html>
<html>
<head>
    <title>Pipeline Stage Updated</title>
</head>
<body>
    <h1>Pipeline Stage Updated</h1>
    <p>The stage for the pipeline <strong>{{ $pipeline->name }}</strong> has been updated to <strong>{{ $newStage }}</strong>.</p>
    <p>Details:</p>
    <ul>
        <li>Name: {{ $pipeline->name }}</li>
        <li>Company: {{ $pipeline->company }}</li>
        <li>Stage: {{ $newStage }}</li>
        <li>Updated At: {{ now() }}</li>
    </ul>
</body>
</html>
