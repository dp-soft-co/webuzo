# Webuzo Laravel Client

عميل Laravel كامل لـ Webuzo Admin و Enduser APIs.

## المتطلبات

- PHP >= 8.2
- Laravel 10.x - 13.x

## التثبيت

```bash
composer require dp-soft-co/webuzo

php artisan vendor:publish --tag=webuzo-config
```

## التكوين

أضف المتغيرات التالية إلى ملف `.env`:

```env
WEBUZO_HOST=panel.example.com
WEBUZO_SCHEME=https
WEBUZO_ADMIN_PORT=2005
WEBUZO_ENDUSER_PORT=2003
WEBUZO_AUTH_METHOD=api_key
WEBUZO_API_USER=root
WEBUZO_API_KEY=your_api_key
WEBUZO_SSL_VERIFY=false
WEBUZO_LOGGING=false
WEBUZO_MAX_RETRIES=3
WEBUZO_RATE_LIMITING=false
```

## الاستخدام الأساسي

### إنشاء مستخدم جديد

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::admin()->addUser([
    'create_user' => 1,
    'user' => 'client01',
    'domain' => 'client01.example.com',
    'user_passwd' => 'Strong#Password1',
    'cnf_user_passwd' => 'Strong#Password1',
    'email' => 'client01@example.com',
]);

if ($response->ok()) {
    echo 'User created successfully';
} else {
    echo 'Error: ' . $response->error();
}
```

### إيقاف مستخدم

```php
Webuzo::admin()->suspendUser([
    'suspend' => 'client01',
    'skip' => 1,
]);
```

### إلغاء إيقاف مستخدم

```php
Webuzo::admin()->unsuspendUser([
    'unsuspend' => 'client01',
    'skip' => 1,
]);
```

### حذف مستخدم

```php
Webuzo::admin()->deleteUser([
    'delete_user' => 'client01',
]);
```

### إضافة دومين (كـ Enduser)

```php
Webuzo::enduserAs('client01')->createDomain([
    'add' => 1,
    'domain_type' => 'addon',
    'domain' => 'shop.example.com',
    'domainpath' => 'public_html/shop',
    'wildcard' => 0,
    'issue_lecert' => 1,
]);
```

### إضافة حساب بريد إلكتروني

```php
Webuzo::enduserAs('client01')->addEmailAccount([
    'add' => 1,
    'login' => 'info',
    'newpass' => 'Strong#Pass123',
    'conf' => 'Strong#Pass123',
    'domain' => 'example.com',
]);
```

### إضافة قاعدة بيانات

```php
Webuzo::enduserAs('client01')->addDatabase([
    'submitdb' => 1,
    'db' => 'app_db',
]);
```

## الاستجابة

جميع الدوال ترجع كائن `ApiResponse` يحتوي على:

- `status` - كود حالة HTTP
- `raw` - نص الاستجابة الأصلي
- `data` - البيانات المفككة (JSON)
- `ok()` - تتحقق من النجاح
- `error()` - ترجع رسالة الخطأ

## الميزات المتقدمة

### التحقق من المعاملات

الباكدج تتحقق تلقائياً من المعاملات المطلوبة:

```php
use Webuzo\Exceptions\ValidationException;

try {
    Webuzo::admin()->addUser(['create_user' => 1]);
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Required parameter 'user' is missing"
}
```

### التسجيل (Logging)

فعّل التسجيل لتتبع جميع الطلبات:

```env
WEBUZO_LOGGING=true
```

### إعادة المحاولة التلقائية

إعادة المحاولة التلقائية للأخطاء (network, 5xx):

```env
WEBUZO_MAX_RETRIES=3
WEBUZO_RETRY_DELAY=1000
```

### التحكم في المعدل (Rate Limiting)

لمنع إغراق الـ API بالطلبات:

```env
WEBUZO_RATE_LIMITING=true
WEBUZO_RATE_LIMIT_MAX=60
WEBUZO_RATE_LIMIT_WINDOW=60
```

## معالجة الأخطاء

```php
use Webuzo\Exceptions\ApiException;
use Webuzo\Exceptions\ValidationException;

try {
    $response = Webuzo::admin()->addUser($params);
    if (!$response->ok()) {
        throw ApiException::fromResponse($response->status(), $response->error());
    }
} catch (ValidationException $e) {
    // خطأ في المعاملات
} catch (ApiException $e) {
    // خطأ في الـ API
}
```

## الدوال المتاحة

### Admin API

- `listUsers()` - قائمة المستخدمين
- `addUser()` - إضافة مستخدم
- `editUser()` - تعديل مستخدم
- `deleteUser()` - حذف مستخدم
- `suspendUser()` - إيقاف مستخدم
- `unsuspendUser()` - إلغاء إيقاف مستخدم
- `resetAccountBandwidthLimit()` - إعادة ضبط الباندويدث
- `createDnsZone()` - إنشاء DNS Zone
- `addARecord()` - إضافة سجل DNS
- `ipBlock()` - حظر/إلغاء حظر IP

### Enduser API

- `createDomain()` - إضافة دومين (parked/subdomain/addon)
- `deleteDomain()` - حذف دومين
- `forceHttps()` - تفعيل HTTPS
- `addRedirect()` - إضافة redirect
- `deleteRedirect()` - حذف redirect
- `addEmailAccount()` - إضافة حساب بريد
- `deleteEmailAccount()` - حذف حساب بريد
- `addDatabase()` - إضافة قاعدة بيانات
- `addDatabaseUser()` - إضافة مستخدم قاعدة بيانات
- `addFtpAccount()` - إضافة حساب FTP
- `installRevokeRenewCertificate()` - إدارة شهادات SSL
- `cronJob()` - إدارة Cron Jobs

## الدعم

للمزيد من المعلومات حول الـ endpoints المتاحة، راجع توثيق Webuzo الرسمي:
https://webuzo.com/docs/api/
