$ErrorActionPreference = 'Stop'
$base = 'https://bilohash.com/hosting'
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest -Uri "$base/login.php" -WebSession $sess -UseBasicParsing
$csrf = if ($lp.Content -match 'name="csrf"\s+value="([^"]+)"') { $Matches[1] } else { '' }
Invoke-WebRequest -Uri "$base/login.php" -Method POST -Body @{ login = 'demo'; password = 'demo'; csrf = $csrf } -WebSession $sess -UseBasicParsing | Out-Null

$dash = Invoke-WebRequest -Uri "$base/panel/?lang=uk" -WebSession $sess -UseBasicParsing
$build = Invoke-WebRequest -Uri "$base/panel/landing-builder.php?lang=uk" -WebSession $sess -UseBasicParsing
Write-Host ("dashboard: HTTP {0}" -f $dash.StatusCode)
Write-Host ("  checklist: {0}" -f $(if ($dash.Content -match 'hp-launch-card') { 'OK' } else { 'MISSING' }))
Write-Host ("  launch-list: {0}" -f $(if ($dash.Content -match 'hp-launch-list') { 'OK' } else { 'MISSING' }))
Write-Host ("builder: HTTP {0}" -f $build.StatusCode)
Write-Host ("  preview-frame: {0}" -f $(if ($build.Content -match 'landing-preview-frame') { 'OK' } else { 'MISSING' }))
Write-Host ("  landing-builder.js: {0}" -f $(if ($build.Content -match 'landing-builder.js') { 'OK' } else { 'MISSING' }))
@('HS_LANDING_CFG', 'data-landing-blocks', 'data-landing-block-palette', 'blocks_json', 'data-elb-builder', 'elb-topbar', 'data-elb-device', 'data-elb-navigator', 'landing-elementor.css', 'elb-dock', 'data-landing-accordions', 'data-landing-spoiler', 'elb-spoiler', 'hs-landing-focus-page', 'data-elb-tips', 'data-elb-tips-open', 'data-elb-widget-search', 'data-elb-canvas-hint', 'data-elb-templates', 'pageTemplates', 'fieldHints') | ForEach-Object {
    Write-Host ("  {0}: {1}" -f $_, $(if ($build.Content -match [regex]::Escape($_)) { 'OK' } else { 'MISSING' }))
}
$js = Invoke-WebRequest -Uri "$base/assets/js/landing-builder.js" -WebSession $sess -UseBasicParsing
@('data-drag-handle', 'moveBlock', 'syncBeforeSubmit', 'wrapBlockSection', 'hs-elb-pick', 'highlightPreviewBlock', 'resizePreviewFrame', 'previewViewportWidth', 'data-gal-pick', 'paletteFilter', 'data-elb-widget-search', 'frame.srcdoc', 'applyPageTemplate', 'renderTestimonials', 'pageTemplates', 'data-elb-templates') | ForEach-Object {
    Write-Host ("  js {0}: {1}" -f $_, $(if ($js.Content -match [regex]::Escape($_)) { 'OK' } else { 'MISSING' }))
}
$galApi = Invoke-WebRequest -Uri "$base/panel/landing-gallery-api.php?action=list" -WebSession $sess -UseBasicParsing
Write-Host ("  gallery-api: HTTP {0}" -f $galApi.StatusCode)
Write-Host ("  gallery-api-json: {0}" -f $(if ($galApi.Content -match '"ok"\s*:\s*true') { 'OK' } else { 'MISSING' }))