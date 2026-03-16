# Simple push script for Windows (PowerShell)
$msg = $args[0]
if (-not $msg) {
    $msg = "Update from Trae Assistant"
}

git add .
git commit -m "$msg"
git push
Write-Host "Changes pushed successfully with message: $msg" -ForegroundColor Green
