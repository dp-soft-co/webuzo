# Webuzo Laravel Package

Laravel package for Webuzo Admin & Enduser APIs with a high-level `WebuzoService` class.

## Requirements

- PHP >= 8.2
- Laravel 10.x – 13.x

## Installation

```bash
composer require dp-soft-co/webuzo

php artisan vendor:publish --tag=webuzo-config
```

## Configuration

Add to your `.env`:

```env
WEBUZO_HOST=panel.example.com
WEBUZO_SCHEME=https
WEBUZO_ADMIN_PORT=2005
WEBUZO_ENDUSER_PORT=2003

# API Key auth (default)
WEBUZO_AUTH_METHOD=api_key
WEBUZO_API_USER=root
WEBUZO_API_KEY=your_api_key

# OR Basic Auth (required for SSO)
WEBUZO_AUTH_METHOD=credentials
WEBUZO_USERNAME=root
WEBUZO_PASSWORD=your_root_password

WEBUZO_SSL_VERIFY=false
WEBUZO_LOGGING=false
WEBUZO_MAX_RETRIES=3
```

## Usage

There are two ways to use the package:

### 1. WebuzoService (High-level — recommended)

`WebuzoService` wraps all API calls and returns a consistent `['success' => bool, ...]` array.

```php
use Webuzo\WebuzoService;
```

### 2. Webuzo Facade (Low-level)

Direct access to all API endpoints, returns an `ApiResponse` object.

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::admin()->listUsers();
if ($response->ok()) {
    $users = $response->data['users'];
}
```

---

## WebuzoService Examples

### Server

```php
// معلومات السيرفر
WebuzoService::getServerInfo();

// إحصائيات المستخدمين
WebuzoService::getStats();
```

### Users

```php
// جلب جميع المستخدمين
WebuzoService::getUsers();

// جلب مستخدم واحد
WebuzoService::getUser('client01');

// جلب مستخدمي ريسيلر
WebuzoService::getUsersByOwner('reseller01');

// إضافة مستخدم جديد مع خطة موارد
$result = WebuzoService::addUser([
    'create_user'     => 1,
    'user'            => 'client01',
    'domain'          => 'client01.com',
    'user_passwd'     => 'Strong#Pass1',
    'cnf_user_passwd' => 'Strong#Pass1',
    'email'           => 'info@client01.com',
    'owner'           => 'reseller01',
    'plan'            => 'basic',
]);

if ($result['success']) {
    WebuzoService::assignResourcePlan('client01', 'users');
}

// إيقاف / تفعيل / حذف مستخدم
WebuzoService::suspendUser('client01');
WebuzoService::unsuspendUser('client01');
WebuzoService::deleteUser('client01');
```

### Plans & Resellers

```php
// الخطط المتاحة
WebuzoService::getPackages();

// خطط الموارد
WebuzoService::getPlanNames();

// الريسيلرز
WebuzoService::getResellers();
```

### SSL Certificates

```php
// قائمة الشهادات
WebuzoService::listCertificates('client01');

// إنشاء / إلغاء / تجديد
WebuzoService::createCertificate('client01', 'client01.com');
WebuzoService::revokeCertificate('client01', 'client01.com');
WebuzoService::renewCertificate('client01', 'client01.com');

// Force HTTPS
WebuzoService::forceHttps('client01', 'client01.com', true);   // تفعيل
WebuzoService::forceHttps('client01', 'client01.com', false);  // إلغاء
```

### Databases

```php
// إنشاء قاعدة بيانات + مستخدم + كل الصلاحيات دفعة واحدة
WebuzoService::createDatabaseWithUser('client01', 'mydb', 'mydbuser', 'StrongPass123');
// النتيجة: database = client01_mydb, db_user = client01_mydbuser

// حذف قاعدة بيانات + مستخدم
WebuzoService::deleteDatabaseWithUser('client01', 'mydb', 'mydbuser');
```

### Domains

```php
// Parked domain
WebuzoService::addDomain('client01', 'newdomain.com');

// Addon domain
WebuzoService::addDomain('client01', 'newdomain.com', 'addon', 'public_html/newdomain');

// Subdomain
WebuzoService::addDomain('client01', 'blog.client01.com', 'subdomain', 'public_html/blog', 'blog');

// حذف دومين
WebuzoService::deleteDomain('client01', 'newdomain.com');
```

### Email Accounts

```php
// إنشاء حساب بريد
WebuzoService::createEmailAccount('client01', 'info', 'client01.com', 'EmailPass123');

// حذف حساب بريد
WebuzoService::deleteEmailAccount('client01', 'info@client01.com');
```

### Cron Jobs

```php
// قائمة الكرون جوبز (تحتوي على الـ IDs)
WebuzoService::listCronJobs('client01');

// إنشاء كرون جوب (كل ساعة)
WebuzoService::createCronJob('client01', 'php /home/client01/public_html/artisan schedule:run', '0', '*');

// تعديل كرون جوب
WebuzoService::editCronJob('client01', 3, 'php /home/client01/public_html/artisan schedule:run', '*/5', '*');

// حذف كرون جوب
WebuzoService::deleteCronJob('client01', 3);
```

### SSO (Single Sign-On)

> **Note:** SSO requires Basic Auth. Set `WEBUZO_USERNAME` and `WEBUZO_PASSWORD` in your `.env`.

```php
// الدخول التلقائي للوحة الأدمن
$result = WebuzoService::adminSsoUrl();
if ($result['success']) {
    return redirect($result['url']); // يفتح لوحة الأدمن مباشرة
}

// الدخول التلقائي للوحة مستخدم معين
$result = WebuzoService::userSsoUrl('client01');
if ($result['success']) {
    return redirect($result['url']); // يفتح لوحة client01 مباشرة
}
```

---

## Example Routes

The following routes demonstrate how to use `WebuzoService` in Laravel. Add them to your `routes/web.php` as needed.

```php
use Webuzo\WebuzoService;

// SSO
Route::get('/sso/admin', fn() => WebuzoService::adminSsoUrl()['success']
    ? redirect(WebuzoService::adminSsoUrl()['url'])
    : response()->json(WebuzoService::adminSsoUrl(), 500));

Route::get('/sso/user/{username}', function ($username) {
    $result = WebuzoService::userSsoUrl($username);
    return $result['success'] ? redirect($result['url']) : response()->json($result, 500);
});

// Server
Route::get('/server-info', fn() => response()->json(WebuzoService::getServerInfo()));

// Users
Route::get('/users',                    fn() => response()->json(WebuzoService::getUsers()));
Route::get('/users/stats',              fn() => response()->json(WebuzoService::getStats()));
Route::get('/users/{username}',         fn($u) => response()->json(WebuzoService::getUser($u)));
Route::get('/users/{username}/suspend', fn($u) => response()->json(WebuzoService::suspendUser($u)));
Route::get('/users/{username}/unsuspend', fn($u) => response()->json(WebuzoService::unsuspendUser($u)));
Route::get('/users/{username}/delete',  fn($u) => response()->json(WebuzoService::deleteUser($u)));

// Plans
Route::get('/plans',          fn() => response()->json(WebuzoService::getPackages()));
Route::get('/resource-plans', fn() => response()->json(WebuzoService::getPlanNames()));
Route::get('/resellers',      fn() => response()->json(WebuzoService::getResellers()));

// SSL
Route::get('/users/{username}/certificates',               fn($u) => response()->json(WebuzoService::listCertificates($u)));
Route::get('/users/{username}/certificates/create/{domain}', fn($u, $d) => response()->json(WebuzoService::createCertificate($u, $d)));
Route::get('/users/{username}/certificates/revoke/{domain}', fn($u, $d) => response()->json(WebuzoService::revokeCertificate($u, $d)));
Route::get('/users/{username}/certificates/renew/{domain}',  fn($u, $d) => response()->json(WebuzoService::renewCertificate($u, $d)));
Route::get('/users/{username}/domains/{domain}/force-https/enable',  fn($u, $d) => response()->json(WebuzoService::forceHttps($u, $d, true)));
Route::get('/users/{username}/domains/{domain}/force-https/disable', fn($u, $d) => response()->json(WebuzoService::forceHttps($u, $d, false)));

// Databases
Route::get('/users/{username}/databases/create', function ($username) {
    $result = WebuzoService::createDatabaseWithUser($username, 'mydb', 'mydbuser', 'StrongPass123');
    return response()->json($result);
});
Route::get('/users/{username}/databases/delete', function ($username) {
    $result = WebuzoService::deleteDatabaseWithUser($username, 'mydb', 'mydbuser');
    return response()->json($result);
});

// Emails
Route::get('/users/{username}/emails/create', function ($username) {
    $result = WebuzoService::createEmailAccount($username, 'info', 'example.com', 'EmailPass123');
    return response()->json($result);
});
Route::get('/users/{username}/emails/delete/{email}', fn($u, $e) => response()->json(WebuzoService::deleteEmailAccount($u, $e)));

// Domains
Route::get('/users/{username}/domains/add', function ($username) {
    $result = WebuzoService::addDomain($username, 'newdomain.com', 'addon', 'public_html/newdomain');
    return response()->json($result);
});
Route::get('/users/{username}/domains/delete/{domain}', fn($u, $d) => response()->json(WebuzoService::deleteDomain($u, $d)));

// Cron Jobs
Route::get('/users/{username}/cronjobs',              fn($u) => response()->json(WebuzoService::listCronJobs($u)));
Route::get('/users/{username}/cronjobs/create', function ($username) {
    $result = WebuzoService::createCronJob($username, 'php /home/user/public_html/artisan schedule:run', '0', '*');
    return response()->json($result);
});
Route::get('/users/{username}/cronjobs/edit/{id}', function ($username, $id) {
    $result = WebuzoService::editCronJob($username, (int) $id, 'php /home/user/public_html/artisan schedule:run', '*/5', '*');
    return response()->json($result);
});
Route::get('/users/{username}/cronjobs/delete/{id}', fn($u, $id) => response()->json(WebuzoService::deleteCronJob($u, (int) $id)));
```

---

## ApiResponse Object

All low-level `Webuzo::` calls return an `ApiResponse` with:

| Method/Property | Description |
|---|---|
| `ok()` | `true` if HTTP 2xx |
| `error()` | Error message string |
| `data` | Decoded response array |
| `raw` | Raw response body |
| `status` | HTTP status code |

## Advanced

### Logging

```env
WEBUZO_LOGGING=true
```

### Auto Retry

```env
WEBUZO_MAX_RETRIES=3
WEBUZO_RETRY_DELAY=1000
```

### Rate Limiting

```env
WEBUZO_RATE_LIMITING=true
WEBUZO_RATE_LIMIT_MAX=60
WEBUZO_RATE_LIMIT_WINDOW=60
```

## Error Handling

```php
use Webuzo\Exceptions\ApiException;
use Webuzo\Exceptions\ValidationException;

try {
    $response = Webuzo::admin()->addUser($params);
} catch (ValidationException $e) {
    // missing or invalid parameters
} catch (ApiException $e) {
    // API / network error
}
```

## Docs

https://webuzo.com/docs/api/
