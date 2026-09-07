# M•REPORT Executive Edition — Milano Studio Design

سامانه گزارش‌گیری Read Only برای سرویس گزارش آتیران، مناسب cPanel، موبایل Android/iPhone، تبلت و دسکتاپ.

## قابلیت‌ها
- چهار تم: Obsidian Neon، Royal Gold، Pearl Azure، Ivory Sage
- داشبورد مدیریتی با نمودارهای 3D/Glass و کارت‌های KPI
- مشتریان، کالاها، موجودی، فاکتورها، مطالبات، چک‌ها، پیام‌ها و اطلاعات شرکت
- پرونده مشتری با تماس مستقیم، پیام مستقیم، چاپ، Excel، اشتراک‌گذاری و یادآور
- چاپ مرورگر/PDF از طریق Print to PDF
- خروجی CSV، Excel-compatible و Word-compatible بدون وابستگی به کتابخانه خارجی
- Web Share API برای اشتراک فایل روی موبایل؛ در صورت پشتیبانی، پنل اشتراک سیستم باز می‌شود و کاربر می‌تواند WhatsApp/Instagram/Bale/Rubika و سایر برنامه‌های نصب‌شده را انتخاب کند.
- یادآور با localStorage و Notification API؛ برای نوتیفیکیشن مطمئن در حالت بسته بودن مرورگر، PWA + Service Worker/Push در مرحله بعد توصیه می‌شود.
- Responsive برای Android/iPhone/iPad/Tablet/Desktop

## نصب cPanel
1. PHP 8.2/8.3 یا بالاتر فعال باشد و cURL، JSON و Sessions در دسترس باشند.
2. محتویات این آرشیو (شامل `index.html`، پوشه‌های `api`، `config`، `assets` و ...) را داخل `public_html/atiran` قرار دهید. اگر از آدرس دامنه دیگری استفاده می‌کنید، مسیر را در `MainActivity.kt` و `README` هماهنگ کنید.
3. `config/config.example.php` را به `config.php` تبدیل و مقادیر را تنظیم کنید؛ `atiran_service_url`، `atiran_username` و `atiran_password` باید آدرس و اعتبار سرویس آتیران باشند.
4. برای کاهش خطر افشای اطلاعات، پوشه `config` را با `.htaccess` از دسترسی مستقیم محافظت کنید (فایل `.htaccess` داخل پوشه `config` همین کار را می‌کند) یا آن را خارج از `public_html` قرار دهید.
5. رمز SQL Server را داخل HTML یا APK قرار ندهید؛ Backend باید واسط سرویس باشد.
6. `api/bootstrap.php` به‌صورت خودکار مسیر `config.php` را در این مسیرهای رایج جستجو می‌کند:
   - `project/api/../config/config.php`
   - `project/config/config.php`
   - `home/config/config.php` (خارج از public_html)
   - `project/api/config.php`

## رفع مشکل ورود
اگر ورود شکست می‌خورد، این موارد را بررسی کنید:
- فایل `config/config.php` وجود داشته باشد (خطای `Failed opening required .../config/config.php` یعنی فایل پیدا نشده؛ مسیر در `api/bootstrap.php` اکنون خودکار حل می‌شود).
- آدرس `atiran_service_url` در `config/config.php` صحیح و در دسترس باشد. اگر سرویس بیرونی پاسخ ندهد، `login` با پیام مربوط به ارتباط با سرویس آتیران رد می‌شود.
- نام کاربری/رمز سرویس آتیران (`atiran_username` / `atiran_password`) در config پر باشد؛ این مقادیر به‌صورت `ServiceLogin` در بدنه‌ی درخواست به سرویس ارسال می‌شوند.
- در اپ اندروید آدرس بک‌اند با کلید `mreport.apiBaseUrl` در `android/gradle.properties` تنظیم می‌شود و در `BuildConfig.API_BASE_URL` قرار می‌گیرد. مقدار پیش‌فرض: `https://ainetmee.com/atiran/api/index.php?r=`

## نکته درباره PDF/Word/Excel
PDF با print engine مرورگر ساخته می‌شود تا بدون کتابخانه خارجی روی cPanel کار کند. Excel و Word به‌صورت فایل سازگار با Office تولید می‌شوند. برای PDF/Excel کاملاً استاندارد و حرفه‌ای در مرحله سرور می‌توان از کتابخانه‌های PHP مثل Dompdf/PhpSpreadsheet استفاده کرد.

## داده و دقت
گزارش‌ها فقط از endpointهای موجود در پروژه آتیران استفاده می‌کنند. محاسبات جدید فقط زمانی باید اضافه شوند که فیلد/endpoint واقعی آن در API مشخص باشد؛ عدد یا ستون ساختگی تولید نمی‌شود.
