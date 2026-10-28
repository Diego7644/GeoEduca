<#
  GEOEDUCA - Practica de Pruebas y Calidad de Software
  Ejecutor de pruebas MANUALES/API con evidencia. NO es una suite PHPUnit.
  Emite peticiones HTTP reales contra el backend (y el proxy de Vite) y guarda
  una evidencia JSON por caso en .\api\ mas un resumen.

  Requisitos:
    - Laravel en http://127.0.0.1:8000  (php artisan serve)
    - Vite en    http://localhost:5173   (npm run dev)
    - Variables de entorno con las contrasenas FICTICIAS de demostracion:
        GEO_DNI_STUDENT (DNI de la cuenta de estudiante y de padre de demostraciÃ³n), GEO_PW_STUDENT, GEO_PW_PARENT, GEO_PW_STAFF
      (no se guardan en la evidencia: se enmascaran como <MASKED>)

  Los tokens devueltos por el login se guardan solo en memoria y se
  reemplazan por <REDACTED> en cualquier archivo generado.

  Criterio de resultado:
    Aprobado   = el comportamiento real coincide con el esperado.
    Fallido    = la funcionalidad existe y su comportamiento real difiere del esperado.
    Bloqueado  = la funcionalidad no existe o no se pudo ejecutar; no es evaluable.
#>
param(
  [string]$Api = 'http://127.0.0.1:8000',
  [string]$Front = 'http://localhost:5173',
  [string]$BackendDir = (Join-Path $PSScriptRoot '..\..\backend'),
  [string]$OutDir = (Join-Path $PSScriptRoot 'api')
)

$ErrorActionPreference = 'Continue'
foreach ($v in 'GEO_DNI_STUDENT', 'GEO_PW_STUDENT', 'GEO_PW_PARENT', 'GEO_PW_STAFF') {
  if (-not [Environment]::GetEnvironmentVariable($v)) { throw "Falta la variable de entorno $v" }
}
$pw = @{ student = $env:GEO_PW_STUDENT; parent = $env:GEO_PW_PARENT; staff = $env:GEO_PW_STAFF }
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

Add-Type -AssemblyName System.Net.Http
$script:handler = New-Object System.Net.Http.HttpClientHandler
$script:handler.AllowAutoRedirect = $false
$script:http = New-Object System.Net.Http.HttpClient($script:handler)
$script:http.Timeout = [TimeSpan]::FromSeconds(20)
$script:results = New-Object System.Collections.ArrayList
$script:tok = @{}
$utf8 = New-Object System.Text.UTF8Encoding($false)

function Hide-Secrets([string]$s) {
  if (-not $s) { return $s }
  return [regex]::Replace($s, '"token"\s*:\s*"[^"]+"', '"token":"<REDACTED>"')
}

function Get-H($r, [string]$name) {
  foreach ($k in $r.Headers.Keys) { if ($k -ieq $name) { return $r.Headers[$k] } }
  return $null
}

function Call-Api {
  param(
    [string]$Method, [string]$Path, [hashtable]$Data = $null, [string]$Raw = $null,
    [hashtable]$Headers = $null, [string]$Base = $Api, [switch]$NoAccept
  )
  $url = if ($Path -match '^https?://') { $Path } else { $Base + $Path }
  $hdr = @{}
  if (-not $NoAccept) { $hdr['Accept'] = 'application/json' }
  if ($Headers) { foreach ($k in $Headers.Keys) { $hdr[$k] = $Headers[$k] } }
  $bodyText = $null; $bodyEv = $null
  if ($Data) {
    $bodyText = ($Data | ConvertTo-Json -Compress)
    $m = @{}
    foreach ($k in $Data.Keys) { if ($k -eq 'password') { $m[$k] = '<MASKED>' } else { $m[$k] = $Data[$k] } }
    $bodyEv = ($m | ConvertTo-Json -Compress)
  } elseif ($Raw) { $bodyText = $Raw; $bodyEv = $Raw }

  $req = New-Object System.Net.Http.HttpRequestMessage((New-Object System.Net.Http.HttpMethod($Method)), $url)
  foreach ($k in $hdr.Keys) { [void]$req.Headers.TryAddWithoutValidation($k, [string]$hdr[$k]) }
  if ($bodyText) { $req.Content = New-Object System.Net.Http.StringContent($bodyText, [Text.Encoding]::UTF8, 'application/json') }

  $sw = [Diagnostics.Stopwatch]::StartNew()
  $rh = @{}; $status = 0; $text = ''
  try {
    $resp = $script:http.SendAsync($req).GetAwaiter().GetResult()
    $text = $resp.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    foreach ($x in $resp.Headers) { $rh[$x.Key] = ($x.Value -join ',') }
    foreach ($x in $resp.Content.Headers) { $rh[$x.Key] = ($x.Value -join ',') }
    $status = [int]$resp.StatusCode
  } catch { $status = 0; $text = $_.Exception.Message }
  $sw.Stop()

  $json = $null
  try { if ($text) { $json = $text | ConvertFrom-Json } } catch { $json = $null }

  $hdrEv = @{}
  foreach ($k in $hdr.Keys) { if ($k -ieq 'Authorization') { $hdrEv[$k] = 'Bearer <REDACTED>' } else { $hdrEv[$k] = $hdr[$k] } }
  $rhEv = @{}
  foreach ($k in $rh.Keys) { if ($k -ine 'Set-Cookie') { $rhEv[$k] = $rh[$k] } }
  $shown = Hide-Secrets $text
  if ($shown.Length -gt 1500) { $shown = $shown.Substring(0, 1500) + '...[truncado]' }

  return [pscustomobject]@{
    Status = $status; Headers = $rh; Text = $text; Json = $json; Ms = $sw.ElapsedMilliseconds
    Evidence = [pscustomobject]@{
      metodo = $Method; url = $url; cabecerasEnviadas = $hdrEv; cuerpoEnviado = $bodyEv
      estado = $status; cabecerasRecibidas = $rhEv; cuerpoRecibido = $shown; ms = $sw.ElapsedMilliseconds
    }
  }
}

function Bearer($t) { return @{ Authorization = "Bearer $t" } }

function Judge($r, [bool]$cond) {
  if ($r.Status -eq 0) { return 'Bloqueado' }
  if ($cond) { return 'Aprobado' } else { return 'Fallido' }
}

function Add-Result {
  param($Id, $Func, $Type, $Pre, $Entrada, $Proc, $Expected, $Verdict, $Actual, $Calls)
  $rec = [pscustomobject]@{
    id = $Id; funcionalidad = $Func; tipo = $Type; precondiciones = $Pre; entrada = $Entrada
    procedimiento = $Proc; esperado = $Expected; resultado = $Verdict; real = $Actual
    ejecutado = (Get-Date).ToString('s'); peticiones = @($Calls | ForEach-Object { $_.Evidence })
  }
  [IO.File]::WriteAllText((Join-Path $OutDir "TC-$Id.json"), ($rec | ConvertTo-Json -Depth 8), $utf8)
  [void]$script:results.Add($rec)
  Write-Host ("{0,-6} {1,-10} {2}" -f $Id, $Verdict, $Actual)
}

$today = (Get-Date).ToString('yyyy-MM-dd')
$STU = 'DNI <DNI-DEMO-ESTUDIANTE> (ficticio), contraseña demo de estudiante <MASKED>, perfil student'
$PAR = 'DNI <DNI-DEMO-ESTUDIANTE> (ficticio), contraseña demo de padre <MASKED>, perfil parent'
$STF = 'DNI 40111222 (ficticio), contraseña demo de docente <MASKED>, perfil staff'
$PRE_API = 'Laravel en 127.0.0.1:8000 activo.'
$PRE_PX = 'Laravel en 127.0.0.1:8000 y Vite en localhost:5173 activos.'

# ---------------- FUNCIONALES ----------------
$r = Call-Api GET '/api/health'
Add-Result 'F-01' 'Endpoint de salud' 'Funcional' $PRE_API 'Ninguna' 'GET /api/health con Accept: application/json' '200 y cuerpo {"status":"ok"}' (Judge $r (($r.Status -eq 200) -and ($r.Json.status -eq 'ok'))) "HTTP $($r.Status) en $($r.Ms) ms; cuerpo: $(Hide-Secrets $r.Text)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.student; role = 'student' }
if ($r.Json.token) { $script:tok.student = $r.Json.token }
Add-Result 'F-02' 'Inicio de sesión de estudiante' 'Funcional' $PRE_API $STU 'POST /api/auth/login' '200, rol student, token presente y sin campo password en la respuesta' (Judge $r (($r.Status -eq 200) -and ($r.Json.user.role -eq 'student') -and [bool]$r.Json.token -and ($r.Text -notmatch '"password"'))) "HTTP $($r.Status); rol=$($r.Json.user.role); token presente=$([bool]$r.Json.token) (valor oculto)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.parent; role = 'parent' }
if ($r.Json.token) { $script:tok.parent = $r.Json.token }
Add-Result 'F-03' 'Inicio de sesión de padre/tutor' 'Funcional' $PRE_API $PAR 'POST /api/auth/login' '200, rol parent, token presente' (Judge $r (($r.Status -eq 200) -and ($r.Json.user.role -eq 'parent') -and [bool]$r.Json.token)) "HTTP $($r.Status); rol=$($r.Json.user.role); token presente=$([bool]$r.Json.token) (valor oculto)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = '40111222'; password = $pw.staff; role = 'staff' }
if ($r.Json.token) { $script:tok.staff = $r.Json.token }
Add-Result 'F-04' 'Inicio de sesión de personal (docente/auxiliar)' 'Funcional' $PRE_API $STF 'POST /api/auth/login' '200, rol staff, token presente' (Judge $r (($r.Status -eq 200) -and ($r.Json.user.role -eq 'staff') -and [bool]$r.Json.token)) "HTTP $($r.Status); rol=$($r.Json.user.role); token presente=$([bool]$r.Json.token) (valor oculto)" @($r)

$r = Call-Api POST '/api/asistencia/justificacion' -Headers (Bearer $script:tok.parent) -Data @{ type = 'medical'; event_date = $today; event_time = '11:30'; reason = 'Cita medica programada en clinica (dato ficticio)' }
Add-Result 'F-05' 'Envío de justificación (contrato de la API)' 'Funcional' 'Sesión de padre iniciada (token simulado).' "type=medical, event_date=$today, event_time=11:30, reason de 50 caracteres ficticio" 'POST /api/asistencia/justificacion con Authorization Bearer' '200 con mensaje de envío y eco de los datos validados (la persistencia se evalúa en B-04)' (Judge $r (($r.Status -eq 200) -and ($r.Json.data.type -eq 'medical') -and [bool]$r.Json.message)) "HTTP $($r.Status); mensaje: $($r.Json.message)" @($r)

$r = Call-Api POST '/api/asistencia/auxiliar/aprobar' -Headers (Bearer $script:tok.staff) -Data @{ student_id = '74829104'; reason = 'Bateria agotada (carnet fisico presentado)' }
Add-Result 'F-06' 'Aprobación manual de ingreso (contrato de la API)' 'Funcional' 'Sesión de personal iniciada (token simulado).' 'student_id=74829104 (ficticio), reason de 41 caracteres' 'POST /api/asistencia/auxiliar/aprobar con Authorization Bearer' '200 con mensaje y eco de los datos validados (la persistencia se evalúa en B-04)' (Judge $r (($r.Status -eq 200) -and ($r.Json.data.student_id -eq '74829104'))) "HTTP $($r.Status); mensaje: $($r.Json.message)" @($r)

# ---------------- VALIDACION DE DATOS ----------------
$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT.Substring(0,7); password = $pw.student; role = 'student' }
Add-Result 'V-01' 'Validación de DNI corto' 'Validación de datos' $PRE_API 'DNI de 7 dígitos (<DNI-DE-7-DIGITOS>), contraseña demo <MASKED>' 'POST /api/auth/login' '422 con error en el campo identifier' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.identifier)) "HTTP $($r.Status); errors.identifier=$($r.Json.errors.identifier)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = 'abc'; role = 'student' }
Add-Result 'V-02' 'Validación de contraseña corta' 'Validación de datos' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE>, contraseña de 3 caracteres' 'POST /api/auth/login' '422 con error en el campo password (mínimo 8)' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.password)) "HTTP $($r.Status); errors.password=$($r.Json.errors.password)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.student; role = 'admin' }
Add-Result 'V-03' 'Validación de perfil inexistente' 'Validación de datos' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE>, contraseña demo <MASKED>, role=admin' 'POST /api/auth/login' '422 con error en el campo role' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.role)) "HTTP $($r.Status); errors.role=$($r.Json.errors.role)" @($r)

$r = Call-Api POST '/api/auth/login' -Raw '{}'
$names = @(); if ($r.Json.errors) { $names = @($r.Json.errors.PSObject.Properties.Name) }
Add-Result 'V-04' 'Validación de formulario vacío' 'Validación de datos' $PRE_API 'Cuerpo JSON vacío {}' 'POST /api/auth/login' '422 con errores en identifier, password y role' (Judge $r (($r.Status -eq 422) -and ($names -contains 'identifier') -and ($names -contains 'password') -and ($names -contains 'role'))) "HTTP $($r.Status); campos con error: $($names -join ', ')" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = 'ABCDEFGH'; password = $pw.student; role = 'student' }
Add-Result 'V-05' 'Validación de DNI con letras' 'Validación de datos' $PRE_API 'DNI ABCDEFGH, contraseña demo <MASKED>' 'POST /api/auth/login' '422 con error en identifier' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.identifier)) "HTTP $($r.Status); errors.identifier=$($r.Json.errors.identifier)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = ('a' * 129); role = 'student' }
Add-Result 'V-06' 'Validación de contraseña demasiado larga' 'Validación de datos' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE>, contraseña de 129 caracteres' 'POST /api/auth/login' '422 con error en password (máximo 128)' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.password)) "HTTP $($r.Status); errors.password=$($r.Json.errors.password)" @($r)

$r = Call-Api POST '/api/asistencia/justificacion' -Headers (Bearer $script:tok.parent) -Data @{ type = 'medical'; event_date = $today; reason = 'corto' }
Add-Result 'V-07' 'Validación de motivo de justificación corto' 'Validación de datos' 'Sesión de padre iniciada (token simulado).' 'reason de 5 caracteres' 'POST /api/asistencia/justificacion' '422 con error en reason (mínimo 10)' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.reason)) "HTTP $($r.Status); errors.reason=$($r.Json.errors.reason)" @($r)

$r = Call-Api POST '/api/asistencia/justificacion' -Headers (Bearer $script:tok.parent) -Data @{ type = 'inexistente'; event_date = $today; reason = 'Motivo valido de prueba ficticio' }
Add-Result 'V-08' 'Validación de tipo de justificación inexistente' 'Validación de datos' 'Sesión de padre iniciada (token simulado).' 'type=inexistente' 'POST /api/asistencia/justificacion' '422 con error en type' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.type)) "HTTP $($r.Status); errors.type=$($r.Json.errors.type)" @($r)

$r = Call-Api POST '/api/asistencia/auxiliar/aprobar' -Headers (Bearer $script:tok.staff) -Data @{ student_id = '74829104' }
Add-Result 'V-09' 'Validación de aprobación manual sin motivo' 'Validación de datos' 'Sesión de personal iniciada (token simulado).' 'student_id=74829104, sin reason' 'POST /api/asistencia/auxiliar/aprobar' '422 con error en reason' (Judge $r (($r.Status -eq 422) -and [bool]$r.Json.errors.reason)) "HTTP $($r.Status); errors.reason=$($r.Json.errors.reason)" @($r)

# ---------------- FUNCIONALES NEGATIVOS ----------------
$r1 = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = 'Clave-incorrecta-01'; role = 'student' }
Add-Result 'N-01' 'Rechazo de contraseña incorrecta' 'Funcional (negativo)' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE>, contraseña incorrecta ficticia, perfil student' 'POST /api/auth/login' '401 con mensaje genérico y sin token' (Judge $r1 (($r1.Status -eq 401) -and -not $r1.Json.token)) "HTTP $($r1.Status); mensaje: $($r1.Json.message)" @($r1)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.parent; role = 'student' }
Add-Result 'N-02' 'Rechazo de credencial de otro perfil' 'Funcional (negativo)' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE> con la contraseña demo del PADRE <MASKED> pero perfil student' 'POST /api/auth/login' '401 y sin token (no se puede entrar a un perfil con la credencial de otro)' (Judge $r (($r.Status -eq 401) -and -not $r.Json.token)) "HTTP $($r.Status); mensaje: $($r.Json.message)" @($r)

$r = Call-Api POST '/api/auth/login' -Data @{ identifier = '99999999'; password = $pw.student; role = 'student' }
Add-Result 'N-03' 'No enumeración de usuarios en el login' 'Seguridad' $PRE_API 'DNI inexistente 99999999, contraseña demo <MASKED>' 'POST /api/auth/login y comparar el cuerpo con el de N-01' '401 con respuesta idéntica a la de contraseña incorrecta (no revela si el DNI existe)' (Judge $r (($r.Status -eq 401) -and ($r.Text -eq $r1.Text))) "HTTP $($r.Status); cuerpo idéntico al de N-01: $($r.Text -eq $r1.Text)" @($r)

# ---------------- INTEGRACION ----------------
$r = Call-Api GET "$Front/api/health"
Add-Result 'I-01' 'Proxy de Vite hacia la API' 'Integración' $PRE_PX 'Ninguna' 'GET http://localhost:5173/api/health' '200 {"status":"ok"} (Vite reenvía a Laravel)' (Judge $r (($r.Status -eq 200) -and ($r.Json.status -eq 'ok'))) "HTTP $($r.Status) en $($r.Ms) ms" @($r)

$r = Call-Api POST "$Front/api/auth/login" -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.student; role = 'student' }
Add-Result 'I-02' 'Login a través del proxy (frontend a backend)' 'Integración' $PRE_PX $STU 'POST http://localhost:5173/api/auth/login' '200 con token y usuario, igual que contra Laravel directo' (Judge $r (($r.Status -eq 200) -and ($r.Json.user.role -eq 'student') -and [bool]$r.Json.token)) "HTTP $($r.Status); rol=$($r.Json.user.role); token presente=$([bool]$r.Json.token) (valor oculto)" @($r)

$r = Call-Api OPTIONS '/api/auth/login' -Headers @{ Origin = 'http://localhost:5173'; 'Access-Control-Request-Method' = 'POST'; 'Access-Control-Request-Headers' = 'content-type' }
Add-Result 'I-03' 'CORS: origen permitido' 'Integración' $PRE_API 'Origin: http://localhost:5173' 'OPTIONS /api/auth/login (preflight)' '204 y Access-Control-Allow-Origin igual al origen' (Judge $r (($r.Status -eq 204) -and ((Get-H $r 'Access-Control-Allow-Origin') -eq 'http://localhost:5173'))) "HTTP $($r.Status); Allow-Origin='$(Get-H $r 'Access-Control-Allow-Origin')'" @($r)

$r = Call-Api OPTIONS '/api/auth/login' -Headers @{ Origin = 'http://evil.example'; 'Access-Control-Request-Method' = 'POST' }
Add-Result 'I-04' 'CORS: origen no autorizado' 'Seguridad' $PRE_API 'Origin: http://evil.example' 'OPTIONS /api/auth/login (preflight)' 'Sin cabecera Access-Control-Allow-Origin (el navegador bloquea al origen ajeno)' (Judge $r (-not (Get-H $r 'Access-Control-Allow-Origin'))) "HTTP $($r.Status); Allow-Origin='$(Get-H $r 'Access-Control-Allow-Origin')'" @($r)

$r = Call-Api POST '/api/auth/login' -NoAccept -Data @{ identifier = $env:GEO_DNI_STUDENT.Substring(0,7); password = $pw.student; role = 'student' }
Add-Result 'I-05' 'Robustez: cliente de API sin cabecera Accept' 'Integración' $PRE_API 'DNI inválido de 7 dígitos; sin Accept: application/json' 'POST /api/auth/login sin Accept (sin seguir redirecciones)' '422 con JSON de errores: una ruta de API no debe responder con redirección HTML' (Judge $r (($r.Status -eq 422))) "HTTP $($r.Status); Location='$(Get-H $r 'Location')'" @($r)

# ---------------- SEGURIDAD ----------------
$r = Call-Api GET '/api/auth/me'
Add-Result 'S-01' 'Perfil autenticado sin credenciales' 'Seguridad' $PRE_API 'Sin cabecera Authorization' 'GET /api/auth/me' 'Sistema seguro: 401 Unauthorized' (Judge $r ($r.Status -eq 401)) "HTTP $($r.Status); cuerpo: $(Hide-Secrets $r.Text)" @($r)

$r = Call-Api GET '/api/asistencia/auxiliar/casos'
Add-Result 'S-02' 'Casos del auxiliar sin autenticación' 'Seguridad' $PRE_API 'Sin cabecera Authorization' 'GET /api/asistencia/auxiliar/casos' 'Sistema seguro: 401 Unauthorized' (Judge $r ($r.Status -eq 401)) "HTTP $($r.Status); cuerpo: $(Hide-Secrets $r.Text)" @($r)

$r = Call-Api POST '/api/asistencia/auxiliar/aprobar' -Data @{ student_id = '74829104'; reason = 'Prueba de seguridad sin sesion (dato ficticio)' }
Add-Result 'S-03' 'Aprobación manual sin autenticación' 'Seguridad' $PRE_API 'Sin cabecera Authorization; payload válido' 'POST /api/asistencia/auxiliar/aprobar' 'Sistema seguro: 401 Unauthorized' (Judge $r ($r.Status -eq 401)) "HTTP $($r.Status); mensaje: $($r.Json.message)" @($r)

$r = Call-Api GET '/api/asistencia/estudiante' -Headers (Bearer 'token.falso.inventado')
Add-Result 'S-04' 'Rechazo de token falso' 'Seguridad' $PRE_API 'Authorization: Bearer con valor inventado' 'GET /api/asistencia/estudiante' 'Sistema seguro: 401 Unauthorized' (Judge $r ($r.Status -eq 401)) "HTTP $($r.Status); cuerpo: $(Hide-Secrets $r.Text)" @($r)

$r = Call-Api GET '/api/asistencia/auxiliar/casos' -Headers (Bearer $script:tok.student)
Add-Result 'S-05' 'Autorización por rol: estudiante en endpoint de personal' 'Seguridad' 'Sesión de estudiante iniciada (token simulado).' 'Token del estudiante en una ruta de auxiliar' 'GET /api/asistencia/auxiliar/casos con token de estudiante' 'Sistema seguro: 403 Forbidden' (Judge $r ($r.Status -eq 403)) "HTTP $($r.Status); cuerpo: $(Hide-Secrets $r.Text)" @($r)

$lg = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = $pw.student; role = 'student' }
$t = $lg.Json.token
$lo = Call-Api POST '/api/auth/logout' -Headers (Bearer $t)
$me = Call-Api GET '/api/auth/me' -Headers (Bearer $t)
Add-Result 'S-06' 'Invalidación del token al cerrar sesión' 'Seguridad' $PRE_API $STU '1) POST /api/auth/login  2) POST /api/auth/logout con el token  3) GET /api/auth/me con el mismo token' 'Sistema seguro: tras el logout, /me responde 401' (Judge $me ($me.Status -eq 401)) "login HTTP $($lg.Status); logout HTTP $($lo.Status); /me tras logout HTTP $($me.Status); cuerpo: $(Hide-Secrets $me.Text)" @($lg, $lo, $me)

$a = Call-Api POST '/api/auth/login' -Data @{ identifier = "<DNI-DEMO-ESTUDIANTE>' OR '1'='1"; password = $pw.student; role = 'student' }
$b = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = "' OR '1'='1' --"; role = 'student' }
$okSql = (@(401, 422) -contains $a.Status) -and (@(401, 422) -contains $b.Status) -and ($a.Text + $b.Text -notmatch 'SQLSTATE|syntax error|QueryException') -and -not $a.Json.token -and -not $b.Json.token
Add-Result 'S-07' 'Resistencia a inyección SQL en el login' 'Seguridad' $PRE_API "Identificador con payload SQL; contraseña con payload SQL" 'POST /api/auth/login (dos peticiones)' '401 o 422, sin token, sin error de base de datos ni 500' (Judge $a $okSql) "identificador: HTTP $($a.Status); contraseña: HTTP $($b.Status); sin trazas SQL: $($a.Text + $b.Text -notmatch 'SQLSTATE|QueryException')" @($a, $b)

$calls = @(); $codes = @()
for ($i = 1; $i -le 12; $i++) {
  $x = Call-Api POST '/api/auth/login' -Data @{ identifier = $env:GEO_DNI_STUDENT; password = "Intento-fallido-$i"; role = 'student' }
  $calls += $x; $codes += $x.Status
}
Add-Result 'S-08' 'Limitación de intentos de login (fuerza bruta)' 'Seguridad' $PRE_API 'DNI <DNI-DEMO-ESTUDIANTE> con 12 contraseñas incorrectas seguidas' '12 × POST /api/auth/login' 'Sistema seguro: algún intento recibe 429 Too Many Requests' (Judge $calls[0] ($codes -contains 429)) "Estados obtenidos: $($codes -join ', ')" $calls

$r = Call-Api GET '/api/health'
$xp = Get-H $r 'X-Powered-By'
Add-Result 'S-09' 'No divulgación de versión de tecnología' 'Seguridad' $PRE_API 'Ninguna' 'GET /api/health y revisar cabeceras de respuesta' 'Sin cabecera X-Powered-By' (Judge $r (-not $xp)) "X-Powered-By='$xp'" @($r)

$r = Call-Api GET '/api/health'
$xc = Get-H $r 'X-Content-Type-Options'
Add-Result 'S-10' 'Cabeceras de seguridad HTTP básicas' 'Seguridad' $PRE_API 'Ninguna' 'GET /api/health y revisar cabeceras de respuesta' 'Presente X-Content-Type-Options: nosniff' (Judge $r ($xc -eq 'nosniff')) "X-Content-Type-Options='$xc'; X-Frame-Options='$(Get-H $r 'X-Frame-Options')'; Strict-Transport-Security='$(Get-H $r 'Strict-Transport-Security')'" @($r)

$r = Call-Api GET '/api/ruta-que-no-existe-geoeduca'
$leak = ($r.Text -match '"trace"|"exception"|"file"|vendor')
Add-Result 'S-11' 'Los errores no filtran detalles internos' 'Seguridad' 'APP_DEBUG=true en backend/.env (entorno local).' 'Ruta inexistente' 'GET /api/ruta-que-no-existe-geoeduca' '404 con mensaje genérico, sin traza, rutas de archivo ni nombres de clases' (Judge $r (($r.Status -eq 404) -and -not $leak)) "HTTP $($r.Status); filtra trazas/rutas internas: $leak" @($r)

# ---------------- NO IMPLEMENTADOS / BLOQUEADOS ----------------
Push-Location $BackendDir
$routesRaw = & php artisan route:list --json --except-vendor 2>$null
Pop-Location
$routes = $routesRaw | ConvertFrom-Json
[IO.File]::WriteAllText((Join-Path $OutDir 'route-list.json'), (($routes | Select-Object method, uri, action) | ConvertTo-Json -Depth 4), $utf8)
$uris = @($routes | ForEach-Object { $_.uri })
function Probe($pattern) { return @($uris | Where-Object { $_ -match $pattern }) }
$routeEv = [pscustomobject]@{ Evidence = [pscustomobject]@{ metodo = 'CLI'; url = 'php artisan route:list --json --except-vendor'; estado = 0; cuerpoRecibido = ('Rutas registradas: ' + ($uris -join ', ')) } }

$m = Probe 'qr|desaf|token'
$v = if ($m.Count -eq 0) { 'Bloqueado' } else { 'Pendiente' }
Add-Result 'B-01' 'Emisión y validación de QR dinámico' 'Funcional (no implementado)' 'Sesión de estudiante y de auxiliar.' 'Ninguna' 'Buscar rutas de emisión/validación de QR en route:list' 'Existen endpoints para emitir un QR con expiración y validarlo una sola vez' $v "Rutas coincidentes: $($m.Count). Rutas totales: $($uris.Count). El QR de la pantalla es un SVG estático." @($routeEv)

$m = Probe 'geo|ubic|geocerca|gps'
$v = if ($m.Count -eq 0) { 'Bloqueado' } else { 'Pendiente' }
Add-Result 'B-02' 'Validación de geolocalización y geocerca' 'Funcional (no implementado)' 'Sede con geocerca configurada.' 'Ninguna' 'Buscar rutas de geolocalización en route:list' 'Existe un endpoint que valide coordenadas contra una geocerca en el servidor' $v "Rutas coincidentes: $($m.Count). Los textos de GPS de la interfaz son fijos." @($routeEv)

$m = Probe 'notific|aviso|whatsapp|alert'
$v = if ($m.Count -eq 0) { 'Bloqueado' } else { 'Pendiente' }
Add-Result 'B-03' 'Notificaciones a apoderados' 'Funcional (no implementado)' 'Apoderado vinculado a un estudiante.' 'Ninguna' 'Buscar rutas de notificaciones en route:list' 'Existen endpoints y mecanismo de envío con registro de intentos' $v "Rutas coincidentes: $($m.Count)." @($routeEv)

$post = Call-Api POST '/api/asistencia/justificacion' -Headers (Bearer $script:tok.parent) -Data @{ type = 'absence'; event_date = $today; reason = 'Justificacion para verificar persistencia (ficticia)' }
$get = Call-Api GET '/api/asistencia/padre' -Headers (Bearer $script:tok.parent)
$pend = ($get.Json.message -match 'Pendiente')
$found = ($get.Text -match 'verificar persistencia')
$v = if ($pend) { 'Bloqueado' } elseif ($found) { 'Aprobado' } else { 'Fallido' }
Add-Result 'B-04' 'Persistencia y consulta de justificaciones' 'Funcional (no implementado)' 'Sesión de padre iniciada (token simulado).' "type=absence, event_date=$today, motivo ficticio" '1) POST /api/asistencia/justificacion  2) GET /api/asistencia/padre' 'La justificación enviada aparece en la consulta del padre' $v "POST HTTP $($post.Status); GET HTTP $($get.Status); mensaje del GET: $($get.Json.message); justificación encontrada: $found" @($post, $get)

# ---------------- RESUMEN ----------------
$ap = @($results | Where-Object { $_.resultado -eq 'Aprobado' }).Count
$fa = @($results | Where-Object { $_.resultado -eq 'Fallido' }).Count
$bl = @($results | Where-Object { $_.resultado -in 'Bloqueado', 'Pendiente' }).Count
$ex = $ap + $fa
$pct = if ($ex -gt 0) { [math]::Round(100 * $ap / $ex, 1) } else { 0 }
$info = [pscustomobject]@{
  fecha = (Get-Date).ToString('s'); api = $Api; frontend = $Front
  commit = (git -C $BackendDir rev-parse --short HEAD); rama = (git -C $BackendDir branch --show-current)
  php = ((php -r 'echo PHP_VERSION;')); node = (node -v)
  total = $results.Count; aprobados = $ap; fallidos = $fa; bloqueados = $bl; ejecutados = $ex; porcentajeAprobacion = $pct
}
[IO.File]::WriteAllText((Join-Path $OutDir 'resumen-api.json'), ($info | ConvertTo-Json), $utf8)
[IO.File]::WriteAllText((Join-Path $OutDir 'resultados-api.json'), ($results | Select-Object id, funcionalidad, tipo, resultado, real | ConvertTo-Json), $utf8)

function ConvertTo-MdCell([string]$s) { if ($null -eq $s) { return '' }; return (($s -replace '\|', '\|') -replace "`r?`n", ' ') }
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine('| ID | Funcionalidad | Tipo | Precondiciones | Entrada ficticia | Procedimiento | Resultado esperado | Resultado real | Estado | Evidencia |')
[void]$sb.AppendLine('|---|---|---|---|---|---|---|---|---|---|')
foreach ($x in $results) {
  [void]$sb.AppendLine(('| {0} | {1} | {2} | {3} | {4} | {5} | {6} | {7} | **{8}** | `api/TC-{0}.json` |' -f $x.id, (ConvertTo-MdCell $x.funcionalidad), (ConvertTo-MdCell $x.tipo), (ConvertTo-MdCell $x.precondiciones), (ConvertTo-MdCell $x.entrada), (ConvertTo-MdCell $x.procedimiento), (ConvertTo-MdCell $x.esperado), (ConvertTo-MdCell $x.real), $x.resultado))
}
[IO.File]::WriteAllText((Join-Path $OutDir 'matriz-resultados-api.md'), $sb.ToString(), (New-Object System.Text.UTF8Encoding($true)))

Write-Host ''
Write-Host ("TOTAL={0} Aprobados={1} Fallidos={2} Bloqueados={3} Ejecutados={4} Aprobacion={5}%" -f $results.Count, $ap, $fa, $bl, $ex, $pct)
