$server_ip = "160.191.237.64"
$user = "root"
$zipFile = "cvmatcher-deploy.zip"
$excludeList = @('.git', 'node_modules', 'vendor', 'storage/logs', '.env.local', 'postgres_data', '.idea', 'brain', 'scratch', '.tempmediaStorage', '.gemini')

Write-Host "1. Dang nen du an..." -ForegroundColor Cyan
# Zip the folder excluding unnecessary big directories
Compress-Archive -Path "backend", "ai-service", "docker-compose.yml", "docker" -DestinationPath $zipFile -Force
# Note: Compress-Archive doesn't easily exclude specific subdirectories in a neat way if we just give it folders, 
# but for our structure, passing specific folders is clean enough. Wait, 'backend/vendor' is inside 'backend'.
