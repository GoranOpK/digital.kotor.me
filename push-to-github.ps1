# PowerShell skripta za push na GitHub
# Pokreni ovu skriptu u PowerShell terminalu

Set-Location $PSScriptRoot

Write-Host "=== Proveravam git status ===" -ForegroundColor Cyan
git status

Write-Host "`n=== Proveravam da .env nije u staging area ===" -ForegroundColor Cyan
$envInStaging = git diff --cached --name-only | Select-String -Pattern "\.env$"
if ($envInStaging) {
    Write-Host "WARNING: .env je u staging area! Uklanjam..." -ForegroundColor Yellow
    git reset HEAD .env
}

Write-Host "`n=== Dodajem sve promene ===" -ForegroundColor Cyan
git add .

Write-Host "`n=== Finalni status pre commit-a ===" -ForegroundColor Cyan
git status

Write-Host "`n=== Commit-ujem promene ===" -ForegroundColor Cyan
$commitMessage = @"
Add browser-side MEGA.nz upload integration using megajs

- Add cloud_path column to user_documents table
- Add browser-side MEGA upload using megajs library
- Add getMegaSession and storeMegaMetadata endpoints
- Modify DocumentController download to redirect to MEGA links
- Add mega-upload.js for client-side upload handling
- Update package.json with megajs dependency
- Remove server-side MEGA API implementation (MegaStorageService)
- Remove tuyenlaptrinh/php-mega-nz from composer.json
- Add MEGA configuration to config/services.php
- Add browser-side upload documentation
"@

git commit -m $commitMessage

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=== Push-ujem na GitHub ===" -ForegroundColor Cyan
    
    # Proveri da li je glavna grana 'main' ili 'master'
    $currentBranch = git branch --show-current
    Write-Host "Trenutna grana: $currentBranch" -ForegroundColor Yellow
    
    git push origin $currentBranch
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n=== ✅ Uspešno push-ovano na GitHub! ===" -ForegroundColor Green
    } else {
        Write-Host "`n=== ❌ Greška pri push-u ===" -ForegroundColor Red
    }
} else {
    Write-Host "`n=== ❌ Greška pri commit-u ===" -ForegroundColor Red
}
