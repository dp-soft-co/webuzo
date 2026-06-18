<?php

declare(strict_types=1);

namespace Webuzo;

use Webuzo\Facades\Webuzo;
use Webuzo\Exceptions\ApiException;
use Webuzo\Exceptions\ValidationException;

class WebuzoService
{
    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    public static function getUsers(array $filters = []): array
    {
        try {
            $response = Webuzo::admin()->listUsers($filters);

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'users' => []];
            }

            $users = [];
            foreach ($response->data['users'] ?? [] as $userData) {
                $users[] = self::formatUserData($userData);
            }

            return ['success' => true, 'total' => count($users), 'users' => $users];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'users' => []];
        }
    }

    public static function getUsersByOwner(string $owner): array
    {
        try {
            $response = Webuzo::admin()->listUsers();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'users' => []];
            }

            $users = [];
            foreach ($response->data['users'] ?? [] as $userData) {
                if (($userData['owner'] ?? '') === $owner) {
                    $users[] = self::formatUserData($userData);
                }
            }

            return ['success' => true, 'total' => count($users), 'owner' => $owner, 'users' => $users];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'users' => []];
        }
    }

    public static function getUser(string $username): array
    {
        try {
            $response = Webuzo::admin()->listUsers();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'user' => null];
            }

            if (isset($response->data['users'][$username])) {
                return ['success' => true, 'user' => self::formatUserData($response->data['users'][$username])];
            }

            return ['success' => false, 'error' => 'User not found', 'user' => null];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'user' => null];
        }
    }

    public static function addUser(array $data): array
    {
        try {
            $response = Webuzo::admin()->addUser($data);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            if (isset($response->data['error'])) {
                return [
                    'success' => false,
                    'message' => is_array($response->data['error'])
                        ? json_encode($response->data['error'])
                        : $response->data['error'],
                ];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'User created successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function editUser(string $username, array $data): array
    {
        try {
            $data['edit_user'] = $username;
            $response = Webuzo::admin()->editUser($data);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function suspendUser(string $username, string $reason = ''): array
    {
        try {
            $params = ['suspend' => $username, 'skip' => 0];
            if ($reason !== '') {
                $params['suspend_reason'] = $reason;
            }

            $response = Webuzo::admin()->suspendUser($params);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'User suspended successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function unsuspendUser(string $username): array
    {
        try {
            $response = Webuzo::admin()->unsuspendUser(['unsuspend' => $username, 'skip' => 0]);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'User unsuspended successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function deleteUser(string $username): array
    {
        try {
            $response = Webuzo::admin()->deleteUser(['delete_user' => $username]);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'User deleted successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Plans & Resources
    // -------------------------------------------------------------------------

    public static function getPackages(): array
    {
        try {
            $response = Webuzo::admin()->plans();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'packages' => []];
            }

            $packages = [];
            foreach ($response->data['plans'] ?? [] as $planName => $planData) {
                $packages[] = [
                    'name'                  => $planName,
                    'slug'                  => $planData['slug'] ?? $planName,
                    'max_disk_limit'        => $planData['max_disk_limit'] ?? 'unlimited',
                    'max_bandwidth_limit'   => $planData['max_bandwidth_limit'] ?? 'unlimited',
                    'max_database'          => $planData['max_database'] ?? 'unlimited',
                    'max_subdomain'         => $planData['max_subdomain'] ?? 'unlimited',
                    'max_addon_domain'      => $planData['max_addon_domain'] ?? 'unlimited',
                    'max_email_account'     => $planData['max_email_account'] ?? 'unlimited',
                    'max_ftp_account'       => $planData['max_ftp_account'] ?? 'unlimited',
                    'reseller'              => $planData['reseller'] ?? '0',
                ];
            }

            return ['success' => true, 'total' => count($packages), 'packages' => $packages];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'packages' => []];
        }
    }

    public static function getPlanNames(): array
    {
        try {
            $response = Webuzo::admin()->resourceLimits();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'plans' => []];
            }

            $plans = [];
            foreach ($response->data['resource_limits'] ?? [] as $limitType => $limitData) {
                $plans[] = [
                    'name'          => $limitType,
                    'title'         => ucfirst(str_replace('_', ' ', $limitType)),
                    'cpu_quota'     => $limitData['cpuquota'] ?? 'N/A',
                    'memory_max'    => $limitData['mem_max'] ?? 'N/A',
                    'max_tasks'     => $limitData['maxtask'] ?? 'N/A',
                    'owner'         => $limitData['owner'] ?? 'root',
                    'users_count'   => count($limitData['users'] ?? []),
                ];
            }

            return ['success' => true, 'total' => count($plans), 'plans' => $plans];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'plans' => []];
        }
    }

    public static function assignResourcePlan(string $username, string $planName): array
    {
        try {
            $response = Webuzo::admin()->resourceLimits([
                'assign_plan' => 1,
                'plan'        => $planName,
                'users[]'     => $username,
            ]);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Resource plan assigned successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function getResellers(): array
    {
        try {
            $response = Webuzo::admin()->listUsers();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'resellers' => []];
            }

            $resellers = [];
            foreach ($response->data['resellers'] ?? [] as $name => $data) {
                $users = self::getUsersByOwner($name);
                $resellers[] = [
                    'name'      => $name,
                    'title'     => $data['title'] ?? $name,
                    'num_users' => $users['success'] ? $users['total'] : 0,
                ];
            }

            return ['success' => true, 'total' => count($resellers), 'resellers' => $resellers];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'resellers' => []];
        }
    }

    // -------------------------------------------------------------------------
    // Server Info & Stats
    // -------------------------------------------------------------------------

    public static function getServerInfo(): array
    {
        try {
            $response = Webuzo::admin()->serverInfo();

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error()];
            }

            $data = $response->data;

            return [
                'success' => true,
                'server' => [
                    'title'        => $data['title'] ?? 'Webuzo Server',
                    'version'      => $data['version'] ?? 'Unknown',
                    'load_average' => $data['load_average'] ?? [],
                    'current_time' => $data['timenow'] ?? time(),
                ],
                'statistics' => [
                    'domains'   => $data['count_data']['domains'] ?? 0,
                    'users'     => $data['count_data']['users'] ?? 0,
                    'databases' => $data['count_data']['dbs'] ?? 0,
                ],
                'resources' => [
                    'cpu' => [
                        'model'   => $data['usage']['cpu']['model_name'] ?? 'N/A',
                        'cores'   => $data['usage']['cpu']['core_count'] ?? 0,
                        'percent' => $data['usage']['cpu']['percent'] ?? 0,
                    ],
                    'ram' => [
                        'limit' => $data['usage']['ram']['limit'] ?? 0,
                        'used'  => $data['usage']['ram']['used'] ?? 0,
                        'free'  => $data['usage']['ram']['free'] ?? 0,
                    ],
                    'disk' => $data['usage']['disk'] ?? [],
                ],
            ];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function getStats(): array
    {
        $result = self::getUsers();

        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }

        $active = 0;
        $suspended = 0;
        $owners = [];

        foreach ($result['users'] as $user) {
            $user['status'] === 'suspended' ? $suspended++ : $active++;
            $owners[$user['owner']] = ($owners[$user['owner']] ?? 0) + 1;
        }

        return [
            'success'         => true,
            'total_users'     => count($result['users']),
            'active_users'    => $active,
            'suspended_users' => $suspended,
            'owners'          => $owners,
        ];
    }

    // -------------------------------------------------------------------------
    // SSL Certificates
    // -------------------------------------------------------------------------

    public static function listCertificates(string $username): array
    {
        try {
            $response = Webuzo::enduserAs($username)->installRevokeRenewCertificate([]);

            if (!$response->ok()) {
                return ['success' => false, 'error' => $response->error(), 'domains' => []];
            }

            $allDomains     = $response->data['domains'] ?? [];
            $installedCerts = $response->data['install_lelist'] ?? [];
            $domains        = [];

            foreach ($allDomains as $domainName => $domainInfo) {
                $certInfo  = $installedCerts[$domainName]['cert_info'] ?? null;
                $domains[] = [
                    'domain'       => $domainName,
                    'ssl_installed'=> $certInfo !== null,
                    'issuer'       => $certInfo['issuer'] ?? null,
                    'valid_from'   => $certInfo['val_from'] ?? null,
                    'valid_till'   => $certInfo['val_till'] ?? null,
                    'next_renew'   => $certInfo['next_renew'] ?? null,
                ];
            }

            return ['success' => true, 'total' => count($domains), 'domains' => $domains];
        } catch (ValidationException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'domains' => []];
        } catch (ApiException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'domains' => []];
        }
    }

    public static function createCertificate(string $username, string $domain): array
    {
        return self::manageCertificate($username, $domain, 'install_cert');
    }

    public static function revokeCertificate(string $username, string $domain): array
    {
        return self::manageCertificate($username, $domain, 'revoke_cert');
    }

    public static function renewCertificate(string $username, string $domain): array
    {
        return self::manageCertificate($username, $domain, 'renew_cert');
    }

    private static function manageCertificate(string $username, string $domain, string $action): array
    {
        try {
            $response = Webuzo::enduserAs($username)->installRevokeRenewCertificate([
                $action  => 1,
                'domain' => [$domain],
            ]);

            if (!$response->ok() || isset($response->data['error'])) {
                return [
                    'success' => false,
                    'message' => is_array($response->data['error'] ?? null)
                        ? implode(', ', $response->data['error'])
                        : ($response->data['error'] ?? $response->error()),
                ];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Certificate action completed'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Force HTTPS
    // -------------------------------------------------------------------------

    public static function forceHttps(string $username, string $domain, bool $enable = true): array
    {
        try {
            $response = Webuzo::enduserAs($username)->forceHttps([
                'force_https' => 1,
                'do'          => $enable ? 1 : 0,
                'domain'      => $domain,
            ]);

            if (!$response->ok() || isset($response->data['error'])) {
                return [
                    'success' => false,
                    'message' => is_array($response->data['error'] ?? null)
                        ? implode(', ', $response->data['error'])
                        : ($response->data['error'] ?? $response->error()),
                ];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? ($enable ? 'Force HTTPS enabled' : 'Force HTTPS disabled')];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Databases
    // -------------------------------------------------------------------------

    public static function createDatabaseWithUser(string $username, string $dbName, string $dbUser, string $dbPassword): array
    {
        try {
            $prefix        = $username . '_';
            $allPrivileges = ['SELECT' => 'Y', 'CREATE' => 'Y', 'INSERT' => 'Y', 'UPDATE' => 'Y', 'ALTER' => 'Y', 'DELETE' => 'Y', 'INDEX' => 'Y', 'CREATE_TEMPORARY_TABLES' => 'Y', 'EXECUTE' => 'Y', 'DROP' => 'Y', 'LOCK_TABLES' => 'Y', 'REFERENCES' => 'Y', 'CREATE_ROUTINE' => 'Y', 'ALTER_ROUTINE' => 'Y', 'EVENT' => 'Y', 'CREATE_VIEW' => 'Y', 'SHOW_VIEW' => 'Y', 'TRIGGER' => 'Y'];

            $dbResponse = Webuzo::enduserAs($username)->addDatabase(['submitdb' => 1, 'db' => $dbName]);
            if (!$dbResponse->ok() || isset($dbResponse->data['error'])) {
                return ['success' => false, 'step' => 'create_database', 'message' => self::extractError($dbResponse)];
            }

            $userResponse = Webuzo::enduserAs($username)->addDatabaseUser(['submituserdb' => 1, 'dbuser' => $dbUser, 'dbpassword' => $dbPassword]);
            if (!$userResponse->ok() || isset($userResponse->data['error'])) {
                return ['success' => false, 'step' => 'create_user', 'message' => self::extractError($userResponse), 'database_created' => true];
            }

            $assignResponse = Webuzo::enduserAs($username)->addDatabaseUserToDatabase([
                'submitpri' => 1,
                'dbname'    => $prefix . $dbName,
                'dbuser'    => $prefix . $dbUser,
                'host'      => 'localhost',
                'pri'       => $allPrivileges,
            ]);
            if (!$assignResponse->ok() || isset($assignResponse->data['error'])) {
                return ['success' => false, 'step' => 'assign_privileges', 'message' => self::extractError($assignResponse), 'database_created' => true, 'user_created' => true];
            }

            return [
                'success'  => true,
                'message'  => 'Database, user created and privileges assigned successfully',
                'database' => $prefix . $dbName,
                'db_user'  => $prefix . $dbUser,
            ];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Domains
    // -------------------------------------------------------------------------

    public static function addDomain(string $username, string $domain, string $domainType = 'parked', string $domainPath = '', string $subdomain = '', bool $issueSSL = false): array
    {
        try {
            $params = [
                'add'          => 1,
                'domain_type'  => $domainType,
                'domain'       => $domain,
                'wildcard'     => 0,
                'issue_lecert' => $issueSSL ? 1 : 0,
            ];

            if ($domainType === 'addon' || $domainType === 'subdomain') {
                $params['domainpath'] = $domainPath ?: 'public_html/' . str_replace('.', '_', $domain);
            }

            if ($domainType === 'subdomain') {
                $params['subdomain'] = $subdomain ?: explode('.', $domain)[0];
            }

            $response = Webuzo::enduserAs($username)->createDomain($params);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Domain added successfully', 'domain' => $domain];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function deleteDomain(string $username, string $domain): array
    {
        try {
            $response = Webuzo::enduserAs($username)->deleteDomain(['delete' => $domain]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Domain deleted successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Email Accounts
    // -------------------------------------------------------------------------

    public static function createEmailAccount(string $username, string $email, string $domain, string $password, string $quota = 'unlimited', int $quotaLimit = 1024): array
    {
        try {
            $response = Webuzo::enduserAs($username)->addEmailAccount([
                'add'         => 1,
                'login'       => $email,
                'domain'      => $domain,
                'newpass'     => $password,
                'conf'        => $password,
                'quota'       => $quota,
                'quota_limit' => $quotaLimit,
                'incoming'    => 'allow',
                'outgoing'    => 'allow',
                'allowlogin'  => 'allow',
            ]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Email account created successfully', 'email' => $email . '@' . $domain];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function deleteEmailAccount(string $username, string $fullEmail): array
    {
        try {
            $response = Webuzo::enduserAs($username)->deleteEmailAccount(['delete' => $fullEmail]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Email account deleted successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // Cron Jobs
    // -------------------------------------------------------------------------

    public static function listCronJobs(string $username): array
    {
        try {
            $response = Webuzo::enduserAs($username)->cronJob([]);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error(), 'crons' => []];
            }

            return ['success' => true, 'crons' => $response->data['cron_list'] ?? []];
        } catch (ApiException | ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'crons' => []];
        }
    }

    public static function createCronJob(string $username, string $cmd, string $minute = '*', string $hour = '*', string $day = '*', string $month = '*', string $weekday = '*'): array
    {
        try {
            $response = Webuzo::enduserAs($username)->cronJob([
                'create_record' => 1,
                'cmd'           => $cmd,
                'minute'        => $minute,
                'hour'          => $hour,
                'day'           => $day,
                'month'         => $month,
                'weekday'       => $weekday,
            ]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Cron job created successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function editCronJob(string $username, int $cronId, string $cmd, string $minute = '*', string $hour = '*', string $day = '*', string $month = '*', string $weekday = '*'): array
    {
        try {
            $response = Webuzo::enduserAs($username)->cronJob([
                'edit_record' => $cronId,
                'cmd'         => $cmd,
                'minute'      => $minute,
                'hour'        => $hour,
                'day'         => $day,
                'month'       => $month,
                'weekday'     => $weekday,
            ]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Cron job updated successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    public static function deleteCronJob(string $username, int $cronId): array
    {
        try {
            $response = Webuzo::enduserAs($username)->cronJob(['delete_record' => $cronId]);

            if (!$response->ok() || isset($response->data['error'])) {
                return ['success' => false, 'message' => self::extractError($response)];
            }

            return ['success' => true, 'message' => $response->data['done']['msg'] ?? 'Cron job deleted successfully'];
        } catch (ValidationException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['context' => $e->getContext(), 'type' => 'validation']];
        } catch (ApiException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'error_details' => ['type' => 'api']];
        }
    }

    // -------------------------------------------------------------------------
    // SSO (Single Sign-On)
    // -------------------------------------------------------------------------

    public static function adminSsoUrl(): array
    {
        $config  = config('webuzo');
        $baseUrl = $config['scheme'] . '://' . rtrim((string) $config['host'], '/') . ':' . $config['admin_port'] . '/index.php?api=json&act=sso';

        return self::requestSso($baseUrl, $config);
    }

    public static function userSsoUrl(string $username): array
    {
        $config  = config('webuzo');
        $baseUrl = $config['scheme'] . '://' . rtrim((string) $config['host'], '/') . ':' . $config['enduser_port'] . '/index.php?loginAs=' . rawurlencode($username) . '&api=json&act=sso';

        return self::requestSso($baseUrl, $config);
    }

    private static function requestSso(string $baseUrl, array $config): array
    {
        try {
            $auth    = $config['auth'];
            $ssoUser = !empty($auth['username']) ? $auth['username'] : $auth['api_user'];
            $ssoPass = !empty($auth['password']) ? $auth['password'] : $auth['api_key'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERPWD, $ssoUser . ':' . $ssoPass);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt_array($ch, [
                CURLOPT_URL            => $baseUrl,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query(['noip' => 1]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => $config['connect_timeout'],
                CURLOPT_TIMEOUT        => $config['timeout'],
            ]);

            $resp  = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => $error];
            }

            $res = json_decode($resp, true);

            if (!empty($res['done']['url'])) {
                return ['success' => true, 'url' => $res['done']['url']];
            }

            return [
                'success' => false,
                'message' => is_array($res['error'] ?? null) ? implode(', ', $res['error']) : ($res['error'] ?? 'Unknown error'),
                'raw'     => $res,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function extractError(mixed $response): string
    {
        return is_array($response->data['error'] ?? null)
            ? implode(', ', $response->data['error'])
            : ($response->data['error'] ?? $response->error());
    }

    private static function formatUserData(array $userData): array
    {
        return [
            'username' => $userData['user'] ?? 'N/A',
            'domain'   => $userData['domain'] ?? 'N/A',
            'email'    => $userData['email'] ?? 'N/A',
            'status'   => $userData['status'] ?? 'active',
            'owner'    => $userData['owner'] ?? 'N/A',
            'type'     => $userData['type'] ?? '1',
            'plan'     => $userData['plan'] ?? 'N/A',
            'created'  => $userData['created'] ?? null,
            'ip'       => $userData['ip'] ?? 'N/A',
            'resources' => [
                'disk'          => $userData['resource']['disk'] ?? [],
                'bandwidth'     => $userData['resource']['bandwidth'] ?? [],
                'email_accounts'=> $userData['resource']['email_account'] ?? [],
                'databases'     => $userData['resource']['db'] ?? [],
            ],
        ];
    }
}
