package com.milano.mreport

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.JavaNetCookieJar
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.net.CookieManager
import java.net.CookiePolicy
import java.util.concurrent.TimeUnit

private val Bg=Color(0xFF070911); private val Panel=Color(0xFF111526); private val Purple=Color(0xFF8B5CF6); private val Cyan=Color(0xFF22D3EE)
// آدرس بک‌اند در android/gradle.properties با کلید mreport.apiBaseUrl تنظیم می‌شود.
private val API=BuildConfig.API_BASE_URL

class MainActivity:ComponentActivity(){ override fun onCreate(b:Bundle?){super.onCreate(b);setContent{MReportApp()}} }

class Api {
    companion object {
        private val cookieManager=CookieManager().apply { setCookiePolicy(CookiePolicy.ACCEPT_ALL) }
        private val http=OkHttpClient.Builder()
            .connectTimeout(20,TimeUnit.SECONDS)
            .readTimeout(60,TimeUnit.SECONDS)
            .cookieJar(JavaNetCookieJar(cookieManager))
            .build()
    }
    suspend fun post(route:String,body:JSONObject):JSONObject=withContext(Dispatchers.IO){val req=Request.Builder().url(API+route).post(body.toString().toRequestBody("application/json".toMediaType())).build();http.newCall(req).execute().use{r->JSONObject(r.body?.string()?:("{}"))}}
}

@Composable fun MReportApp(){var logged by remember{mutableStateOf(false)};var user by remember{mutableStateOf("")};var error by remember{mutableStateOf("")};var busy by remember{mutableStateOf(false)};val scope=rememberCoroutineScope();if(!logged)LoginScreen(error,busy){u,p->if(!busy){busy=true;error="";scope.launch{try{val j=Api().post("login",JSONObject().put("username",u).put("password",p));if(j.optBoolean("ok",false)){logged=true;user=u}else error=j.optString("message","ورود ناموفق")}catch(e:Exception){error=e.message?:"خطا در اتصال"}finally{busy=false}}}}else Dashboard(user){logged=false}}

@Composable fun LoginScreen(error:String,busy:Boolean,onLogin:(String,String)->Unit){var u by remember{mutableStateOf("")};var p by remember{mutableStateOf("")};Box(Modifier.fillMaxSize().background(Brush.radialGradient(listOf(Color(0xFF29164D),Bg),radius=1000f)).padding(24.dp),contentAlignment=Alignment.Center){Card(colors=CardDefaults.cardColors(Panel),shape=RoundedCornerShape(28.dp),modifier=Modifier.fillMaxWidth().widthIn(max=430.dp)){Column(Modifier.padding(28.dp),horizontalAlignment=Alignment.CenterHorizontally){Box(Modifier.size(82.dp).background(Brush.linearGradient(listOf(Purple,Cyan)),RoundedCornerShape(25.dp)),contentAlignment=Alignment.Center){Text("M",fontSize=42.sp,fontWeight=FontWeight.Black,color=Color.White)};Spacer(Modifier.height(16.dp));Text("M•REPORT",fontSize=27.sp,fontWeight=FontWeight.Black);Text("گزارش‌گیری مدیریتی آتیران",color=Color.LightGray,fontSize=12.sp);Spacer(Modifier.height(25.dp));OutlinedTextField(u,{u=it},label={Text("نام کاربری")},singleLine=true,modifier=Modifier.fillMaxWidth());Spacer(Modifier.height(10.dp));OutlinedTextField(p,{p=it},label={Text("رمز عبور")},singleLine=true);Spacer(Modifier.height(15.dp));Button(onClick={onLogin(u,p)},enabled=!busy&&u.isNotBlank()&&p.isNotBlank(),modifier=Modifier.fillMaxWidth()){Text(if(busy)"در حال ورود…" else "ورود امن")};if(error.isNotBlank())Text(error,color=Color(0xFFFF7185),fontSize=11.sp,modifier=Modifier.padding(top=10.dp));Spacer(Modifier.height(18.dp));Text("Milano Studio Design • میلادی عقیلی",color=Color.Gray,fontSize=10.sp)}}}}

@Composable fun Dashboard(user:String,onLogout:()->Unit){var page by remember{mutableStateOf("dashboard")};Scaffold(containerColor=Bg,bottomBar={NavigationBar(containerColor=Panel){listOf("dashboard" to "خانه","customers" to "مشتری","goods" to "کالا","inventory" to "انبار","checks" to "چک").forEach{(id,label)->NavigationBarItem(selected=page==id,onClick={page=id},icon={Icon(Icons.Default.Assessment,null)},label={Text(label,fontSize=9.sp)})}}}){pad->Column(Modifier.fillMaxSize().padding(pad).padding(horizontal=14.dp)){Row(Modifier.fillMaxWidth().padding(vertical=12.dp),verticalAlignment=Alignment.CenterVertically){Column(Modifier.weight(1f)){Text("M•REPORT",fontSize=22.sp,fontWeight=FontWeight.Black);Text("پنل مدیریتی • $user",fontSize=10.sp,color=Color.Gray)}IconButton(onClick=onLogout){Icon(Icons.Default.Logout,"خروج")}};when(page){"dashboard"->Home();"customers"->ReportPage("مشتریان","customers");"goods"->ReportPage("کالاها","goods");"inventory"->ReportPage("موجودی انبار","inventory");"checks"->ReportPage("چک‌ها","checks")}}}}

@Composable fun Home(){Column{Row(Modifier.fillMaxWidth(),horizontalArrangement=Arrangement.spacedBy(10.dp)){Metric("مشتریان","API واقعی");Metric("کالاها","API واقعی")};Spacer(Modifier.height(12.dp));Card(colors=CardDefaults.cardColors(Panel),shape=RoundedCornerShape(22.dp),modifier=Modifier.fillMaxWidth()){Column(Modifier.padding(20.dp)){Text("مرکز کنترل مدیر",fontWeight=FontWeight.Bold,fontSize=16.sp);Spacer(Modifier.height(10.dp));Text("فاکتورها • مطالبات • چک‌ها • موجودی • فروش • اطلاعات شرکت",color=Color.LightGray,fontSize=12.sp);Spacer(Modifier.height(14.dp));Text("تمام بخش‌ها Read Only هستند و داده‌ها از Backend امن M•REPORT دریافت می‌شوند.",color=Color.Gray,fontSize=10.sp)}}}}
@Composable fun Metric(a:String,b:String){Card(colors=CardDefaults.cardColors(Panel),shape=RoundedCornerShape(18.dp),modifier=Modifier.weight(1f)){Column(Modifier.padding(16.dp)){Text(a,color=Color.Gray,fontSize=10.sp);Text(b,fontWeight=FontWeight.Bold,fontSize=17.sp,color=Cyan)}}}
@Composable fun ReportPage(title:String,route:String){var rows by remember{mutableStateOf(listOf<Map<String,String>>())};var loading by remember{mutableStateOf(true)};LaunchedEffect(route){loading=true;rows=try{val j=Api().post(route,JSONObject().put("start",0).put("fetch",300));val a=j.optJSONArray("data")?:j.optJSONArray("Result");if(a==null) emptyList() else (0 until a.length()).map{idx->val o=a.getJSONObject(idx);o.keys().asSequence().associateWith{k->o.optString(k,"-")}}}catch(_:Exception){emptyList()};loading=false};Card(colors=CardDefaults.cardColors(Panel),shape=RoundedCornerShape(20.dp),modifier=Modifier.fillMaxSize()){Column(Modifier.padding(14.dp)){Text(title,fontSize=18.sp,fontWeight=FontWeight.Bold);Spacer(Modifier.height(8.dp));if(loading)Box(Modifier.fillMaxWidth().height(120.dp),contentAlignment=Alignment.Center){CircularProgressIndicator(color=Cyan)}else LazyColumn(verticalArrangement=Arrangement.spacedBy(8.dp)){items(rows.take(200)){r->Card(colors=CardDefaults.cardColors(Color(0xFF181D2E)),shape=RoundedCornerShape(14.dp)){Column(Modifier.padding(12.dp)){r.entries.take(5).forEach{(k,v)->Text("$k: $v",fontSize=10.sp,color=Color.LightGray);Spacer(Modifier.height(2.dp))}}}}}}}}}
