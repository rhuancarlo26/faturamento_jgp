<p>
O DAV do empreendimento 
<strong>{{ $dav->empreendimento->cod_emp }}</strong>
teve alteração no status.
</p>

<p>
<b>Produto:</b> {{ $dav->produto }} <br>
<b>Status:</b> {{ $dav->status }}
</p>

@if($dav->status === 'Reprovado')

<p>
<b>Motivo da reprovação:</b><br>
{{ $dav->motivo_reprovacao }}
</p>
@endif

<p>
Acesse o DAV pelo portal SEGAC:
<a href="https://redesegac.com/" target="_blank">https://redesegac.com/</a>
</p>
