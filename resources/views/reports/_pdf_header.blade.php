@php
    $brandName = $appBrandName ?? config('app.name', 'Tele-Fleet');
    $orgName = $appOrgName ?: (config('app.org_name') ?: 'Lagos Island State Administration');
    $orgAddress = $appOrgAddress ?: (config('app.org_address') ?: '17B, Awolowo Road, Ikoyi, Lagos');
    $logoFile = config('app.brand_logo_file');
    $logoUrl = config('app.brand_logo_url');
    $gdEnabled = extension_loaded('gd');

    $logoSrc = null;
    if (is_string($logoFile) && $logoFile !== '' && is_file($logoFile) && is_readable($logoFile)) {
        $mime = @mime_content_type($logoFile);
        if (! is_string($mime) || $mime === '') {
            $mime = 'image/png';
        }
        $data = base64_encode((string) file_get_contents($logoFile));
        if ($data !== '') {
            // Data URI is the most reliable for Dompdf (no file/remote access needed).
            $logoSrc = "data:{$mime};base64,{$data}";
        }
    }

    if (! $logoSrc && is_string($logoUrl) && $logoUrl !== '') {
        $logoSrc = $logoUrl;
    }
@endphp

<div style="text-align:center; margin: 0 0 18px 0;">
    @if ($gdEnabled && is_string($logoSrc) && $logoSrc !== '')
        <img src="{{ $logoSrc }}" alt="{{ $brandName }} logo" style="height: 90px; width: auto; max-width: 92%; object-fit: contain;">
    @else
        <div style="font-size: 22px; font-weight: 700; margin-bottom: 6px;">{{ $brandName }}</div>
    @endif
    <div style="font-size: 14px; font-weight: 700; letter-spacing: 0.6px;">{{ strtoupper($orgName) }}</div>
    <div style="font-size: 12px; margin-top: 2px;">{{ $orgAddress }}</div>
</div>
<div style="border-bottom: 1px solid #e5e7eb; margin-bottom: 16px;"></div>
