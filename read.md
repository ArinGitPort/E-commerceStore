forgot-password.php
register.php 

requires mail/code

composer require google/apiclient


# Make sure you're in the right directory
if (Test-Path "composer.json") {
    Write-Host "✅ Found composer.json"

    # Remove vendor and composer.lock
    if (Test-Path "vendor") {
        Remove-Item -Recurse -Force "vendor"
        Write-Host "🗑️ Removed vendor folder"
    }

    if (Test-Path "composer.lock") {
        Remove-Item "composer.lock"
        Write-Host "🗑️ Removed composer.lock file"
    }

    # Clear Composer cache
    composer clear-cache
    Write-Host "🧹 Cleared Composer cache"

    # Reinstall everything
    composer install
    Write-Host "📦 Reinstalled dependencies"

    # Just in case PHPMailer still isn't there, force require it
    composer require phpmailer/phpmailer
    Write-Host "📬 PHPMailer should now be installed!"
} else {
    Write-Host "❌ composer.json not found in this directory."
}

# get_new_orders PLEASE FIX
# ADD FUNCTION TO COMPLETE BUTTON in order_management.php FOR REPORT GENERATION
# ADD AUDIt LOGS
# ADD SALES GRAPH
# DASHBOARD INFO



