$ErrorActionPreference = 'Stop'

function Invoke-Check {
    param([Parameter(Mandatory = $true)][string[]] $Command)
    $executable = $Command[0]
    $arguments = $Command[1..($Command.Length - 1)]
    & $executable @arguments
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

$composeFile = if ($env:BITACORA_COMPOSE_FILE) { $env:BITACORA_COMPOSE_FILE } else { 'docker-compose.yml' }
$composePrefix = @('docker', 'compose')
if ($env:BITACORA_COMPOSE_ENV_FILE) {
    $composePrefix += @('--env-file', $env:BITACORA_COMPOSE_ENV_FILE)
}
$composePrefix += @('-f', $composeFile)

Invoke-Check ($composePrefix + @('config', '--quiet'))

if ($composeFile -eq 'docker-compose.prod.yml') {
    Invoke-Check ($composePrefix + @('build', 'app', 'nginx'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'sh', '-lc', "find public database scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l"))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'php', 'tests/run.php'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'php', 'tests/section_mail_privacy_test.php'))
    Invoke-Check ($composePrefix + @('run', '--rm', 'app', 'sh', '-lc', 'php database/migrate.php && php tests/integration.php'))
} else {
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'composer', 'install', '--no-dev', '--prefer-dist', '--no-interaction', '--optimize-autoloader', '--no-scripts'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'sh', '-lc', "find public database scripts tests -name '*.php' -print0 | xargs -0 -n1 php -l"))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'php', 'tests/run.php'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'php', 'tests/section_mail_privacy_test.php'))
    Invoke-Check ($composePrefix + @('run', '--rm', 'app', 'sh', '-lc', 'php database/migrate.php && php tests/integration.php'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'composer', 'validate', '--strict'))
    Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'app', 'composer', 'audit'))
}

Invoke-Check ($composePrefix + @('run', '--rm', '--no-deps', 'nginx', 'nginx', '-t'))
