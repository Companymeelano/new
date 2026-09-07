<?php
require __DIR__.'/bootstrap.php';
$cfg=$config; $route=trim($_GET['r']??'health','/'); $data=input(); $t=token($cfg['cpuid']);
if($route==='health') out(['ok'=>true,'app'=>'M•REPORT','brand'=>'Milano Studio Design','time'=>date('c')]);
if($route==='login'){
  $u=trim((string)($data['username']??'')); $p=(string)($data['password']??'');
  if($u===''||$p==='') out(['ok'=>false,'message'=>'نام کاربری و رمز عبور را وارد کنید.'],422);
  $res=atiran($cfg,'Post/Login',['login'=>['Username'=>$u,'Password'=>$p],'token'=>$t]);
  if($res===null) out(['ok'=>false,'message'=>'ورود توسط سرویس آتیران تأیید نشد.'],401);
  $cust=atiran($cfg,'Post/GetCustomerByLogin',['customerLogin'=>['Username'=>$u,'Password'=>$p,'NewPassword'=>null,'Title'=>null,'Path'=>null,'Location'=>null,'Order'=>null,'Shmo'=>null,'Moname'=>null,'Address'=>null,'Job'=>null,'Email'=>null,'Tel1'=>null,'Tel2'=>null,'Cell'=>null,'Credit'=>null,'Man'=>null],'token'=>$t]);

  $customerData=is_array($cust)?$cust:[];
  $derivedShmo=(string)($data['shmo']??'');
  if($derivedShmo===''){ if(isset($customerData['Shmo'])) $derivedShmo=(string)$customerData['Shmo']; elseif(isset($customerData[0])&&is_array($customerData[0])&&isset($customerData[0]['Shmo'])) $derivedShmo=(string)$customerData[0]['Shmo']; }
  $_SESSION['mreport']=['logged_in'=>true,'username'=>$u,'role'=>$res,'customer'=>$customerData,'shmo'=>$derivedShmo];
  out(['ok'=>true,'user'=>$_SESSION['mreport']]);
}
if($route==='logout'){ $_SESSION=[]; session_destroy(); out(['ok'=>true]); }
$s=requireLogin(); $shmo=(string)($data['shmo']??$s['shmo']??'');
$routes=[
 'company'=>fn()=>atiran($cfg,'Get/CompanyInfo',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'periods'=>fn()=>atiran($cfg,'Get/GetPeriods',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'counts'=>fn()=>['customers'=>atiran($cfg,'Get/CountMo',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),'goods'=>atiran($cfg,'Get/CountKa',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),'maxShmo'=>atiran($cfg,'Get/MaxShMo',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']])],
 'customers'=>fn()=>atiran($cfg,'Get/Customers/'.max(0,(int)($data['start']??0)).'/'.min(1000,max(1,(int)($data['fetch']??200))),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'customer'=>fn()=>atiran($cfg,'Get/CustomerByShMo/'.rawurlencode($shmo),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'groups.customers'=>fn()=>atiran($cfg,'Get/CustGroups',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'goods'=>fn()=>atiran($cfg,'Get/Kalas/'.max(0,(int)($data['start']??0)).'/'.min(1000,max(1,(int)($data['fetch']??200))),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'groups.goods'=>fn()=>atiran($cfg,'Get/KaGroups',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'prices'=>fn()=>atiran($cfg,'Get/ForoshPrices/'.max(0,(int)($data['start']??0)).'/'.min(1000,max(1,(int)($data['fetch']??200))),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'warehouses'=>fn()=>atiran($cfg,'Get/Anbars',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'inventory'=>fn()=>atiran($cfg,'Get/InventoryAnbars/'.max(0,(int)($data['start']??0)).'/'.min(1000,max(1,(int)($data['fetch']??500))),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'invoices'=>fn()=>atiran($cfg,'Get/CustomerFactors/'.rawurlencode($shmo),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'unpaid'=>fn()=>atiran($cfg,'Get/NotPaidFactors/'.rawurlencode($shmo),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'checks'=>fn()=>atiran($cfg,'Get/Checks',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'customer.checks'=>fn()=>atiran($cfg,'Get/CustomerChecks/'.rawurlencode($shmo),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'customer.acts'=>fn()=>atiran($cfg,'Get/CustActsReport/'.rawurlencode($shmo).'/'.rawurlencode((string)($data['beginDate']??'1900/01/01')).'/'.rawurlencode((string)($data['endDate']??'2999/12/29')),['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
 'messages'=>fn()=>atiran($cfg,'Get/VisitorMessages',['CPUID'=>$t['CPUID'],'Key'=>$t['Key']]),
];
if(!isset($routes[$route])) out(['ok'=>false,'message'=>'گزارش ناشناخته است.'],404);
try { out(['ok'=>true,'data'=>$routes[$route]()]); } catch(Throwable $e){ out(['ok'=>false,'message'=>$e->getMessage()],500); }
