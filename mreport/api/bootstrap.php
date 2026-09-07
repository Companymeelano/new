<?php
// Resolve config from several common layouts so the API no longer dies with a
// fatal "Failed opening required .../config/config.php".
$configCandidates = [
    dirname(__DIR__) . '/config/config.php',       // project/api/../config/config.php  -> project/config/config.php
    dirname(__DIR__, 2) . '/config/config.php',    // project/config/config.php          -> legacy layout next to app root
    dirname(__DIR__, 3) . '/config/config.php',    // outside public_html: home/config/config.php
    __DIR__ . '/../config.php',                    // api/config.php fallback
];
$configPath = null;
foreach ($configCandidates as $candidate) {
    if (is_file($candidate)) { $configPath = $candidate; break; }
}
if ($configPath === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        ['ok' => false, 'message' => 'تنظیمات پیدا نشد. فایل config/config.php باید در مسیر پروژه (یا یک سطح بالاتر) قرار داشته باشد.'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
$config = require $configPath;
session_name($config['session_name'] ?? 'mreport_session');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array('*', $config['allowed_origins'] ?? [], true)) header('Access-Control-Allow-Origin: *');
elseif ($origin && in_array($origin, $config['allowed_origins'] ?? [], true)) header('Access-Control-Allow-Origin: '.$origin);
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
function out($data, int $status=200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function input(): array { $raw=file_get_contents('php://input'); $j=json_decode($raw ?: '{}', true); return is_array($j)?$j:$_POST; }
function atiran_credentials(array $config): array {
    $u=(string)($config['atiran_username']??''); $p=(string)($config['atiran_password']??'');
    if($u===''||$p==='') return [];
    return ['Username'=>$u,'Password'=>$p];
}
function token(string $cpuid): array { $now=new DateTimeImmutable('now'); $key=$now->format('YmdHis'); return ['CPUID'=>$cpuid,'Key'=>(string)((int)$key*3593)]; }
function atiran(array $config,string $path,array $body): mixed {
    $creds=atiran_credentials($config); if($creds!==[]) $body['ServiceLogin']=$creds;
    $url=rtrim($config['atiran_service_url'],'/').'/'.ltrim($path,'/');
    $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>['Content-Type: application/json; charset=utf-8'],CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_UNICODE)]);
    $raw=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($raw===false) out(['ok'=>false,'message'=>'ارتباط با سرویس آتیران برقرار نشد: '.$err],502);
    if($code<200||$code>=300) out(['ok'=>false,'message'=>'سرویس آتیران HTTP '.$code,'raw'=>substr($raw,0,500)],502);
    $root=json_decode($raw,true); if(!is_array($root)) out(['ok'=>false,'message'=>'پاسخ سرویس نامعتبر است.'],502);
    if((int)($root['Status']??0)!==1) out(['ok'=>false,'message'=>(string)($root['Result']??'خطای سرویس آتیران')],401);
    $result=$root['Result']??null; if(is_string($result)){ $decoded=json_decode($result,true); return $decoded===null && $result!=='null' ? $result : $decoded; } return $result;
}
function requireLogin(): array { if(empty($_SESSION['mreport']['logged_in'])) out(['ok'=>false,'message'=>'نیاز به ورود مجدد است.'],401); return $_SESSION['mreport']; }
