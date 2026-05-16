<#
.SYNOPSIS
    Smart AI Recruitment System - Project Runner (Windows)
.DESCRIPTION
    Usage:
        .\run.ps1 up      # Khoi chay toan bo du an
        .\run.ps1 down    # Tat du an
        .\run.ps1 build   # Build lai Docker images
        .\run.ps1 logs    # Xem log backend
        .\run.ps1 restart # Restart backend
        .\run.ps1 status  # Xem trang thai
#>

param(
    [Parameter(Position=0)]
    [ValidateSet("up","down","build","logs","restart","status")]
    [string]$Command = "up"
)

function Write-Step($msg) {
    Write-Host ""
    Write-Host "==> $msg" -ForegroundColor Cyan
}

switch ($Command) {
    "up" {
        Write-Step "Khoi dong Docker Compose (MySQL + Backend + AI Service)..."
        docker compose up -d --build 2>&1 | ForEach-Object {
            if ($_ -notmatch "level=warning") { Write-Host $_ }
        }

        Write-Host ""
        Write-Host "Dang cho backend khoi dong (5s)..." -ForegroundColor Yellow
        Start-Sleep -Seconds 5

        Write-Host ""
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "  HE THONG DA SAN SANG!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "  Backend:    http://localhost:8000" -ForegroundColor White
        Write-Host "  AI Service: http://localhost:8001" -ForegroundColor White
        Write-Host "  MySQL:      localhost:3307" -ForegroundColor White
        Write-Host ""
        Write-Host "  -> Mo trinh duyet: http://localhost:8000" -ForegroundColor Green
        Write-Host ""
        Write-Host "  Xem log:  .\run.ps1 logs" -ForegroundColor DarkGray
        Write-Host "  Tat:      .\run.ps1 down" -ForegroundColor DarkGray
        Write-Host ""

        # Start Vite frontend if exists
        if (Test-Path "frontend/package.json") {
            Write-Step "Khoi dong Vite Frontend..."
            Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PWD\frontend'; npm run dev"
        }
    }

    "down" {
        Write-Step "Tat Docker Compose..."
        docker compose down 2>&1 | ForEach-Object {
            if ($_ -notmatch "level=warning") { Write-Host $_ }
        }
        Write-Host "Da tat." -ForegroundColor Green
    }

    "build" {
        Write-Step "Build Docker images..."
        docker compose build 2>&1 | ForEach-Object {
            if ($_ -notmatch "level=warning") { Write-Host $_ }
        }
        Write-Host "Build xong." -ForegroundColor Green
    }

    "logs" {
        Write-Step "Log backend (Ctrl+C de thoat)..."
        docker compose logs backend --follow --tail=100
    }

    "restart" {
        Write-Step "Restart backend..."
        docker compose restart backend 2>&1 | ForEach-Object {
            if ($_ -notmatch "level=warning") { Write-Host $_ }
        }
        Write-Host "Backend da restart." -ForegroundColor Green
    }

    "status" {
        Write-Step "Trang thai containers:"
        docker compose ps -a 2>&1 | ForEach-Object {
            if ($_ -notmatch "level=warning") { Write-Host $_ }
        }
    }
}
