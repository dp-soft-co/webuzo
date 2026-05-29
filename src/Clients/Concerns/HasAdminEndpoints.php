<?php

declare(strict_types=1);

namespace Webuzo\Clients\Concerns;

use Webuzo\Support\ApiResponse;

trait HasAdminEndpoints
{
    public function listUsers(array $params = []): ApiResponse
    {
        return $this->call('users', $params);
    }

    public function addUser(array $params = []): ApiResponse
    {
        return $this->call('add_user', $params);
    }

    public function editUser(array $params = []): ApiResponse
    {
        return $this->call('add_user', $params);
    }

    public function deleteUser(array $params = []): ApiResponse
    {
        return $this->call('users', $params);
    }

    public function suspendUser(array $params = []): ApiResponse
    {
        return $this->call('users', $params);
    }

    public function unsuspendUser(array $params = []): ApiResponse
    {
        return $this->call('users', $params);
    }

    public function resetAccountBandwidthLimit(array $params = []): ApiResponse
    {
        return $this->call('reset_bandwidth', $params);
    }

    public function resourceLimits(array $params = []): ApiResponse
    {
        return $this->call('resource_limits', $params);
    }

    public function singleSignOn(array $params = []): ApiResponse
    {
        return $this->call('sso', $params);
    }

    public function installApps(array $params = []): ApiResponse
    {
        return $this->call('apps', $params);
    }

    public function apachePhpHandlers(array $params = []): ApiResponse
    {
        return $this->call('apache_php_sapi', $params);
    }

    public function multiPhpManager(array $params = []): ApiResponse
    {
        return $this->call('multiphp_manager', $params);
    }

    public function listEapps(array $params = []): ApiResponse
    {
        return $this->call('eapps', $params);
    }

    public function createDnsZone(array $params = []): ApiResponse
    {
        return $this->call('dns_zones', $params);
    }

    public function deleteDnsZone(array $params = []): ApiResponse
    {
        return $this->call('dns_zones', $params);
    }

    public function setDnsZoneTtl(array $params = []): ApiResponse
    {
        return $this->call('set_ttl', $params);
    }

    public function listDnsTemplates(array $params = []): ApiResponse
    {
        return $this->call('dns_template', $params);
    }

    public function editDnsTemplate(array $params = []): ApiResponse
    {
        return $this->call('dns_template', $params);
    }

    public function addARecord(array $params = []): ApiResponse
    {
        return $this->call('advancedns', $params);
    }

    public function editDnsRecord(array $params = []): ApiResponse
    {
        return $this->call('advancedns', $params);
    }

    public function fetchDnsRecordOfDomain(array $params = []): ApiResponse
    {
        return $this->call('advancedns', $params);
    }

    public function deleteDnsRecord(array $params = []): ApiResponse
    {
        return $this->call('advancedns', $params);
    }

    public function listMxRecord(array $params = []): ApiResponse
    {
        return $this->call('mxentry', $params);
    }

    public function addMxRecord(array $params = []): ApiResponse
    {
        return $this->call('mxentry', $params);
    }

    public function editMxRecords(array $params = []): ApiResponse
    {
        return $this->call('mxentry', $params);
    }

    public function parkDomain(array $params = []): ApiResponse
    {
        return $this->call('park_domain', $params);
    }

    public function domainForwarding(array $params = []): ApiResponse
    {
        return $this->call('domain_forwarding', $params);
    }

    public function changeDomainIp(array $params = []): ApiResponse
    {
        return $this->call('change_domain_ip', $params);
    }

    public function assignIpv6Address(array $params = []): ApiResponse
    {
        return $this->call('change_domain_ip', $params);
    }

    public function listDomains(array $params = []): ApiResponse
    {
        return $this->call('domains', $params);
    }

    public function listDomainForwarders(array $params = []): ApiResponse
    {
        return $this->call('redirect_list', $params);
    }

    public function deleteDomainForwarder(array $params = []): ApiResponse
    {
        return $this->call('redirect_list', $params);
    }

    public function automaticSsl(array $params = []): ApiResponse
    {
        return $this->call('automatic_ssl', $params);
    }

    public function addStorage(array $params = []): ApiResponse
    {
        return $this->call('addstorage', $params);
    }

    public function editStorage(array $params = []): ApiResponse
    {
        return $this->call('addstorage', $params);
    }

    public function deleteStorage(array $params = []): ApiResponse
    {
        return $this->call('storage', $params);
    }

    public function listStorage(array $params = []): ApiResponse
    {
        return $this->call('storage', $params);
    }

    public function changeHostname(array $params = []): ApiResponse
    {
        return $this->call('webuzoconfigs', $params);
    }

    public function deleteIps(array $params = []): ApiResponse
    {
        return $this->call('ips', $params);
    }

    public function rebuildIpsPool(array $params = []): ApiResponse
    {
        return $this->call('ips', $params);
    }

    public function showIpAddressUsage(array $params = []): ApiResponse
    {
        return $this->call('ips', $params);
    }

    public function resolverConfiguration(array $params = []): ApiResponse
    {
        return $this->call('resolver', $params);
    }

    public function rebrandingSettings(array $params = []): ApiResponse
    {
        return $this->call('rebranding_settings', $params);
    }

    public function updateSettings(array $params = []): ApiResponse
    {
        return $this->call('update_settings', $params);
    }

    public function bruteForceSettings(array $params = []): ApiResponse
    {
        return $this->call('bruteforce_settings', $params);
    }

    public function mailSettings(array $params = []): ApiResponse
    {
        return $this->call('mail_settings', $params);
    }

    public function remoteSmtpServers(array $params = []): ApiResponse
    {
        return $this->call('remote_smtp_servers', $params);
    }

    public function viewEmailQueue(array $params = []): ApiResponse
    {
        return $this->call('email_queue', $params);
    }

    public function emailQueueManager(array $params = []): ApiResponse
    {
        return $this->call('email_queue_manager', $params);
    }

    public function emailDeliveryReport(array $params = []): ApiResponse
    {
        return $this->call('email_delivery_report', $params);
    }

    public function emailDeliverability(array $params = []): ApiResponse
    {
        return $this->call('email_deliverability', $params);
    }

    public function bandwidth(array $params = []): ApiResponse
    {
        return $this->call('bandwidth', $params);
    }

    public function ipBlock(array $params = []): ApiResponse
    {
        return $this->call('ipblock', $params);
    }
}
