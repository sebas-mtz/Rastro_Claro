$ErrorActionPreference = 'Stop'
$auditRoot = Split-Path -Parent $PSScriptRoot
$auditEnvironment = @{
    APP_ENV = 'testing'
    APP_CONFIG_CACHE = Join-Path $auditRoot ('storage/framework/cache/audit-' + [guid]::NewGuid().ToString('N') + '.php')
    DB_CONNECTION = 'sqlite'
    DB_DATABASE = ':memory:'
    DB_URL = 'null'
    CACHE_STORE = 'array'
    SESSION_DRIVER = 'array'
    QUEUE_CONNECTION = 'sync'
    MAIL_MAILER = 'array'
    CONTROL_PARTOS_SEED_EMAIL = 'demo@rastroclaro.test'
}
$auditPreviousEnvironment = @{}

Push-Location -LiteralPath $auditRoot
try {
    foreach ($auditName in $auditEnvironment.Keys) {
        $auditPreviousEnvironment[$auditName] = [Environment]::GetEnvironmentVariable($auditName, 'Process')
        [Environment]::SetEnvironmentVariable($auditName, $auditEnvironment[$auditName], 'Process')
    }

    # Both processes use a disposable SQLite database in memory.
    & php artisan test
    if ($LASTEXITCODE -ne 0) {
        throw 'php artisan test failed.'
    }

    & php artisan migrate:fresh --seed --no-interaction
    if ($LASTEXITCODE -ne 0) {
        throw 'php artisan migrate:fresh --seed failed.'
    }
} finally {
    foreach ($auditName in $auditPreviousEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($auditName, $auditPreviousEnvironment[$auditName], 'Process')
    }
    Pop-Location
}
