<?php

declare(strict_types=1);

namespace Webuzo\Clients\Concerns;

use Webuzo\Support\ApiResponse;

trait HasEnduserEndpoints
{
    public function createDomain(array $params = []): ApiResponse
    {
        $this->validateRequired($params, ['add', 'domain_type', 'domain', 'wildcard', 'issue_lecert'], 'createDomain');
        $this->validateOneOf($params, 'domain_type', ['parked', 'subdomain', 'addon'], 'createDomain');

        if (($params['domain_type'] ?? '') === 'subdomain') {
            $this->validateRequired($params, ['domainpath', 'subdomain'], 'createDomain');
        }

        if (($params['domain_type'] ?? '') === 'addon') {
            $this->validateRequired($params, ['domainpath'], 'createDomain');
        }

        return $this->call('domainadd', $params);
    }

    public function deleteDomain(array $params = []): ApiResponse
    {
        return $this->call('domainmanage', $params);
    }

    public function forceHttps(array $params = []): ApiResponse
    {
        return $this->call('domainmanage', $params);
    }

    public function addRedirect(array $params = []): ApiResponse
    {
        return $this->call('redirects', $params);
    }

    public function listRedirects(array $params = []): ApiResponse
    {
        return $this->call('redirects', $params);
    }

    public function deleteRedirect(array $params = []): ApiResponse
    {
        return $this->call('redirects', $params);
    }

    public function addEmailAccount(array $params = []): ApiResponse
    {
        $this->validateRequired($params, ['add', 'login', 'newpass', 'conf', 'domain'], 'addEmailAccount');
        $this->validateOneOf($params, 'quota', ['unlimited', 'limited', ''], 'addEmailAccount');
        return $this->call('add_email_account', $params);
    }

    public function editEmailAccount(array $params = []): ApiResponse
    {
        return $this->call('add_email_account', $params);
    }

    public function deleteEmailAccount(array $params = []): ApiResponse
    {
        return $this->call('email_account', $params);
    }

    public function addEmailForwarder(array $params = []): ApiResponse
    {
        return $this->call('email_forward', $params);
    }

    public function deleteEmailForwarder(array $params = []): ApiResponse
    {
        return $this->call('email_forward', $params);
    }

    public function emailDeliverability(array $params = []): ApiResponse
    {
        return $this->call('email_deliverability', $params);
    }

    public function emailSentSummary(array $params = []): ApiResponse
    {
        return $this->call('email_sent_summary', $params);
    }

    public function trackEmailDelivery(array $params = []): ApiResponse
    {
        return $this->call('track_email_delivery', $params);
    }

    public function addFtpAccount(array $params = []): ApiResponse
    {
        return $this->call('ftp_account', $params);
    }

    public function editFtpAccount(array $params = []): ApiResponse
    {
        return $this->call('ftp', $params);
    }

    public function deleteFtpAccount(array $params = []): ApiResponse
    {
        return $this->call('ftp', $params);
    }

    public function listFtpAccounts(array $params = []): ApiResponse
    {
        return $this->call('ftp', $params);
    }

    public function changeFtpAccountPassword(array $params = []): ApiResponse
    {
        return $this->call('editftp', $params);
    }

    public function ftpConnections(array $params = []): ApiResponse
    {
        return $this->call('ftp_connections', $params);
    }

    public function apiKeys(array $params = []): ApiResponse
    {
        return $this->call('apikey', $params);
    }

    public function addApiKey(array $params = []): ApiResponse
    {
        return $this->call('apikey', $params);
    }

    public function deleteApiKey(array $params = []): ApiResponse
    {
        return $this->call('apikey', $params);
    }

    public function multiPhpManager(array $params = []): ApiResponse
    {
        return $this->call('multi_php', $params);
    }

    public function changePassword(array $params = []): ApiResponse
    {
        return $this->call('changepassword', $params);
    }

    public function ipBlock(array $params = []): ApiResponse
    {
        return $this->call('ipblock', $params);
    }

    public function singleSignOn(array $params = []): ApiResponse
    {
        return $this->call('sso', $params);
    }

    public function installRevokeRenewCertificate(array $params = []): ApiResponse
    {
        return $this->call('acme', $params);
    }

    public function installCertificate(array $params = []): ApiResponse
    {
        return $this->call('install_cert', $params);
    }

    public function addARecord(array $params = []): ApiResponse
    {
        return $this->call('advancedns', $params);
    }

    public function addDatabase(array $params = []): ApiResponse
    {
        return $this->call('dbmanage', $params);
    }

    public function addDatabaseUser(array $params = []): ApiResponse
    {
        return $this->call('dbmanage', $params);
    }

    public function addDatabaseUserToDatabase(array $params = []): ApiResponse
    {
        return $this->call('dbmanage', $params);
    }

    public function deleteDatabase(array $params = []): ApiResponse
    {
        return $this->call('dbmanage', $params);
    }

    public function cronJob(array $params = []): ApiResponse
    {
        return $this->call('cronjob', $params);
    }

    public function bandwidth(array $params = []): ApiResponse
    {
        return $this->call('bandwidth', $params);
    }
}
