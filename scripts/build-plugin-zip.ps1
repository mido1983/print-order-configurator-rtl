param(
	[string] $Version = ''
)

$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Split-Path -Parent $scriptDir
$slug = 'print-order-configurator-rtl'
$distDir = Join-Path $repoRoot 'dist'
$buildRoot = Join-Path ([System.IO.Path]::GetTempPath()) ($slug + '-build-' + [System.Guid]::NewGuid().ToString('N'))
$pluginRoot = Join-Path $buildRoot $slug
$zipPath = Join-Path $distDir ($slug + '.zip')

$includeItems = @(
	'assets',
	'docs',
	'includes',
	'languages',
	'templates',
	'CHANGELOG.md',
	'LICENSE',
	'print-order-configurator-rtl.php',
	'README.md',
	'readme.txt',
	'SECURITY.md',
	'uninstall.php'
)

New-Item -ItemType Directory -Path $pluginRoot -Force | Out-Null
New-Item -ItemType Directory -Path $distDir -Force | Out-Null

foreach ($item in $includeItems) {
	$source = Join-Path $repoRoot $item

	if (Test-Path -LiteralPath $source) {
		Copy-Item -LiteralPath $source -Destination $pluginRoot -Recurse -Force
	}
}

$excludedPatterns = @(
	'.git',
	'.github',
	'node_modules',
	'.DS_Store',
	'*.zip'
)

foreach ($pattern in $excludedPatterns) {
	Get-ChildItem -LiteralPath $pluginRoot -Recurse -Force -Filter $pattern -ErrorAction SilentlyContinue |
		Remove-Item -Recurse -Force
}

Remove-Item -LiteralPath $zipPath -Force -ErrorAction SilentlyContinue
Compress-Archive -LiteralPath $pluginRoot -DestinationPath $zipPath -Force

if ($Version -ne '') {
	$versionedZipPath = Join-Path $distDir ($slug + '-v' + $Version + '.zip')
	Remove-Item -LiteralPath $versionedZipPath -Force -ErrorAction SilentlyContinue
	Copy-Item -LiteralPath $zipPath -Destination $versionedZipPath -Force
}

Remove-Item -LiteralPath $buildRoot -Recurse -Force

Write-Host "Built $zipPath"
