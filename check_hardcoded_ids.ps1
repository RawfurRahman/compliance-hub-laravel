#!/usr/bin/env powershell

$foundHardcodes = @()

# Check DashboardController.php for hardcoded =1; patterns
$dashboardControllerPath = "D:/compliance-hub-laravel/app/Http/Controllers/DashboardController.php"
$dashboardContent = Get-Content $dashboardControllerPath -Raw

# Check for projectId=1 pattern (the broken one we're fixing)
if ($dashboardContent -match '\`\$projectId = 1;') {
    $foundHardcodes += "DashboardController.php: Found broken pattern: `$projectId = 1;"
}

# Check for any remaining =1; patterns in DashboardController (excluding known safe ones)
$lines = $dashboardContent -split '\n'
for ($i = 0; $i -lt $lines.Count; $i++) {
    $line = $lines[$i].Trim()
    if ($line -match '^\s*\$projectId = 1;' -or $line -match '^\s*\$id = 1;') {
        $foundHardcodes += "DashboardController.php: Line $($i+1): Found hardcoded ID pattern: $line"
    }
    # Also check for any "= 1; // Replace" patterns (these are the bug)
    if ($line -match '= 1;\s*// Replace') {
        $foundHardcodes += "DashboardController.php: Line $($i+1): Found placeholder pattern: $line"
    }
}

# Check all controllers for hardcoded =1; patterns
$controllersPath = "D:/compliance-hub-laravel/app/Http/Controllers"
$controllers = Get-ChildItem -Path $controllersPath -Filter "*.php" -Recurse

foreach ($controller in $controllers) {
    $controllerContent = Get-Content $controller.FullName -Raw
    if ($controllerContent -match '^\s*\$projectId = 1;' -or $controllerContent -match '^\s*\$id = 1;') {
        $matches = Select-String -Path $controller.FullName -Pattern '^\s*\$projectId = 1;|^\s*\$id = 1;'
        foreach ($match in $matches) {
            $foundHardcodes += "$($controller.FullName): Line $($match.LineNumber): Found hardcoded ID pattern: $($match.Line)"
        }
    }
}

# Report results
if ($foundHardcodes.Count -gt 0) {
    "=== HARDCODED ID PATTERNS FOUND ==="
    foreach ($item in $foundHardcodes) {
        Write-Host $item
    }
    exit 1
} else {
    Write-Host "✓ No hardcoded ID patterns found. All fixes are good."
    exit 0
}
