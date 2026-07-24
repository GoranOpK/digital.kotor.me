Add-Type -AssemblyName System.Drawing

$outDir = Join-Path $PSScriptRoot "..\public\img\kalendar-kulture\categories"
$outDir = [System.IO.Path]::GetFullPath($outDir)
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$categories = @(
    @{ File = "koncerti.jpg"; Label = "Koncerti"; Color = "#1F4E79" },
    @{ File = "predstave.jpg"; Label = "Predstave"; Color = "#7B2D3B" },
    @{ File = "izlozbe.jpg"; Label = "Izlozbe"; Color = "#4A6741" },
    @{ File = "sportski-dogadjaji.jpg"; Label = "Sportski dogadjaji"; Color = "#0F5C4C" },
    @{ File = "knjizevne-veceri.jpg"; Label = "Knjizevne veceri"; Color = "#5C4A3A" },
    @{ File = "filmske-projekcije.jpg"; Label = "Filmske projekcije"; Color = "#2C2C54" },
    @{ File = "radionice.jpg"; Label = "Radionice"; Color = "#8A5A2B" },
    @{ File = "promocije-publikacija.jpg"; Label = "Promocije publikacija"; Color = "#3E5C76" },
    @{ File = "performansi.jpg"; Label = "Performansi"; Color = "#6B2D5B" },
    @{ File = "filmski-festivali.jpg"; Label = "Filmski festivali"; Color = "#1A1A2E" },
    @{ File = "likovne-manifestacije.jpg"; Label = "Likovne manifestacije"; Color = "#8B4513" },
    @{ File = "prezentacije.jpg"; Label = "Prezentacije"; Color = "#2F4F4F" },
    @{ File = "paneli-o-kulturi.jpg"; Label = "Paneli o kulturi"; Color = "#4B3F72" },
    @{ File = "manifestacije-mjesne-zajednice.jpg"; Label = "Mjesne zajednice"; Color = "#2E5A3C" },
    @{ File = "manifestacije-nvu.jpg"; Label = "NVU"; Color = "#5A3A2E" }
)

function ConvertFrom-HexColor([string]$hex) {
    $hex = $hex.TrimStart('#')
    $r = [Convert]::ToInt32($hex.Substring(0,2), 16)
    $g = [Convert]::ToInt32($hex.Substring(2,2), 16)
    $b = [Convert]::ToInt32($hex.Substring(4,2), 16)
    return [System.Drawing.Color]::FromArgb(255, $r, $g, $b)
}

foreach ($cat in $categories) {
    $bmp = New-Object System.Drawing.Bitmap 1200, 800
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

    $base = ConvertFrom-HexColor $cat.Color
    $g.Clear($base)

    $panelBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(70, 0, 0, 0))
    $g.FillRectangle($panelBrush, 80, 520, 1040, 180)

    $titleFont = New-Object System.Drawing.Font "Segoe UI", 42, ([System.Drawing.FontStyle]::Bold)
    $subFont = New-Object System.Drawing.Font "Segoe UI", 20, ([System.Drawing.FontStyle]::Regular)
    $white = [System.Drawing.Brushes]::White
    $g.DrawString("Kalendar kulture", $subFont, $white, 110, 545)
    $g.DrawString($cat.Label, $titleFont, $white, 110, 590)

    $path = Join-Path $outDir $cat.File
    $bmp.Save($path, [System.Drawing.Imaging.ImageFormat]::Jpeg)
    $g.Dispose(); $bmp.Dispose(); $panelBrush.Dispose(); $titleFont.Dispose(); $subFont.Dispose()
    Write-Output "created $path"
}

$fallback = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot "..\public\img\kalendar-kulture-default-event.png"))
if (-not (Test-Path $fallback)) {
    $bmp = New-Object System.Drawing.Bitmap 1200, 800
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.Clear((ConvertFrom-HexColor "#2F3E46"))
    $font = New-Object System.Drawing.Font "Segoe UI", 36, ([System.Drawing.FontStyle]::Bold)
    $g.DrawString("Kalendar kulture", $font, [System.Drawing.Brushes]::White, 110, 340)
    $bmp.Save($fallback, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose(); $bmp.Dispose(); $font.Dispose()
    Write-Output "created $fallback"
}

Get-ChildItem $outDir | ForEach-Object { "$($_.Name) $($_.Length)" }
