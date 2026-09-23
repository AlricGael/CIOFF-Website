# Génère maquette/assets/adherents.js à partir des données de l'extension WordPress
# (wp-content/plugins/cioff-core/data/adherents.json et includes/departements.php).
# Usage : powershell -ExecutionPolicy Bypass -File maquette\generer-donnees.ps1

$racine = Split-Path -Parent $PSScriptRoot
$plugin = Join-Path $racine 'wp-content\plugins\cioff-core'
$utf8   = New-Object Text.UTF8Encoding $false

# Départements : '29' => array( 'Finistère', $bre ),
$php = [IO.File]::ReadAllText((Join-Path $plugin 'includes\departements.php'), $utf8)
$regions = @{}
foreach ($m in [regex]::Matches($php, '\$(\w+)\s*=\s*(["''])(.+?)\2;')) { $regions[$m.Groups[1].Value] = $m.Groups[3].Value }
$depts = @{}
foreach ($m in [regex]::Matches($php, "'(\w+)'\s*=>\s*array\(\s*([`"'])(.+?)\2,\s*\`$(\w+)\s*\)")) {
	$depts[$m.Groups[1].Value] = @($m.Groups[3].Value, $regions[$m.Groups[4].Value])
}

$json = [IO.File]::ReadAllText((Join-Path $plugin 'data\adherents.json'), $utf8) | ConvertFrom-Json
$liste = @()
$i = 0
foreach ($a in $json.adherents) {
	$i++
	$d = $depts[[string]$a.dept]
	$premier = ($a.description -split "`n")[0]
	if ($premier.Length -gt 160) { $premier = $premier.Substring(0, 157).TrimEnd() + '…' }
	$liste += [ordered]@{
		id      = $i
		nom     = $a.nom
		type    = $a.type
		ville   = $a.ville
		dept    = [string]$a.dept
		deptNom = $(if ($d) { $d[0] } else { '' })
		region  = $(if ($d) { $d[1] } else { '' })
		lat     = $a.lat
		lng     = $a.lng
		url     = 'fiche.html?id=' + $i
		image   = ''
		extrait = $premier
		dates   = $a.dates
		site    = $a.site
		email   = $a.email
		description = $a.description
	}
}
$sortie = "/* Généré par generer-donnees.ps1 – ne pas modifier à la main. Source : cioff-france.org */`nwindow.CIOFF_ADHERENTS = " + ($liste | ConvertTo-Json -Depth 4 -Compress) + ";`n"
[IO.File]::WriteAllText((Join-Path $PSScriptRoot 'assets\adherents.js'), $sortie, $utf8)
"$($liste.Count) adhérents écrits dans maquette/assets/adherents.js"
