# Webuzo Laravel Client

A complete Laravel client for Webuzo Admin and Enduser APIs. This README
provides full details (parameters, required/optional flags, sample curl/php
requests and usage examples) so you do not need to consult the official docs
for the covered endpoints.

Supported: Admin API (port 2005) and Enduser API (port 2003). Use admin
credentials to operate on enduser accounts with the `loginAs` parameter.

## Requirements

- PHP >= 8.2
- Laravel 10.x - 13.x (illuminate/support, illuminate/http)

## Install

```bash
composer require dp-soft-co/webuzo

php artisan vendor:publish --tag=webuzo-config
```

## Configuration

Edit `config/webuzo.php` or set these environment variables in `.env`:

```
WEBUZO_HOST=panel.example.com
WEBUZO_SCHEME=https
WEBUZO_ADMIN_PORT=2005
WEBUZO_ENDUSER_PORT=2003
WEBUZO_RESPONSE=json        # json or serialize
WEBUZO_AUTH_METHOD=api_key  # or credentials
WEBUZO_API_USER=root
WEBUZO_API_KEY=your_api_key
WEBUZO_USERNAME=root        # if using credentials
WEBUZO_PASSWORD=your_pass   # if using credentials
WEBUZO_SSL_VERIFY=false     # for self-signed certs
```

Notes:
- When `WEBUZO_AUTH_METHOD=api_key` the package sends `apiuser` + `apikey` in
    request body. For `credentials` it uses HTTP Basic Auth.

## How the client maps to Webuzo

- `Webuzo::admin()` -> Admin API (port 2005)
- `Webuzo::enduser()` -> Enduser API (port 2003)
- `Webuzo::enduserAs('username')` or `Webuzo::enduser()->as('username')` uses
    the `loginAs` parameter so admin credentials can manage a specific user.

All methods accept an associative array of parameters. The client exposes named
methods for common endpoints; any `act` can also be called with `call('act', $params)`.

Example (generic):

```php
use Webuzo\Facades\Webuzo;

$resp = Webuzo::admin()->call('users', ['search' => 'demo']);
if (!$resp->ok()) {
        throw new \RuntimeException($resp->error() ?? 'Unknown error');
}
print_r($resp->data);
```

## Response

The returned `Webuzo\Support\ApiResponse` object has:

- `status` (HTTP status code)
- `raw` (raw response body)
- `data` (decoded JSON or unserialized data)
- helper methods: `ok()` and `error()`

---

## Detailed Endpoints (Admin)

This section lists the Admin endpoints implemented as named methods with the
exact parameter names and their requirement status.

### Add / Edit User

Method: `addUser(array $params)` / `editUser(array $params)`

Act: `add_user`

Parameters:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| create_user / edit_user | POST | Trigger create or edit user | Yes |
| domain | POST | Primary domain for user | Yes |
| user / user_name | POST | Username | Yes |
| user_passwd | POST | Password | Yes |
| cnf_user_passwd | POST | Confirm password | Yes |
| email | POST | User email | No |
| plan | POST | Plan name (or use `prefill_default`) | No |
| billing_prefill | POST | Use plan defaults (1) | No |
| prefill_default | POST | Use default plan (1) | No |
| ip | POST | IPv4 to assign | No |
| ipv6 | POST | IPv6 to assign | No |

Sample curl (Admin, port 2005):

```bash
curl --insecure -d "create_user=1" \
    -d "user=curluser" -d "domain=example.com" \
    -d "user_passwd=Pa$$w0rd" -d "cnf_user_passwd=Pa$$w0rd" \
    -u "admin:password" \
    "https://panel.example.com:2005/index.php?api=json&act=add_user"
```

Example using package:

```php
Webuzo::admin()->addUser([
        'create_user' => 1,
        'user' => 'curluser',
        'domain' => 'example.com',
        'user_passwd' => 'Pa$$w0rd',
        'cnf_user_passwd' => 'Pa$$w0rd',
]);
```

### List Users

Method: `listUsers(array $params)`

Act: `users`

Params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| search | GET | Search by username | No |
| domain | GET | Search by domain | No |
| owner | GET | Search by owner | No |
| email | GET | Search by email | No |
| ip | GET | Search by IP | No |
| reslen | GET | Number of records per page | No |
| page | GET | Page number | No |

```php
$resp = Webuzo::admin()->listUsers(['search' => 'demo']);
```

### Delete User

Method: `deleteUser(array $params)`

Act: `users` (with `delete_user`)

Params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| delete_user | POST | Username or comma-separated list | Yes |
| del_sub_acc | POST | 1 delete reseller sub accounts | No |
| skip_reseller | POST | 1 skip reseller deletion | No |

Sample:

```php
Webuzo::admin()->deleteUser(['delete_user' => 'user1,user2']);
```

### Reset Account Bandwidth

Method: `resetAccountBandwidthLimit(array $params)`

Act: `reset_bandwidth`

Params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| reset | POST | set to 1 to trigger | Yes |
| user | POST | username or comma-separated list | Yes |

```php
Webuzo::admin()->resetAccountBandwidthLimit(['reset' => 1, 'user' => 'demo']);
```

### IP Block (Admin & Enduser)

Method: `ipBlock(array $params)`

Act: `ipblock`

Block IP params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| add_ip | GET | set to 1 to block IP | Yes |
| dip / ip | POST | IP address or CIDR to block | Yes |

Unblock params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| delete | GET | set to 1 to unblock | Yes |
| ip | GET | IP to unblock | Yes |

Sample block:

```php
Webuzo::admin()->ipBlock(['add_ip' => 1, 'dip' => '1.2.3.4/32']);
```

---

## Detailed Endpoints (Enduser)

### Create Domain (Parked/Subdomain/Addon)

Method: `createDomain(array $params)`

Act: `domainadd`

Common params (parked/subdomain/addon):

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| add | GET | set to 1 to add domain | Yes |
| domain_type | GET | `parked`, `subdomain`, or `addon` | Yes |
| domain | GET | domain name | Yes |
| wildcard | GET | 0 or 1 | Yes |
| issue_lecert | GET | 0 or 1 (issue Let’s Encrypt) | Yes |

Subdomain additional params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| domainpath | GET | public_html/subdomain | Yes |
| subdomain | GET | subdomain name | Yes |

Addon additional params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| domainpath | GET | public_html/addon_domain_name | Yes |

Sample (create addon):

```php
Webuzo::enduserAs('soft')->createDomain([
        'add' => 1,
        'domain_type' => 'addon',
        'domain' => 'new.example.com',
        'domainpath' => 'public_html/new',
        'wildcard' => 0,
        'issue_lecert' => 1,
]);
```

### Delete Domain

Method: `deleteDomain(array $params)`

Act: `domainmanage` (with `delete`)

Params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| delete | GET | domain or comma-separated list | Yes |

```php
Webuzo::enduserAs('soft')->deleteDomain(['delete' => 'example.com']);
```

### Redirects

Method: `addRedirect(array $params)` / `listRedirects()` / `deleteRedirect()`

Act: `redirects`

Params to add:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| add | GET | 1 to add | Yes |
| selectdomain | GET | domain | Yes |
| path | GET | path to redirect from | Yes |
| type | GET | `temporary` or `permanent` | Yes |
| address | GET | destination URL | Yes |

Sample:

```php
Webuzo::enduserAs('soft')->addRedirect([
        'add' => 1,
        'selectdomain' => 'example.com',
        'path' => '',
        'type' => 'temporary',
        'address' => 'https://example2.com',
]);
```

### FTP (Add / List / Delete / Connections)

Method: `addFtpAccount`, `listFtpAccounts`, `deleteFtpAccount`, `ftpConnections`

Add FTP params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| create_acc | POST | 1 to create | Yes |
| login | POST | ftp username | Yes |
| newpass | POST | password | Yes |
| conf | POST | confirm password | Yes |
| ftpdomain | POST | domain | Yes |
| dir | POST | directory | No |
| quota | POST | `unlimited` or `limited` | Yes |
| quota_limit | POST | MB when limited | No |

Sample:

```php
Webuzo::enduserAs('soft')->addFtpAccount([
        'create_acc' => 1,
        'login' => 'ftpuser',
        'newpass' => 'Pa$$',
        'conf' => 'Pa$$',
        'ftpdomain' => 'example.com',
        'quota' => 'limited',
        'quota_limit' => 1024,
]);
```

List active FTP connections / disconnect:

```php
Webuzo::enduserAs('soft')->ftpConnections();
Webuzo::enduserAs('soft')->ftpConnections(['ftp_connection_pid' => 27628]);
```

### Email Accounts

Method: `addEmailAccount`, `editEmailAccount`, `deleteEmailAccount`

Add params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| add | POST | 1 to add | Yes |
| login | POST | email user | Yes |
| newpass | POST | password | Yes |
| conf | POST | confirm password | Yes |
| domain | POST | domain | Yes |
| quota | POST | unlimited/limited | No |
| quota_limit | POST | MB if limited | No |
| incoming | POST | allow / suspend | No |
| outgoing | POST | allow / suspend / hold | No |

Delete:

| delete | POST | email address or comma list | Yes |

```php
Webuzo::enduserAs('soft')->addEmailAccount([
        'add' => 1,
        'login' => 'postmaster',
        'newpass' => 'Pa$$',
        'conf' => 'Pa$$',
        'domain' => 'example.com',
]);
```

### Databases

Method: `addDatabase`, `addDatabaseUser`, `addDatabaseUserToDatabase`, `deleteDatabase`

Add database:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| submitdb | GET | 1 to create | Yes |
| db | GET | database name | Yes |

Add DB user:

| submituserdb | GET | 1 to create user | Yes |
| dbuser | GET | username | Yes |
| dbpassword | GET | password | Yes |

Assign user to database (privileges):

| submitpri | GET | 1 to assign | Yes |
| dbname | GET | database name | Yes |
| dbuser | GET | database username | Yes |
| host | GET | host (`localhost` or `%`) | Yes |
| pri[...] | GET | privileges array (SELECT, INSERT...) | Yes |

Sample: assign privileges

```php
Webuzo::enduserAs('soft')->addDatabaseUserToDatabase([
        'submitpri' => 1,
        'dbname' => 'app_db',
        'dbuser' => 'app_user',
        'host' => 'localhost',
        'pri[SELECT]' => 'Y',
        'pri[INSERT]' => 'Y',
]);
```

### SSL (Auto Install / Revoke / Renew)

Method: `installRevokeRenewCertificate`, `installCertificate`

Act: `acme` / `install_cert`

Install / revoke / renew params:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| install_cert | POST | 1 to install | Yes for install |
| domain[] | POST | array of domains | Yes |
| revoke_cert | POST | 1 to revoke | Yes for revoke |
| renew_cert | POST | 1 to renew | Yes for renew |

Sample:

```php
Webuzo::enduserAs('soft')->installRevokeRenewCertificate([
        'install_cert' => 1,
        'domain[]' => ['example.com', 'www.example.com'],
]);
```

### API Keys (Enduser)

Method: `apiKeys()`, `addApiKey()`, `deleteApiKey()` (act `apikey`)

Add:

| do | GET | set to 1 to add | Yes |
| ip[] | GET | allowed IPs | No |
| notes | GET | notes for the key | No |

Delete:

| del | GET | key to delete | Yes |

Sample add:

```php
Webuzo::enduserAs('soft')->addApiKey(['do' => 1, 'ip[]' => ['1.2.3.4'], 'notes' => 'CI']);
```

### Cron Jobs

Method: `cronJob(array $params)` (act `cronjob`)

Add:

| Name | Method | Description | Required |
| --- | --- | --- | --- |
| create_record | GET | 1 to add | Yes |
| minute | GET | 0-59 | Yes |
| hour | GET | 0-23 | Yes |
| day | GET | 1-31 | Yes |
| month | GET | 1-12 | Yes |
| weekday | GET | 0-6 | Yes |
| cmd | GET | command to run | Yes |

Edit / Delete / Update email use `edit_record`, `delete_record`, `update_cron_email` respectively.

Sample add:

```php
Webuzo::enduserAs('soft')->cronJob([
        'create_record' => 1,
        'minute' => '0',
        'hour' => '*/6',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
        'cmd' => '/usr/bin/php /home/soft/artisan schedule:run',
]);
```

---

## Errors & Debugging

- If Webuzo returns serialized responses, set `WEBUZO_RESPONSE=serialize` in
    `.env`. The client will unserialize automatically.
- For debugging, set `WEBUZO_SSL_VERIFY=false` (only for testing) and inspect
    `$response->raw` when `$response->data` is null.

## Best Practices

- Use Admin API for user-wide actions and Enduser API for user-scoped actions.
- Prefer API keys for automation; rotate keys periodically.
- Validate responses (call `ok()` on the returned `ApiResponse`).
- When calling methods that accept arrays (eg. `domain[]`) pass them as
    `['domain[]' => ['a','b']]` or as individual keys `domain[]` depending on
    how your HTTP client encodes arrays. The package sends form-encoded body.

## Missing endpoints / contributions

If you need any additional endpoint added (some docs are long and occasionally
have new acts), open an issue or request the specific endpoint and I will add
the named method and an example.

---

If you want, I will now run through the remaining API pages and add any
missing endpoint parameter tables to this README (e.g., DNS templates, MX
records, advanced DNS operations). Reply "add DNS & MX tables" to include
those as well.

Typical call:

```php
Webuzo::admin()->deleteUser([
    'delete_user' => 'demo1,demo2',
    'del_sub_acc' => 1,
    'skip_reseller' => 1,
]);
```

#### Suspend and unsuspend users

The package maps both operations to the same `users` act because Webuzo treats
account management actions as variants of the users screen. In practice, these
operations use user-management switches for suspension or unsuspension while
targeting one or more usernames.

Use the corresponding named methods:

```php
Webuzo::admin()->suspendUser([
    'user' => 'demo1',
]);

Webuzo::admin()->unsuspendUser([
    'user' => 'demo1',
]);
```

### Admin `add_user`

Used by:

- `addUser()`
- `editUser()`

#### Create user

| Field | Required | Meaning |
| --- | --- | --- |
| `create_user` | Yes | Set to `1` to trigger account creation. |
| `domain` | Yes | Primary domain. |
| `user` | Yes | Username. |
| `user_passwd` | Yes | Password. |
| `cnf_user_passwd` | Yes | Confirm password. |
| `email` | No | Email address. |
| `plan` | Conditionally | Plan name. If set, pair it with `billing_prefill=1`. |
| `billing_prefill` | Conditionally | Use selected plan defaults. |
| `prefill_default` | Conditionally | Use default plan fields when `plan` is not supplied. |

Typical call:

```php
Webuzo::admin()->addUser([
    'create_user' => 1,
    'user' => 'client01',
    'domain' => 'client01.example.com',
    'user_passwd' => 'Strong#Password1',
    'cnf_user_passwd' => 'Strong#Password1',
    'email' => 'client01@example.com',
    'plan' => 'basic',
    'billing_prefill' => 1,
]);
```

#### Edit user

`editUser()` uses the same Webuzo screen and `act`. Pass the account-selection
fields plus the values you want to update. The package does not rename or
translate any field names, so send the exact Webuzo form fields required by the
edit action.

### Admin `resource_limits`

Used by:

- `resourceLimits()`

#### Create plan

| Field | Meaning |
| --- | --- |
| `save=1` | Create or save a plan. |
| `plan` | Plan name. |
| `cpuquota` | CPU percent, minimum `25`. |
| `read_bw` / `write_bw` | Read/write IO bandwidth such as `256K`, `1G`. |
| `diskread` / `diskwrite` | Max IOPS values. |
| `mem_max` / `memhigh` | Memory cap and high watermark, such as `1G`, `800M`. |
| `maxtask` | Max tasks/processes. |

#### Edit plan

| Field | Meaning |
| --- | --- |
| `save=1` | Save the updated plan. |
| `plan` | New plan name. |
| `edit` | Existing plan name being edited. |

#### Delete plan

| Field | Meaning |
| --- | --- |
| `delete` | Plan name to delete. |

#### Assign plan to selected users

| Field | Meaning |
| --- | --- |
| `assign_plan=1` | Trigger assignment. |
| `plan` | Plan name. |
| `users[]` | Array of usernames. |

#### Assign plan to all users

| Field | Meaning |
| --- | --- |
| `assign_plan=1` | Trigger assignment. |
| `plan` | Plan name. |
| `allusers=true` | Assign to all users. |

#### Remove plan from a user

| Field | Meaning |
| --- | --- |
| `remove_user=1` | Trigger removal. |
| `user` | Username. |
| `plan` | Plan name. |

### Admin `dns_zones`

Used by:

- `createDnsZone()`
- `deleteDnsZone()`

#### Create zone

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1` to create a zone. |
| `domain` | Yes | Zone domain. |
| `ipv4` | Yes | Primary IPv4 for the zone. |
| `user` | No | Zone owner, defaults to `root` if omitted. |

Typical call:

```php
Webuzo::admin()->createDnsZone([
    'add' => 1,
    'domain' => 'example.com',
    'ipv4' => '203.0.113.10',
    'user' => 'root',
]);
```

#### Delete zone

`deleteDnsZone()` uses the same `dns_zones` act and expects the delete-specific
field names from Webuzo's DNS zone management screen.

### Admin `advancedns`

Used by:

- `addARecord()`
- `editDnsRecord()`
- `fetchDnsRecordOfDomain()`
- `deleteDnsRecord()`

#### Add record

| Field | Required | Meaning |
| --- | --- | --- |
| `create_record` | Yes | Set to `1` to create a record. |
| `domain` | Yes | Domain whose zone you are editing. |
| `name` | Yes | Record name or prefix. |
| `ttl` | Yes | TTL value. |
| `selecttype` | Yes | Record type. |
| `address` | Yes | Target address or value. |

Typical call:

```php
Webuzo::admin()->addARecord([
    'create_record' => 1,
    'domain' => 'example.com',
    'name' => 'app',
    'ttl' => 14400,
    'selecttype' => 'A',
    'address' => '203.0.113.11',
]);
```

`editDnsRecord()`, `fetchDnsRecordOfDomain()`, and `deleteDnsRecord()` are
different operations on the same advanced DNS screen and therefore use the same
`advancedns` act with operation-specific switches.

### Admin `park_domain`

Used by:

- `parkDomain()`

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1` to park the domain. |
| `redirect_to` | Yes | Existing domain that receives the parked domain. |
| `domain` | Yes | Domain to park. |

Typical call:

```php
Webuzo::admin()->parkDomain([
    'add' => 1,
    'redirect_to' => 'example.com',
    'domain' => 'www.example.net',
]);
```

### Admin `addstorage`

Used by:

- `addStorage()`
- `editStorage()`

| Field | Required | Meaning |
| --- | --- | --- |
| `addstorage` | Yes | Set to `1` to save a storage definition. |
| `name` | Yes | Storage label. |
| `path` | Yes | Existing directory path on the server. |
| `type` | Yes | Storage type. |
| `alert` | No | Usage percentage threshold for alerts. |

Typical call:

```php
Webuzo::admin()->addStorage([
    'addstorage' => 1,
    'name' => 'backup-storage',
    'path' => '/mnt/backups',
    'type' => 'local',
    'alert' => 80,
]);
```

### Admin `webuzoconfigs`

Used by:

- `changeHostname()`

This act controls three different configuration groups.

#### Update main panel configuration

| Field | Meaning |
| --- | --- |
| `editconfigs=1` | Save panel identity/network settings. |
| `WU_PRIMARY_IP` | Primary IPv4. |
| `WU_PRIMARY_IPV6` | Primary IPv6. |
| `WU_PRIMARY_DOMAIN` | Hostname / panel domain. |
| `WU_NS1`, `WU_NS2` | Nameservers. |
| `quota` | Enable or disable quota handling. |

#### Update Webuzo PHP panel settings

| Field | Meaning |
| --- | --- |
| `webuzophpsettings=1` | Save panel PHP settings. |
| `max_execution_time` | PHP max execution time. |
| `post_max_size` | Max POST size. |
| `upload_max_filesize` | Max upload size. |
| `client_max_body_size` | Web service body size. |
| `pm_max_children` | PHP-FPM max children. |
| `pm_start_servers` | PHP-FPM start servers. |
| `pm_min_spare_servers` | PHP-FPM min spare servers. |
| `pm_max_spare_servers` | PHP-FPM max spare servers. |
| `pm_max_requests` | PHP-FPM max requests. |
| `request_terminate_timeout` | PHP-FPM request timeout. |

#### Update Webuzo panel ports

| Field | Meaning |
| --- | --- |
| `webuzo_ports=1` | Save panel port settings. |
| `admin_port_ssl` | Admin SSL port. |
| `admin_port_nonssl` | Admin non-SSL port. |
| `enduser_port_ssl` | Enduser SSL port. |
| `enduser_port_nonssl` | Enduser non-SSL port. |

### Admin `email_queue`

Used by:

- `viewEmailQueue()`

| Field | Meaning |
| --- | --- |
| `domain` | Optional domain filter. |
| `euser` | Optional email user filter. |

Typical call:

```php
Webuzo::admin()->viewEmailQueue([
    'domain' => 'example.com',
    'euser' => 'noreply',
]);
```

### Admin `ipblock`

Used by:

- `ipBlock()`

#### Block IP or network

| Field | Meaning |
| --- | --- |
| `add_ip=1` | Trigger block operation. |
| `dip` | IP, CIDR, range, or domain to block. |

#### Unblock

| Field | Meaning |
| --- | --- |
| `delete` | Indexed blocked-entry identifier to remove. |

#### List blocked entries

Call the endpoint with no action fields.

### Enduser `domainadd`

Used by:

- `createDomain()`

The package exposes one method, but Webuzo uses the same act for parked,
subdomain, and addon domain creation.

#### Create parked domain

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1` to create the domain. |
| `domain_type` | Yes | `parked` |
| `domain` | Yes | Domain to create. |
| `wildcard` | Yes | `1` to enable wildcard, `0` otherwise. |
| `issue_lecert` | Yes | `1` to request Let's Encrypt, `0` otherwise. |

#### Create subdomain

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1`. |
| `domain_type` | Yes | `subdomain` |
| `domain` | Yes | Parent domain. |
| `domainpath` | Yes | Document root, such as `public_html/blog`. |
| `wildcard` | Yes | `1` or `0`. |
| `issue_lecert` | Yes | `1` or `0`. |
| `subdomain` | Yes | Subdomain label only. |

#### Create addon domain

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1`. |
| `domain_type` | Yes | `addon` |
| `domain` | Yes | Addon domain. |
| `domainpath` | Yes | Document root. |
| `wildcard` | Yes | `1` or `0`. |
| `issue_lecert` | Yes | `1` or `0`. |

Typical call:

```php
Webuzo::enduser()->createDomain([
    'add' => 1,
    'domain_type' => 'addon',
    'domain' => 'shop.example.com',
    'domainpath' => 'public_html/shop',
    'wildcard' => 0,
    'issue_lecert' => 1,
]);
```

### Enduser `domainmanage`

Used by:

- `deleteDomain()`
- `forceHttps()`

#### Delete domains

| Field | Required | Meaning |
| --- | --- | --- |
| `delete` | Yes | Single domain or comma-separated domains. |

Typical call:

```php
Webuzo::enduser()->deleteDomain([
    'delete' => 'old.example.com,staging.example.com',
]);
```

#### Force HTTPS

| Field | Required | Meaning |
| --- | --- | --- |
| `force_https` | Yes | Set to `1` to trigger the feature. |
| `do` | Yes | `1` to enable, `0` to disable. |
| `domain` | Yes | Target domain. |

Typical call:

```php
Webuzo::enduser()->forceHttps([
    'force_https' => 1,
    'do' => 1,
    'domain' => 'example.com',
]);
```

### Enduser `redirects`

Used by:

- `addRedirect()`
- `listRedirects()`
- `deleteRedirect()`

#### Add redirect

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1` to create the rule. |
| `selectdomain` | Yes | Domain that owns the redirect. |
| `path` | Yes | Source path. Empty string means root. |
| `type` | Yes | `temporary` or `permanent`. |
| `address` | Yes | Destination URL. |

#### List redirects

No extra fields are required.

#### Delete redirect

| Field | Required | Meaning |
| --- | --- | --- |
| `delete` | Yes | Set to `1`. |
| `domain` | Yes | Domain that owns the redirect. |
| `path` | Yes | Redirect path to remove. |

Typical add call:

```php
Webuzo::enduser()->addRedirect([
    'add' => 1,
    'selectdomain' => 'example.com',
    'path' => '',
    'type' => 'permanent',
    'address' => 'https://www.example.com',
]);
```

### Enduser `add_email_account`

Used by:

- `addEmailAccount()`
- `editEmailAccount()`

#### Create account

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1` to create the mailbox. |
| `login` | Yes | Mailbox user part only. |
| `newpass` | Yes | Password. |
| `conf` | Yes | Confirm password. |
| `domain` | Yes | Mail domain. |
| `quota` | No | `unlimited` or `limited`. |
| `quota_limit` | No | Limit in MB when `quota=limited`. |
| `incoming` | No | `allow` or `suspend`. |
| `outgoing` | No | `allow`, `suspend`, or `hold`. |
| `allowlogin` | No | `allow` or `suspend`. |
| `no_hash` | No | `1` to skip password hashing. |

Typical call:

```php
Webuzo::enduser()->addEmailAccount([
    'add' => 1,
    'login' => 'support',
    'newpass' => 'Strong#Pass123',
    'conf' => 'Strong#Pass123',
    'domain' => 'example.com',
    'quota' => 'limited',
    'quota_limit' => 2048,
    'incoming' => 'allow',
    'outgoing' => 'allow',
    'allowlogin' => 'allow',
]);
```

#### Edit account

`editEmailAccount()` uses the same Webuzo screen and act. Pass the mailbox
selection fields plus the values you want to update.

### Enduser `email_forward`

Used by:

- `addEmailForwarder()`
- `deleteEmailForwarder()`

#### Create forwarder

| Field | Required | Meaning |
| --- | --- | --- |
| `add` | Yes | Set to `1`. |
| `email` | Yes | Mailbox user part only, not the full address. |
| `domain` | Yes | Mail domain. |
| `forwarder` | Yes | `email`, `pipe`, `sys`, `fail`, or `discard`. |
| `femail` | Conditional | Destination email when `forwarder=email`. |
| `fail` | Conditional | Failure message when `forwarder=fail`. |
| `pipe_path` | Conditional | Relative path when `forwarder=pipe`. |

Typical call:

```php
Webuzo::enduser()->addEmailForwarder([
    'add' => 1,
    'email' => 'sales',
    'domain' => 'example.com',
    'forwarder' => 'email',
    'femail' => 'team@example.net',
]);
```

#### Delete forwarder

`deleteEmailForwarder()` uses the same `email_forward` act with the delete
operation fields from Webuzo's forwarder management screen.

### Enduser `ftp_account`, `ftp`, and `editftp`

Used by:

- `addFtpAccount()`
- `editFtpAccount()`
- `deleteFtpAccount()`
- `listFtpAccounts()`
- `changeFtpAccountPassword()`

#### Create FTP account

| Field | Required | Meaning |
| --- | --- | --- |
| `create_acc` | Yes | Set to `1`. |
| `login` | Yes | FTP username. |
| `newpass` | Yes | Password. |
| `conf` | Yes | Confirm password. |
| `ftpdomain` | Yes | Domain that owns the account. |
| `dir` | No | Directory relative to the account root. |
| `quota` | Yes | `unlimited` or `limited`. |
| `quota_limit` | No | Limit in MB when `quota=limited`. |
| `raw` | No | `1` if password is already encrypted. |

Typical call:

```php
Webuzo::enduser()->addFtpAccount([
    'create_acc' => 1,
    'login' => 'deploy',
    'newpass' => 'Strong#Pass123',
    'conf' => 'Strong#Pass123',
    'ftpdomain' => 'example.com',
    'dir' => 'public_html/releases',
    'quota' => 'limited',
    'quota_limit' => 1024,
]);
```

#### Other FTP operations

- `listFtpAccounts()` uses `act=ftp` with no change switch.
- `editFtpAccount()` uses `act=ftp` with edit-specific fields.
- `deleteFtpAccount()` uses `act=ftp` with delete-specific fields.
- `changeFtpAccountPassword()` uses `act=editftp`.

### Enduser `apikey`

Used by:

- `apiKeys()`
- `addApiKey()`
- `deleteApiKey()`

#### Create API key

| Field | Meaning |
| --- | --- |
| `do=1` | Trigger key creation. |
| `ip[]` | Optional allowlist IPs. |
| `notes` | Optional note. |

#### Delete API key

| Field | Meaning |
| --- | --- |
| `del` | API key value to delete. |

Typical create call:

```php
Webuzo::enduser()->addApiKey([
    'do' => 1,
    'ip' => ['203.0.113.10', '203.0.113.11'],
    'notes' => 'CI runner',
]);
```

### Enduser `dbmanage`

Used by:

- `addDatabase()`
- `addDatabaseUser()`
- `addDatabaseUserToDatabase()`
- `deleteDatabase()`

#### Create database

| Field | Required | Meaning |
| --- | --- | --- |
| `submitdb` | Yes | Set to `1`. |
| `db` | Yes | Database name. |

#### Create database user

| Field | Required | Meaning |
| --- | --- | --- |
| `submituserdb` | Yes | Set to `1`. |
| `dbuser` | Yes | Database username. |
| `dbpassword` | Yes | Database user password. |

#### Add user privileges to database

| Field | Required | Meaning |
| --- | --- | --- |
| `submitpri` | Yes | Set to `1`. |
| `dbname` | Yes | Database name. |
| `dbuser` | Yes | Database user. |
| `host` | Yes | Usually `localhost` or `%`. |
| `pri[...]` | Yes | Privilege map such as `SELECT=Y`, `CREATE=Y`, `INSERT=Y`. |

Typical call:

```php
Webuzo::enduser()->addDatabaseUserToDatabase([
    'submitpri' => 1,
    'dbname' => 'app_db',
    'dbuser' => 'app_user',
    'host' => 'localhost',
    'pri' => [
        'SELECT' => 'Y',
        'CREATE' => 'Y',
        'INSERT' => 'Y',
        'UPDATE' => 'Y',
        'DELETE' => 'Y',
    ],
]);
```

#### Delete database

`deleteDatabase()` uses the same `dbmanage` screen with delete-specific fields
targeting the database name you want removed.

### Enduser `cronjob`

Used by:

- `cronJob()`

#### Add cron

| Field | Required | Meaning |
| --- | --- | --- |
| `create_record` | Yes | Set to `1`. |
| `minute` | Yes | Cron minute field. |
| `hour` | Yes | Cron hour field. |
| `day` | Yes | Cron day field. |
| `month` | Yes | Cron month field. |
| `weekday` | Yes | Cron weekday field. |
| `cmd` | Yes | Command to run. |

#### Edit cron

| Field | Required | Meaning |
| --- | --- | --- |
| `edit_record` | Yes | Cron index ID. |
| `minute`, `hour`, `day`, `month`, `weekday`, `cmd` | Yes | Updated schedule and command. |

#### Delete cron

| Field | Required | Meaning |
| --- | --- | --- |
| `delete_record` | Yes | Cron index ID. |

#### Update cron notification email

| Field | Required | Meaning |
| --- | --- | --- |
| `update_cron_email` | Yes | Set to `1`. |
| `email` | Yes | Notification address, or empty string to disable. |

Typical call:

```php
Webuzo::enduser()->cronJob([
    'create_record' => 1,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'weekday' => '*',
    'cmd' => '/usr/local/bin/php /home/example/public_html/artisan schedule:run',
]);
```

### Enduser `install_cert`

Used by:

- `installCertificate()`

| Field | Required | Meaning |
| --- | --- | --- |
| `install_key` | Yes | Set to `1`. |
| `selectdomain` | Yes | Domain name. |
| `kpaste` | Yes | Private key PEM text. |
| `cpaste` | Yes | Certificate PEM text. |
| `bpaste` | No | CA bundle PEM text. |

Typical call:

```php
Webuzo::enduser()->installCertificate([
    'install_key' => 1,
    'selectdomain' => 'example.com',
    'kpaste' => $privateKeyPem,
    'cpaste' => $certificatePem,
    'bpaste' => $caBundlePem,
]);
```

## Admin API reference

### Users

| Method | Act | Purpose |
| --- | --- | --- |
| `listUsers()` | `users` | List users with optional filters and pagination. |
| `addUser()` | `add_user` | Create a hosting account. |
| `editUser()` | `add_user` | Update an existing account through the same screen. |
| `deleteUser()` | `users` | Delete one or more users. |
| `suspendUser()` | `users` | Suspend an account. |
| `unsuspendUser()` | `users` | Unsuspend an account. |
| `resetAccountBandwidthLimit()` | `reset_bandwidth` | Reset an account bandwidth counter. |
| `resourceLimits()` | `resource_limits` | Create, edit, assign, or remove resource limit plans. |
| `singleSignOn()` | `sso` | Generate admin-side single sign-on access. |

### Apps

| Method | Act | Purpose |
| --- | --- | --- |
| `installApps()` | `apps` | Install applications through the admin apps screen. |
| `apachePhpHandlers()` | `apache_php_sapi` | Manage Apache PHP handler configuration. |
| `multiPhpManager()` | `multiphp_manager` | Manage PHP versions per domain/account. |
| `listEapps()` | `eapps` | List installed or available extra apps. |

### DNS

| Method | Act | Purpose |
| --- | --- | --- |
| `createDnsZone()` | `dns_zones` | Create a DNS zone. |
| `deleteDnsZone()` | `dns_zones` | Delete a DNS zone through the same screen. |
| `setDnsZoneTtl()` | `set_ttl` | Change a zone TTL. |
| `listDnsTemplates()` | `dns_template` | List DNS templates. |
| `editDnsTemplate()` | `dns_template` | Update a DNS template. |
| `addARecord()` | `advancedns` | Create a DNS record. |
| `editDnsRecord()` | `advancedns` | Edit a DNS record. |
| `fetchDnsRecordOfDomain()` | `advancedns` | Retrieve records for a domain. |
| `deleteDnsRecord()` | `advancedns` | Delete a DNS record. |
| `listMxRecord()` | `mxentry` | List MX entries. |
| `addMxRecord()` | `mxentry` | Add an MX record. |
| `editMxRecords()` | `mxentry` | Edit existing MX records. |

### Domains

| Method | Act | Purpose |
| --- | --- | --- |
| `parkDomain()` | `park_domain` | Park one domain onto another. |
| `domainForwarding()` | `domain_forwarding` | Configure admin-side domain forwarding. |
| `changeDomainIp()` | `change_domain_ip` | Change the IP for a domain. |
| `assignIpv6Address()` | `change_domain_ip` | Assign IPv6 through the same screen. |
| `listDomains()` | `domains` | List domains managed by the server. |
| `listDomainForwarders()` | `redirect_list` | List domain forwarders. |
| `deleteDomainForwarder()` | `redirect_list` | Delete a domain forwarder. |
| `automaticSsl()` | `automatic_ssl` | Configure automatic SSL behavior. |

### Storage

| Method | Act | Purpose |
| --- | --- | --- |
| `addStorage()` | `addstorage` | Add a storage mount/definition. |
| `editStorage()` | `addstorage` | Edit storage using the same screen. |
| `deleteStorage()` | `storage` | Delete a storage entry. |
| `listStorage()` | `storage` | List storage entries. |

### Networking and settings

| Method | Act | Purpose |
| --- | --- | --- |
| `changeHostname()` | `webuzoconfigs` | Update panel identity, PHP settings, or panel ports. |
| `deleteIps()` | `ips` | Delete IPs from the pool. |
| `rebuildIpsPool()` | `ips` | Rebuild IP pools. |
| `showIpAddressUsage()` | `ips` | Show where an IP is assigned. |
| `resolverConfiguration()` | `resolver` | Manage resolver/DNS resolver settings. |
| `rebrandingSettings()` | `rebranding_settings` | Update rebranding assets and labels. |
| `updateSettings()` | `update_settings` | Manage update behavior/settings. |
| `bruteForceSettings()` | `bruteforce_settings` | Manage brute force protection settings. |
| `mailSettings()` | `mail_settings` | Configure mail service settings. |
| `remoteSmtpServers()` | `remote_smtp_servers` | Manage remote SMTP relays/servers. |

### Email reporting and delivery

| Method | Act | Purpose |
| --- | --- | --- |
| `viewEmailQueue()` | `email_queue` | View queued outbound mail. |
| `emailQueueManager()` | `email_queue_manager` | Operate on queued messages. |
| `emailDeliveryReport()` | `email_delivery_report` | Inspect mail delivery reports. |
| `emailDeliverability()` | `email_deliverability` | Inspect server mail deliverability checks. |

### Security and utilities

| Method | Act | Purpose |
| --- | --- | --- |
| `bandwidth()` | `bandwidth` | View bandwidth statistics. |
| `ipBlock()` | `ipblock` | Block, unblock, or list blocked IPs. |

## Enduser API reference

### Domains

| Method | Act | Purpose |
| --- | --- | --- |
| `createDomain()` | `domainadd` | Create parked, subdomain, or addon domains. |
| `deleteDomain()` | `domainmanage` | Delete one or more domains. |
| `forceHttps()` | `domainmanage` | Enable or disable Force HTTPS. |
| `addRedirect()` | `redirects` | Add a domain redirect. |
| `listRedirects()` | `redirects` | List redirect rules. |
| `deleteRedirect()` | `redirects` | Delete a redirect rule. |

### Email

| Method | Act | Purpose |
| --- | --- | --- |
| `addEmailAccount()` | `add_email_account` | Create a mailbox. |
| `editEmailAccount()` | `add_email_account` | Edit mailbox settings via the same screen. |
| `deleteEmailAccount()` | `email_account` | Delete a mailbox. |
| `addEmailForwarder()` | `email_forward` | Create a mail forwarder. |
| `deleteEmailForwarder()` | `email_forward` | Delete a mail forwarder. |
| `emailDeliverability()` | `email_deliverability` | Inspect mailbox/domain deliverability. |
| `emailSentSummary()` | `email_sent_summary` | View mail sent summary. |
| `trackEmailDelivery()` | `track_email_delivery` | Track delivery of a specific message or account. |

### FTP

| Method | Act | Purpose |
| --- | --- | --- |
| `addFtpAccount()` | `ftp_account` | Create an FTP user. |
| `editFtpAccount()` | `ftp` | Edit an FTP user. |
| `deleteFtpAccount()` | `ftp` | Delete an FTP user. |
| `listFtpAccounts()` | `ftp` | List FTP users. |
| `changeFtpAccountPassword()` | `editftp` | Change an FTP password. |
| `ftpConnections()` | `ftp_connections` | View active FTP connections. |

### Configuration and security

| Method | Act | Purpose |
| --- | --- | --- |
| `apiKeys()` | `apikey` | List API keys. |
| `addApiKey()` | `apikey` | Create a new API key. |
| `deleteApiKey()` | `apikey` | Delete an API key. |
| `multiPhpManager()` | `multi_php` | Manage PHP version selection. |
| `changePassword()` | `changepassword` | Change the enduser password. |
| `ipBlock()` | `ipblock` | Block, unblock, or list blocked IPs. |
| `singleSignOn()` | `sso` | Generate single sign-on access. |

### SSL

| Method | Act | Purpose |
| --- | --- | --- |
| `installRevokeRenewCertificate()` | `acme` | Install, revoke, or renew ACME/Let's Encrypt certificates. |
| `installCertificate()` | `install_cert` | Install a manual certificate and key pair. |

### DNS and database

| Method | Act | Purpose |
| --- | --- | --- |
| `addARecord()` | `advancedns` | Add a DNS record from the enduser panel. |
| `addDatabase()` | `dbmanage` | Create a database. |
| `addDatabaseUser()` | `dbmanage` | Create a database user. |
| `addDatabaseUserToDatabase()` | `dbmanage` | Assign user privileges on a database. |
| `deleteDatabase()` | `dbmanage` | Delete a database. |

### Utilities

| Method | Act | Purpose |
| --- | --- | --- |
| `cronJob()` | `cronjob` | Create, edit, delete, or update cron mail settings. |
| `bandwidth()` | `bandwidth` | View bandwidth statistics. |

## Practical examples

### List users

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::admin()->listUsers([
    'search' => 'client',
    'reslen' => 25,
    'page' => 1,
]);

$users = $response->data['users'] ?? [];
```

### Create an admin user account

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::admin()->addUser([
    'create_user' => 1,
    'user' => 'client01',
    'domain' => 'client01.example.com',
    'user_passwd' => 'Strong#Password1',
    'cnf_user_passwd' => 'Strong#Password1',
    'email' => 'client01@example.com',
    'plan' => 'basic',
    'billing_prefill' => 1,
]);
```

### Delete a user

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::admin()->deleteUser([
    'delete_user' => 'client01',
    'del_sub_acc' => 1,
    'skip_reseller' => 1,
]);
```

### Create an addon domain as enduser

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->createDomain([
    'add' => 1,
    'domain_type' => 'addon',
    'domain' => 'shop.example.com',
    'domainpath' => 'public_html/shop',
    'wildcard' => 0,
    'issue_lecert' => 1,
]);
```

### Enable Force HTTPS

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->forceHttps([
    'force_https' => 1,
    'do' => 1,
    'domain' => 'example.com',
]);
```

### Add a redirect

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->addRedirect([
    'add' => 1,
    'selectdomain' => 'example.com',
    'path' => '',
    'type' => 'temporary',
    'address' => 'https://www.example.com',
]);
```

### Create an email account

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->addEmailAccount([
    'add' => 1,
    'login' => 'support',
    'newpass' => 'Strong#Pass123',
    'conf' => 'Strong#Pass123',
    'domain' => 'example.com',
    'quota' => 'limited',
    'quota_limit' => 2048,
    'incoming' => 'allow',
    'outgoing' => 'allow',
    'allowlogin' => 'allow',
]);
```

### Create an FTP account

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->addFtpAccount([
    'create_acc' => 1,
    'login' => 'deploy',
    'newpass' => 'Strong#Pass123',
    'conf' => 'Strong#Pass123',
    'ftpdomain' => 'example.com',
    'dir' => 'public_html/releases',
    'quota' => 'limited',
    'quota_limit' => 1024,
]);
```

### Create a database and grant privileges

```php
use Webuzo\Facades\Webuzo;

Webuzo::enduser()->addDatabase([
    'submitdb' => 1,
    'db' => 'app_db',
]);

Webuzo::enduser()->addDatabaseUser([
    'submituserdb' => 1,
    'dbuser' => 'app_user',
    'dbpassword' => 'Strong#Pass123',
]);

Webuzo::enduser()->addDatabaseUserToDatabase([
    'submitpri' => 1,
    'dbname' => 'app_db',
    'dbuser' => 'app_user',
    'host' => 'localhost',
    'pri' => [
        'SELECT' => 'Y',
        'CREATE' => 'Y',
        'INSERT' => 'Y',
        'UPDATE' => 'Y',
        'DELETE' => 'Y',
    ],
]);
```

### Add a cron job

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->cronJob([
    'create_record' => 1,
    'minute' => '*/5',
    'hour' => '*',
    'day' => '*',
    'month' => '*',
    'weekday' => '*',
    'cmd' => '/usr/local/bin/php /home/example/public_html/artisan schedule:run',
]);
```

### Install a certificate manually

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->installCertificate([
    'install_key' => 1,
    'selectdomain' => 'example.com',
    'kpaste' => $privateKeyPem,
    'cpaste' => $certificatePem,
    'bpaste' => $caBundlePem,
]);
```

### Create an API key for an enduser

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduser()->addApiKey([
    'do' => 1,
    'ip' => ['203.0.113.10'],
    'notes' => 'Deploy pipeline',
]);
```

### Perform enduser work as admin

```php
use Webuzo\Facades\Webuzo;

$response = Webuzo::enduserAs('soft')->listRedirects();
```

## Notes and recommendations

- Prefer the named wrapper methods when they exist. They keep your code clearer
  and make intent explicit.
- Prefer `call('exact_act_name', $params)` for endpoints not wrapped by the
  package yet.
- Webuzo often returns business errors inside a successful HTTP response. Check
  both `$response->ok()` and `$response->error()`.
- Many actions share the same `act`. The operation is determined by the form
  fields you send, not by the method name alone.
- The package does not rename parameter keys. Send Webuzo field names exactly as
  shown in the examples and tables above.
- Optional fields may vary slightly by Webuzo version, installed modules, panel
  configuration, and whether the account is admin, reseller, or enduser.
