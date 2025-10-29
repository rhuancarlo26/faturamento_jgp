<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $oficio_num }}</title>
    <style>
        @page {
            margin: 2.5cm 2.5cm 2cm 2.5cm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header img {
            height: 80px;
            margin-bottom: 10px;
        }

        .oficio-num {
            text-align: right;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .date {
            text-align: right;
            margin-bottom: 30px;
        }

        .subject {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .body-text {
            text-align: justify;
            white-space: pre-line; /* preserva quebras de linha */
        }

        .signature {
            margin-top: 80px;
            text-align: center;
            font-weight: bold;
        }

        .signature span {
            display: block;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ public_path('images/logo.jpg') }}" alt="Logo">
        <div>JGP CONSULTORIA E PARTICIPAÇÕES LTDA</div>
    </div>

    <div class="oficio-num">
        {{ $oficio_num }}
    </div>

    <div class="date">
        {{ $data_oficio }}
    </div>

    <div class="subject">
        Assunto: {{ $assunto }}
    </div>

    <div class="body-text">
        {!! nl2br(e($texto_oficio)) !!}
    </div>

    <div class="signature">
        <span>______________________________________</span>
        <span>Responsável Técnico</span>
        <span>JGP Consultoria</span>
    </div>

</body>
</html>
