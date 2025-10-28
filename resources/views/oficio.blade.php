<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $oficio_num }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; margin: 40px; }
        h1 { text-align: center; font-size: 16pt; margin-bottom: 30px; }
        p { text-align: justify; line-height: 1.6; }
        .dados { margin-bottom: 30px; }
        .dados strong { display: inline-block; width: 100px; }
    </style>
</head>
<body>
    <h1>{{ $oficio_num }}</h1>
    <div class="dados">
        <p><strong>Rodovia:</strong> {{ $rodovia }}</p>
        <p><strong>Data:</strong> {{ $data_oficio }}</p>
        <p><strong>Assunto:</strong> {{ $assunto }}</p>
    </div>
    <p>{!! nl2br(e($texto_oficio)) !!}</p>
</body>
</html>
