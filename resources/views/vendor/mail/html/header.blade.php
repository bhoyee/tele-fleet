<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $brandName = config('app.name', 'Tele-Fleet');
    $logoUrl = config('app.brand_logo_url');
@endphp
@if (is_string($logoUrl) && trim($logoUrl) !== '')
<img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" style="height: 48px; max-width: 260px; width: auto; object-fit: contain;">
@else
{{ $brandName }}
@endif
</a>
</td>
</tr>

