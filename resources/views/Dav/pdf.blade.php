<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DAV</title>

<style>
    @page {
        margin: 90px 35px 50px 35px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #333;
    }

    /* ================= HEADER FIXO ================= */

    .pdf-header {
        position: fixed;
        top: -75px;
        left: 0;
        right: 0;
        height: 70px;
        text-align: center;
    }

    .logo-left {
        position: absolute;
        left: 25px;   /* aumenta = vai pra direita */
        top: 20px;     /* aumenta = desce */
    }

    .logo-right {
        position: absolute;
        right: 25px;  /* aumenta = vai pra esquerda */
        top: 20px;     /* aumenta = desce */
    }

    .titulo {
        text-align: center;
        font-weight: bold;
        font-size: 17px;
        margin-top: 23px;
        margin-bottom: 40px;
        font-family: Arial, sans-serif;
        font-size: 16px;
    }

    .linha-topo {
        width: 100%;
        border-top: 1px solid #000;
        margin: 5px 0 8px 0;
        margin-top: -4px;
    }

    /* ================= SECTIONS ================= */

    .section {
        margin-bottom: 18px;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #fefeff;
        page-break-inside: avoid;

        font-family: Arial, sans-serif;
        font-size: 11px;
    }

    .section-title {
        font-weight: bold;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 10px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 4px;
        color: #021850;
    }

    .diarias-box {
        margin-top: 12px;
        text-align: center;
        font-size: 10px;
        font-family: Arial, sans-serif;
    }

    /* ================= GRIDS ================= */

    .grid-2,
    .grid-3,
    .grid-4 {
        width: 100%;
        border-collapse: collapse;
    }

    .grid-2 td,
    .grid-3 td,
    .grid-4 td {
        padding: 4px 6px 4px 0;
        vertical-align: top;
    }

    .grid-2 td { width: 50%; }
    .grid-3 td { width: 33.33%; }
    .grid-4 td { width: 25%; }

    /* ================= LABELS ================= */

    .label {
        font-size: 10px;
        text-transform: uppercase;
        color: #666;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .value {
        margin-top: 2px;
        font-size: 12px;
    }

    /* ================= TRECHOS ================= */

    .trecho-box {
        border: 1px solid #ddd;
        padding: 6px 8px;
        margin-bottom: 6px;
        background: #fff;
        page-break-inside: avoid;
    }

    .trecho-box strong {
        font-size: 11px;
    }

    /* ================= ASSINATURA ================= */

    .assinatura {
        margin-top: 45px;
        text-align: center;
    }

    .linha-assinatura {
        border-top: 1px solid #000;
        width: 220px;
        margin: 0 auto 5px;
    }

    .data-emissao {
        margin-top: 12px;
        text-align: right;
        font-size: 11px;
    }


</style>

</head>
<body>

<div class="pdf-header">
    <div class="logo-left">
        <img src="{{ public_path('images/DNIT.jpg') }}" height="35">
    </div>

    <div class="logo-right">
        <img src="{{ public_path('images/logo.jpg') }}" height="25">
    </div>
</div>

<div class="linha-topo"></div>

<div class="titulo">
    Documento de Autorização de Viagem - DAV
</div>

<div class="section">
    <table class="grid-2">
        <tr>
            <td>
                <strong>Contrato:</strong> 094/2022
            </td>
            <td>
                <strong>Coordenador:</strong> {{ $dav->coordenador }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Empreendimento:</strong> {{ $dav->empreendimento->cod_emp }}
            </td>
            <td>
                <strong>Nº OSE:</strong> {{ $dav->n_ose }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Produto:</strong> {{ $dav->produto }}
            </td>
            <td>
                <strong>Subproduto:</strong> {{ $dav->subproduto }}
            </td>
        </tr>
    </table>
</div>

@foreach($dav->profissionais as $prof)
<div class="section" style="page-break-inside: avoid;">
    <div class="section-title">Profissional</div>

    <table class="grid-3">
        <tr>
            <td>
                <strong>Nome:</strong> 
                {{ $prof->profissional->nome ?? '-' }}
            </td>
            <td>
                <strong>Formação:</strong> 
                {{ $prof->profissional->formacao ?? '-' }}
            </td>
            <td>
                <strong>Função:</strong> 
                {{ $prof->funcao }}
            </td>
            <tr>
                <td>
                    <strong>Período:</strong> 
                    {{ \Carbon\Carbon::parse($prof->data_ini)->format('d/m/Y') }}
                    até
                    {{ \Carbon\Carbon::parse($prof->data_fim)->format('d/m/Y') }}
                </td>
                <td>
                    <strong>Diárias:</strong> 
                    {{ $prof->diarias ?? 0 }}
                </td>
            </tr>
        </tr>
    </table>

    @if($prof->trechos->count())
        <div style="margin-top:20px;">
            <div class="label" style="margin-bottom:10px;">
                Trechos da Viagem
            </div>

            @foreach($prof->trechos as $trecho)
                <div class="trecho-box" style="page-break-inside: avoid;">

                    <table width="100%">
                        <tr>
                            <td width="50%">
                                <strong>Origem:</strong> {{ $trecho->origem }}
                            </td>
                            <td width="50%">
                                <strong>Destino:</strong> {{ $trecho->destino }}
                            </td>
                        </tr>
                    </table>
                    
                    {{-- TRANSPORTES POR TRECHO --}}
                    @if(
                        $trecho->aereo_qtd ||
                        $trecho->aquatico_qtd ||
                        $trecho->terrestre_pickup_qtd ||
                        $trecho->terrestre_hatch_qtd
                    )
                        <table class="grid-4" style="margin-top:10px;">
                            <tr>
                                @if($trecho->aereo_qtd)
                                    <td> • Aéreo: {{ $trecho->aereo_qtd }}</td>
                                @endif
                                @if($trecho->aquatico_qtd)
                                    <td> • Aquático: {{ $trecho->aquatico_qtd }}</td>
                                @endif
                                @if($trecho->terrestre_pickup_qtd)
                                    <td> • Pickup: {{ $trecho->terrestre_pickup_qtd }}</td>
                                @endif
                                @if($trecho->terrestre_hatch_qtd)
                                    <td> • Hatch: {{ $trecho->terrestre_hatch_qtd }}</td>
                                @endif
                            </tr>
                        </table>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

</div>
@endforeach

<div class="section" style="margin-top:25px;">
    <div class="section-title">
        Quadro resumo do DAV
    </div>

    <table width="100%" style="border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr style="background:#f0f2f5;">
                <th style="border:1px solid #ccc; padding:6px;"></th>

                <th style="border:1px solid #ccc; padding:6px; text-align:center;">
                    <div style="font-size:10px; font-weight:bold;">14.1.1</div>
                    <div style="font-size:10px;">Diárias</div>
                </th>

                <th style="border:1px solid #ccc; padding:6px; text-align:center;">
                    <div style="font-size:10px; font-weight:bold;">14.1.3</div>
                    <div style="font-size:10px;">Passagem Aérea</div>
                </th>

                <th style="border:1px solid #ccc; padding:6px; text-align:center;">
                    <div style="font-size:10px; font-weight:bold;">14.1.7</div>
                    <div style="font-size:10px;">Veículo Aquático</div>
                </th>

                <th style="border:1px solid #ccc; padding:6px; text-align:center;">
                    <div style="font-size:10px; font-weight:bold;">14.1.5</div>
                    <div style="font-size:10px;">Veículo Hatch</div>
                </th>

                <th style="border:1px solid #ccc; padding:6px; text-align:center;">
                    <div style="font-size:10px; font-weight:bold;">14.1.6</div>
                    <div style="font-size:10px;">Veículo Pickup</div>
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td style="border:1px solid #ccc; padding:6px; font-weight:bold;">
                    Solicitado
                </td>

                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $resumo['totais']['diarias'] }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $resumo['totais']['aereo'] }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $resumo['totais']['aquatico'] }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $resumo['totais']['hatch'] }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $resumo['totais']['pickup'] }}
                </td>
            </tr>

            <tr>
                <td style="border:1px solid #ccc; padding:6px; font-weight:bold;">
                    Saldo Restante
                </td>

                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $dav->diarias_total }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $dav->aereo_total }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $dav->aquatico_total }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $dav->hatch_total }}
                </td>
                <td style="border:1px solid #ccc; padding:6px; text-align:center;">
                    {{ $dav->pickup_total }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="section" style="margin-top:40px; border:none; background:#fff;">

    <table width="100%" style="margin-top:40px; font-family: Arial, sans-serif; font-size:11px;">
        <tr>
            <!-- ASSINATURA 1 -->
            <td width="50%" style="text-align:left; vertical-align:top; padding-right:40px;">

                {{-- IMAGEM DA ASSINATURA --}}
                <div style="margin-bottom:-20px;">
                    <img src="{{ public_path('images/assinatura-jose3.png') }}" 
                        style="height:45px;">
                </div>

                {{-- LINHA --}}
                <div style="border-top:1px solid #000; width:220px; margin-bottom:6px;"></div>

                <div style="font-weight:bold;">
                    José Carlos de Lima Pereira
                </div>
                <div>
                    Coordenador Geral
                </div>
                <div>
                    JGP Consultoria e Participações Ltda.
                </div>
                <div>
                    Contrato nº: 94/2022
                </div>

            </td>

             <!-- ASSINATURA 2 -->
            <td width="50%" style="text-align:left; vertical-align:top; padding-left:40px;">

                {{-- ESPAÇO RESERVADO PARA ASSINATURA FUTURA --}}
                <div style="height:45px; margin-bottom:-20px;"></div>

                {{-- LINHA --}}
                <div style="border-top:1px solid #000; width:220px; margin-bottom:6px;"></div>
                
                <div style="font-weight:bold;">
                    Douglas Freitas de Almeida Filho
                </div>
                <div>
                    Analista em Infraestrutura de Transportes - Eng. Civil
                </div>
                <div>
                    Fiscal do Contrato nº. 94/2022
                </div>

            </td>
        </tr>
    </table>

    <div style="margin-top:25px; text-align:center; font-size:10px; font-family: Arial, sans-serif;">
        <strong>Data de Emissão:</strong>
        {{ \Carbon\Carbon::parse($dav->created_at)->format('d/m/Y') }}
    </div>

</div>

</body>
</html>