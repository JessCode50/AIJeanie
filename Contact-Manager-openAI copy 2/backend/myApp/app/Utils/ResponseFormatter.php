<?php

namespace App\Utils;

class ResponseFormatter
{
    public function formatSuccessMessage($function, $parameters)
    {
        $baseMessage = "✅ Successfully executed: " . $this->getFunctionDisplayName($function);
        
        if (!empty($parameters)) {
            $paramString = $this->formatParameters($parameters);
            $baseMessage .= "\n📋 Parameters: " . $paramString;
        }
        
        return $baseMessage;
    }

    public function formatListAccounts($accountsData)
    {
        if (!isset($accountsData['data']['acct']) || !is_array($accountsData['data']['acct'])) {
            return "❌ Unable to retrieve accounts data";
        }

        $accounts = $accountsData['data']['acct'];
        $formatted = "🖥️ **Hosting Accounts**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "🌐 **Server:** cPanel Hosting Server\n";
        $formatted .= "📊 **Total Accounts:** " . count($accounts) . "\n\n";
        
        if (count($accounts) === 0) {
            $formatted .= "📭 **No hosting accounts found**\n\n";
            $formatted .= "💡 **Status:** No hosting accounts are currently configured on this server\n";
            $formatted .= "🔧 **Suggestion:** Use 'create account' to add new hosting accounts\n";
        } else {
            foreach ($accounts as $index => $account) {
                $formatted .= "🔹 **Account #" . ($index + 1) . "**\n";
                $formatted .= "   • **Domain:** " . ($account['domain'] ?? 'N/A') . "\n";
                $formatted .= "   • **User:** " . ($account['user'] ?? 'N/A') . "\n";
                $formatted .= "   • **Email:** " . ($account['email'] ?? 'N/A') . "\n";
                $formatted .= "   • **Package:** " . ($account['plan'] ?? 'N/A') . "\n";
                $formatted .= "   • **Disk Used:** " . $this->formatDiskUsage($account['diskused'] ?? 'N/A') . "\n";
                $formatted .= "   • **Created:** " . $this->formatDate($account['startdate'] ?? 'N/A') . "\n";
                
                if ($index < count($accounts) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatClientInfo($clientData)
    {
        if (!isset($clientData['result']) || $clientData['result'] !== 'success') {
            return "❌ Unable to retrieve client information";
        }

        $client = $clientData['client'] ?? $clientData;
        $formatted = "👤 **Client Information**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        
        $formatted .= "🆔 **Client ID:** " . ($client['client_id'] ?? 'N/A') . "\n";
        $formatted .= "👤 **Name:** " . ($client['fullname'] ?? 'N/A') . "\n";
        $formatted .= "🏢 **Company:** " . ($client['companyname'] ?? 'N/A') . "\n";
        $formatted .= "📧 **Email:** " . ($client['email'] ?? 'N/A') . "\n";
        $formatted .= "📞 **Phone:** " . ($client['phonenumberformatted'] ?? $client['phonenumber'] ?? 'N/A') . "\n";
        $formatted .= "✅ **Status:** " . $this->formatStatus($client['status'] ?? 'N/A') . "\n\n";
        
        $formatted .= "🔹 **Contact Details**\n";
        $formatted .= "   • **Street:** " . ($client['address1'] ?? 'N/A') . "\n";
        if (!empty($client['address2'])) {
            $formatted .= "   • **Address 2:** " . $client['address2'] . "\n";
        }
        $formatted .= "   • **City:** " . ($client['city'] ?? 'N/A') . "\n";
        $formatted .= "   • **State/Province:** " . ($client['state'] ?? 'N/A') . "\n";
        $formatted .= "   • **Postal Code:** " . ($client['postcode'] ?? 'N/A') . "\n";
        $formatted .= "   • **Country:** " . ($client['countryname'] ?? $client['country'] ?? 'N/A') . "\n";
        $formatted .= "   ───────────────────────────────────\n\n";
        
        $formatted .= "🔹 **Account Settings**\n";
        $formatted .= "   • **Currency:** " . ($client['currency_code'] ?? 'N/A') . "\n";
        $formatted .= "   • **Credit Balance:** $" . number_format(floatval($client['credit'] ?? 0), 2) . "\n";
        $formatted .= "   • **Tax Exempt:** " . ($client['taxexempt'] ? '✅ Yes' : '❌ No') . "\n";
        $formatted .= "   • **Email Opt-out:** " . ($client['emailoptout'] ? '✅ Yes' : '❌ No') . "\n";
        $formatted .= "   • **Marketing Emails:** " . ($client['marketing_emails_opt_in'] ? '✅ Opted In' : '❌ Opted Out') . "\n";
        
        if (!empty($client['lastlogin'])) {
            $formatted .= "   ───────────────────────────────────\n\n";
            $formatted .= "🔹 **Last Login**\n";
            $formatted .= "   • " . str_replace(['<br>', "\n"], ["\n   • ", "\n   • "], $client['lastlogin']) . "\n";
        }
        
        $formatted .= "\n";
        
        return $formatted;
    }

    public function formatEmailList($emailData)
    {
        if (!isset($emailData['result']['data']) || !is_array($emailData['result']['data'])) {
            return "❌ Unable to retrieve email list";
        }

        $emails = $emailData['result']['data'];
        $formatted = "📧 **Email Accounts**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "👤 **cPanel User:** " . ($emailData['cpanel_user'] ?? 'N/A') . "\n";
        $formatted .= "📊 **Total Email Accounts:** " . count($emails) . "\n\n";
        
        if (count($emails) === 0) {
            $formatted .= "📭 **No email accounts found**\n\n";
            $formatted .= "💡 **Status:** No email accounts are currently configured for this user\n";
            $formatted .= "🔧 **Suggestion:** Use 'add email account' to create new email addresses\n";
        } else {
            foreach ($emails as $index => $email) {
                $formatted .= "🔹 **Email #" . ($index + 1) . "**\n";
                $formatted .= "   • **Address:** " . ($email['email'] ?? 'N/A') . "\n";
                $formatted .= "   • **Domain:** " . ($email['domain'] ?? 'N/A') . "\n";
                $formatted .= "   • **Quota:** " . $this->formatQuota($email['quota'] ?? 'N/A') . "\n";
                $formatted .= "   • **Usage:** " . $this->formatDiskUsage($email['diskused'] ?? 'N/A') . "\n";
                
                if ($index < count($emails) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatInvoices($invoiceData)
    {
        if (!isset($invoiceData['invoices']) || !is_array($invoiceData['invoices'])) {
            return "❌ Unable to retrieve invoices";
        }

        $invoices = $invoiceData['invoices'];
        $formatted = "🧾 **Client Invoices**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "👤 **Client ID:** " . ($invoiceData['client_id'] ?? 'N/A') . "\n";
        $formatted .= "📊 **Total Invoices:** " . count($invoices) . "\n\n";
        
        if (count($invoices) === 0) {
            $formatted .= "📭 **No invoices found**\n\n";
            $formatted .= "💡 **Status:** No invoices have been generated for this client\n";
            $formatted .= "🔧 **Suggestion:** Check if this client has any active services or billing history\n";
        } else {
            foreach ($invoices as $index => $invoice) {
                $formatted .= "🔹 **Invoice #" . ($invoice['id'] ?? 'N/A') . "**\n";
                $formatted .= "   • **Date:** " . $this->formatDate($invoice['date'] ?? 'N/A') . "\n";
                $formatted .= "   • **Due Date:** " . $this->formatDate($invoice['duedate'] ?? 'N/A') . "\n";
                $formatted .= "   • **Total:** " . ($invoice['total'] ?? 'N/A') . "\n";
                $formatted .= "   • **Status:** " . $this->formatInvoiceStatus($invoice['status'] ?? 'N/A') . "\n";
                
                if ($index < count($invoices) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatEmailCount($countData, $cpanelUser)
    {
        if (!isset($countData['result']['data']) || !is_numeric($countData['result']['data'])) {
            return "❌ Unable to retrieve email count for user: {$cpanelUser}";
        }

        $count = intval($countData['result']['data']);
        $formatted = "📧 **Email Account Count**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "👤 **cPanel User:** {$cpanelUser}\n";
        $formatted .= "📊 **Total Email Accounts:** {$count}\n\n";
        
        if ($count === 0) {
            $formatted .= "💡 **Status:** No email accounts found\n";
            $formatted .= "🔧 **Suggestion:** Use 'add email account' to create new email addresses\n";
        } elseif ($count === 1) {
            $formatted .= "✅ **Status:** 1 email account configured\n";
        } else {
            $formatted .= "✅ **Status:** {$count} email accounts configured\n";
        }
        
        return $formatted;
    }

    public function formatEmailCreation($creationData, $cpanelUser, $emailUser)
    {
        if (!isset($creationData['result']['data']) || !is_array($creationData['result']['data'])) {
            $errorMsg = "❌ **Email Account Creation Failed**\n";
            $errorMsg .= "═══════════════════════════════════════\n\n";
            $errorMsg .= "👤 **cPanel User:** {$cpanelUser}\n";
            $errorMsg .= "📧 **Email User:** {$emailUser}\n\n";
            
            if (isset($creationData['error'])) {
                $errorMsg .= "🔍 **Error:** " . $creationData['error'] . "\n";
            } elseif (isset($creationData['data']['reason'])) {
                $errorMsg .= "🔍 **Error:** " . $creationData['data']['reason'] . "\n";
            } else {
                $errorMsg .= "🔍 **Error:** Unknown error occurred\n";
            }
            
            $errorMsg .= "💡 **Suggestion:** Please verify the cPanel username exists and the email username is not already taken\n";
            return $errorMsg;
        }

        $result = $creationData['result']['data'];
        
        if (isset($result['result']) && $result['result'] === '1') {
            $formatted = "✅ **Email Account Created Successfully**\n";
            $formatted .= "═══════════════════════════════════════\n\n";
            $formatted .= "👤 **cPanel User:** {$cpanelUser}\n";
            $formatted .= "📧 **New Email:** {$emailUser}@[domain]\n";
            $formatted .= "🔐 **Password:** [Hidden for security]\n\n";
            $formatted .= "✉️ **Status:** Email account is now active and ready to use\n";
            $formatted .= "🔧 **Next Steps:** Configure email client with these settings\n";
        } else {
            $formatted = "❌ **Email Account Creation Failed**\n";
            $formatted .= "═══════════════════════════════════════\n\n";
            $formatted .= "👤 **cPanel User:** {$cpanelUser}\n";
            $formatted .= "📧 **Email User:** {$emailUser}\n\n";
            $formatted .= "🔍 **Error:** " . ($result['reason'] ?? 'Creation failed') . "\n";
            $formatted .= "💡 **Suggestion:** Please check the username and try again\n";
        }
        
        return $formatted;
    }

    public function formatEmailForwarders($forwarderData, $cpanelUser, $domain)
    {
        if (!isset($forwarderData['result']['data']) || !is_array($forwarderData['result']['data'])) {
            return "❌ Unable to retrieve email forwarders for {$domain}";
        }

        $forwarders = $forwarderData['result']['data'];
        $formatted = "📧 **Email Forwarders**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "👤 **cPanel User:** {$cpanelUser}\n";
        $formatted .= "🌐 **Domain:** {$domain}\n";
        $formatted .= "📊 **Total Forwarders:** " . count($forwarders) . "\n\n";
        
        if (count($forwarders) === 0) {
            $formatted .= "📭 **No email forwarders found**\n\n";
            $formatted .= "💡 **Status:** No email forwarding rules are currently configured for this domain\n";
            $formatted .= "🔧 **Suggestion:** Use 'add email forwarder' to create forwarding rules\n";
        } else {
            foreach ($forwarders as $index => $forwarder) {
                $formatted .= "🔹 **Forwarder #" . ($index + 1) . "**\n";
                $formatted .= "   • **From:** " . ($forwarder['dest'] ?? 'N/A') . "\n";
                $formatted .= "   • **To:** " . ($forwarder['forward'] ?? 'N/A') . "\n";
                
                if (isset($forwarder['html_dest'])) {
                    $formatted .= "   • **Type:** HTML Destination\n";
                }
                
                if ($index < count($forwarders) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatPackageList($packageData)
    {
        if (!isset($packageData['data']['pkg']) || !is_array($packageData['data']['pkg'])) {
            return "❌ Unable to retrieve package list";
        }

        $packages = $packageData['data']['pkg'];
        $formatted = "📦 **Hosting Packages**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "🌐 **Server:** cPanel Hosting Server\n";
        $formatted .= "📊 **Total Packages:** " . count($packages) . "\n\n";
        
        if (count($packages) === 0) {
            $formatted .= "📭 **No hosting packages found**\n\n";
            $formatted .= "💡 **Status:** No hosting packages are currently configured on this server\n";
            $formatted .= "🔧 **Suggestion:** Contact system administrator to configure hosting packages\n";
        } else {
            foreach ($packages as $index => $package) {
                $formatted .= "🔹 **Package #" . ($index + 1) . "**\n";
                $formatted .= "   • **Name:** " . ($package['name'] ?? 'N/A') . "\n";
                $formatted .= "   • **Disk Quota:** " . $this->formatQuota($package['QUOTA'] ?? 'N/A') . "\n";
                $formatted .= "   • **Bandwidth:** " . $this->formatQuota($package['BWLIMIT'] ?? 'N/A') . "\n";
                $formatted .= "   • **Max Domains:** " . ($package['MAXADDON'] ?? 'N/A') . "\n";
                $formatted .= "   • **Max Subdomains:** " . ($package['MAXSUB'] ?? 'N/A') . "\n";
                $formatted .= "   • **Max Email:** " . ($package['MAXPOP'] ?? 'N/A') . "\n";
                
                if ($index < count($packages) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatDomainInfo($domainData)
    {
        if (!isset($domainData['data']) || !is_array($domainData['data'])) {
            return "❌ Unable to retrieve domain information";
        }

        $domains = $domainData['data'];
        $formatted = "🌐 **Domain Information**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "🖥️ **Server:** cPanel Hosting Server\n";
        $formatted .= "📊 **Total Domains:** " . count($domains) . "\n\n";
        
        if (count($domains) === 0) {
            $formatted .= "📭 **No domains found**\n\n";
            $formatted .= "💡 **Status:** No domains are currently configured on this server\n";
            $formatted .= "🔧 **Suggestion:** Add hosting accounts with domains to see domain information\n";
        } else {
            foreach ($domains as $index => $domain) {
                $formatted .= "🔹 **Domain #" . ($index + 1) . "**\n";
                $formatted .= "   • **Domain:** " . ($domain['domain'] ?? 'N/A') . "\n";
                $formatted .= "   • **Type:** " . ($domain['type'] ?? 'N/A') . "\n";
                $formatted .= "   • **Owner:** " . ($domain['user'] ?? 'N/A') . "\n";
                $formatted .= "   • **Document Root:** " . ($domain['docroot'] ?? 'N/A') . "\n";
                
                if ($index < count($domains) - 1) {
                    $formatted .= "   ───────────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    private function getFunctionDisplayName($function)
    {
        $displayNames = [
            'get_domain_info' => 'Domain Information Retrieval',
            'listaccts' => 'Account List Retrieval',
            'listpkgs' => 'Package List Retrieval',
            'count_pops' => 'Email Account Count',
            'createacct' => 'Account Creation',
            'delete_pop' => 'Email Account Deletion',
            'list_pops' => 'Email Account List',
            'add_pop' => 'Email Account Creation',
            'list_forwarders' => 'Email Forwarders List',
            'add_forwarder' => 'Email Forwarder Creation',
            'client' => 'Client Information Retrieval',
            'invoices' => 'Invoice Retrieval'
        ];

        return $displayNames[$function] ?? ucfirst(str_replace('_', ' ', $function));
    }

    private function formatParameters($parameters)
    {
        $formatted = [];
        foreach ($parameters as $key => $value) {
            if (is_string($value) && strlen($value) > 30) {
                $value = substr($value, 0, 30) . '...';
            }
            $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }
        return implode(', ', $formatted);
    }

    private function formatDiskUsage($diskUsed)
    {
        if ($diskUsed === 'N/A' || !is_numeric($diskUsed)) {
            return $diskUsed;
        }
        
        $bytes = floatval($diskUsed);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function formatQuota($quota)
    {
        if ($quota === 'unlimited' || $quota === '0') {
            return '♾️ Unlimited';
        }
        return $this->formatDiskUsage($quota);
    }

    private function formatDate($dateString)
    {
        if ($dateString === 'N/A' || empty($dateString)) {
            return $dateString;
        }
        
        try {
            $date = new \DateTime($dateString);
            return $date->format('M j, Y g:i A');
        } catch (Exception $e) {
            return $dateString;
        }
    }

    private function formatStatus($status)
    {
        $statusEmojis = [
            'Active' => '✅ Active',
            'Inactive' => '❌ Inactive',
            'Suspended' => '⏸️ Suspended',
            'Closed' => '🔒 Closed'
        ];

        return $statusEmojis[$status] ?? $status;
    }

    private function formatInvoiceStatus($status)
    {
        $statusEmojis = [
            'Paid' => '✅ Paid',
            'Unpaid' => '⏳ Unpaid',
            'Overdue' => '🚨 Overdue',
            'Cancelled' => '❌ Cancelled',
            'Refunded' => '↩️ Refunded'
        ];

        return $statusEmojis[$status] ?? $status;
    }

    public function formatErrorMessage($error, $context = '')
    {
        $formatted = "❌ **Error Occurred**\n";
        if (!empty($context)) {
            $formatted .= "📍 **Context:** " . $context . "\n";
        }
        $formatted .= "🔍 **Details:** " . $error . "\n";
        $formatted .= "💡 **Suggestion:** Please check your input and try again.";
        
        return $formatted;
    }

    public function formatSuccessResponse($message, $data = null)
    {
        $formatted = "✅ **Success**\n";
        $formatted .= "📝 **Message:** " . $message . "\n";
        
        if ($data !== null) {
            $formatted .= "📊 **Data:** " . $this->formatData($data) . "\n";
        }
        
        return $formatted;
    }

    private function formatData($data)
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        
        return (string) $data;
    }

    public function formatServerLoad($loadData)
    {
        if (!isset($loadData['data'])) {
            return "❌ Unable to retrieve server load information";
        }

        // Handle the nested data structure from WHM API
        $data = $loadData['data'];
        if (isset($data['data'])) {
            $data = $data['data']; // Extract the actual load data
        }
        
        $formatted = "📊 **Server Load Average**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "🖥️ **Server:** " . ($data['hostname'] ?? 'cPanel Server') . "\n";
        $formatted .= "⏰ **Timestamp:** " . ($loadData['timestamp'] ?? date('Y-m-d H:i:s')) . "\n\n";
        
        $formatted .= "📈 **Load Averages:**\n";
        if (isset($data['one'])) {
            $formatted .= "   • **1 minute:** " . $data['one'] . " " . $this->getLoadStatus(floatval($data['one'])) . "\n";
        }
        if (isset($data['five'])) {
            $formatted .= "   • **5 minutes:** " . $data['five'] . " " . $this->getLoadStatus(floatval($data['five'])) . "\n";
        }
        if (isset($data['fifteen'])) {
            $formatted .= "   • **15 minutes:** " . $data['fifteen'] . " " . $this->getLoadStatus(floatval($data['fifteen'])) . "\n";
        }
        
        $formatted .= "\n💡 **Performance Status:** " . $this->getOverallLoadStatus($data) . "\n";
        
        return $formatted;
    }

    public function formatServerStatus($statusData)
    {
        // Handle nested data structure: data.data.actual_data
        if (!isset($statusData['data'])) {
            return "❌ Unable to retrieve server status information";
        }

        // Extract actual data from nested structure
        $data = $statusData['data'];
        if (isset($data['data'])) {
            $data = $data['data'];
        }

        $formatted = "🖥️ **Complete Server Status**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "⏰ **Report Generated:** " . ($statusData['timestamp'] ?? date('Y-m-d H:i:s')) . "\n\n";
        
        // Server Version Info - handle nested structure
        if (isset($data['server_version'])) {
            $versionData = $data['server_version'];
            // Extract from nested data structure if exists
            if (isset($versionData['data'])) {
                $version = $versionData['data'];
            } else {
                $version = $versionData;
            }
            $formatted .= "🔧 **Server Information:**\n";
            $formatted .= "   • **Version:** " . ($version['version'] ?? 'N/A') . "\n";
            $formatted .= "   • **Build:** " . ($version['build'] ?? 'N/A') . "\n";
            $formatted .= "   • **Release Tier:** " . ($version['release_tier'] ?? 'N/A') . "\n\n";
        }
        
        // Load Average - handle nested structure 
        if (isset($data['load_average'])) {
            $loadData = $data['load_average'];
            // Extract from nested data structure if exists
            if (isset($loadData['data'])) {
                $load = $loadData['data'];
            } else {
                $load = $loadData;
            }
            $formatted .= "📊 **Current Load:**\n";
            $formatted .= "   • **1 min:** " . ($load['one'] ?? 'N/A') . " " . $this->getLoadStatus(floatval($load['one'] ?? 0)) . "\n";
            $formatted .= "   • **5 min:** " . ($load['five'] ?? 'N/A') . " " . $this->getLoadStatus(floatval($load['five'] ?? 0)) . "\n";
            $formatted .= "   • **15 min:** " . ($load['fifteen'] ?? 'N/A') . " " . $this->getLoadStatus(floatval($load['fifteen'] ?? 0)) . "\n\n";
        }
        
        // Account Statistics
        if (isset($data['accounts']['data']['acct'])) {
            $accounts = $data['accounts']['data']['acct'];
            $formatted .= "👥 **Hosting Accounts:**\n";
            $formatted .= "   • **Total Accounts:** " . count($accounts) . "\n";
            $formatted .= "   • **Active Domains:** " . count(array_unique(array_column($accounts, 'domain'))) . "\n\n";
        }
        
        // Bandwidth Statistics
        if (isset($data['bandwidth_stats'])) {
            $formatted .= "📡 **Bandwidth Status:**\n";
            $formatted .= "   • **Monitoring:** Active\n";
            $formatted .= "   • **Data Available:** ✅ Yes\n\n";
        }
        
        $formatted .= "✅ **Overall Status:** Server is operational and responding normally\n";
        
        return $formatted;
    }

    public function formatServerDiskUsage($diskData)
    {
        // Handle nested data structure: data.data.actual_data
        if (!isset($diskData['data'])) {
            return "❌ Unable to retrieve disk usage information";
        }

        // Extract actual data from nested structure
        $data = $diskData['data'];
        if (isset($data['data'])) {
            $data = $data['data'];
        }

        $formatted = "💾 **Server Disk Usage Analysis**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "⏰ **Analysis Time:** " . ($diskData['timestamp'] ?? date('Y-m-d H:i:s')) . "\n\n";
        
        // Summary
        if (isset($data['summary'])) {
            $summary = $data['summary'];
            $formatted .= "📊 **Usage Summary:**\n";
            $formatted .= "   • **Total Accounts:** " . ($data['total_accounts'] ?? 'N/A') . "\n";
            $formatted .= "   • **Total Disk Used:** " . number_format($summary['total_disk_used_mb'] ?? 0, 2) . " MB\n";
            $formatted .= "   • **Accounts with Limits:** " . ($summary['accounts_with_limits'] ?? 0) . "\n";
            $formatted .= "   • **Unlimited Accounts:** " . ($summary['accounts_unlimited'] ?? 0) . "\n\n";
            
            // Heaviest User
            if (isset($summary['heaviest_user'])) {
                $heavy = $summary['heaviest_user'];
                $formatted .= "🏆 **Highest Usage Account:**\n";
                $formatted .= "   • **User:** " . ($heavy['user'] ?? 'N/A') . "\n";
                $formatted .= "   • **Domain:** " . ($heavy['domain'] ?? 'N/A') . "\n";
                $formatted .= "   • **Usage:** " . ($heavy['disk_used'] ?? 'N/A') . "\n";
                $formatted .= "   • **Limit:** " . ($heavy['disk_limit'] ?? 'N/A') . "\n\n";
            }
        }
        
        // Account Details (show top 5 by usage)
        if (isset($data['disk_usage_by_account']) && is_array($data['disk_usage_by_account'])) {
            $accounts = $data['disk_usage_by_account'];
            $formatted .= "📋 **Top Disk Usage by Account:**\n";
            
            // Sort by disk usage (convert MB values for sorting)
            usort($accounts, function($a, $b) {
                $aUsage = floatval(str_replace('M', '', $a['disk_used'] ?? '0'));
                $bUsage = floatval(str_replace('M', '', $b['disk_used'] ?? '0'));
                return $bUsage <=> $aUsage;
            });
            
            $topAccounts = array_slice($accounts, 0, 5);
            foreach ($topAccounts as $index => $account) {
                $formatted .= "   " . ($index + 1) . ". **" . ($account['domain'] ?? 'N/A') . "**\n";
                $formatted .= "      • User: " . ($account['user'] ?? 'N/A') . "\n";
                $formatted .= "      • Usage: " . ($account['disk_used'] ?? 'N/A') . "\n";
                $formatted .= "      • Limit: " . ($account['disk_limit'] ?? 'unlimited') . "\n";
                if ($index < count($topAccounts) - 1) {
                    $formatted .= "      ─────────────────────────────\n";
                }
                $formatted .= "\n";
            }
        }
        
        return $formatted;
    }

    public function formatServerServices($servicesData)
    {
        // Handle nested data structure: data.data.actual_data
        if (!isset($servicesData['data'])) {
            return "❌ Unable to retrieve server services information";
        }

        // Extract actual data from nested structure
        $data = $servicesData['data'];
        if (isset($data['data'])) {
            $data = $data['data'];
        }

        $formatted = "🔧 **Server Services & System Information**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        $formatted .= "⏰ **Status Check Time:** " . ($servicesData['timestamp'] ?? date('Y-m-d H:i:s')) . "\n\n";
        
        // Service Status
        $services = ['httpd', 'mysql', 'ftpd', 'exim', 'named'];
        $formatted .= "🔍 **Service Status:**\n";
        
        foreach ($services as $service) {
            $statusKey = $service . '_status';
            if (isset($data[$statusKey])) {
                $status = $data[$statusKey];
                $isRunning = $this->determineServiceStatus($status);
                $formatted .= "   • **" . strtoupper($service) . ":** " . ($isRunning ? '✅ Running' : '❌ Stopped') . "\n";
            } else {
                $formatted .= "   • **" . strtoupper($service) . ":** ⚠️ Status Unknown\n";
            }
        }
        $formatted .= "\n";
        
        // System Information
        foreach ($data as $key => $value) {
            if (strpos($key, 'server_info_for_') === 0) {
                $user = str_replace('server_info_for_', '', $key);
                $formatted .= "💻 **System Info (via {$user}):**\n";
                
                if (isset($value['result']['data'])) {
                    $info = $value['result']['data'];
                    if (isset($info['hostname'])) {
                        $formatted .= "   • **Hostname:** " . $info['hostname'] . "\n";
                    }
                    if (isset($info['machine_type'])) {
                        $formatted .= "   • **Architecture:** " . $info['machine_type'] . "\n";
                    }
                    if (isset($info['operating_system'])) {
                        $formatted .= "   • **OS:** " . $info['operating_system'] . "\n";
                    }
                    if (isset($info['processor_information'])) {
                        $formatted .= "   • **CPU:** " . $info['processor_information'] . "\n";
                    }
                }
                $formatted .= "\n";
                break; // Only show one successful server info
            }
        }
        
        $formatted .= "📡 **Monitoring Status:** All services are being actively monitored\n";
        $formatted .= "🔧 **System Health:** Server is operational and responding\n";
        
        return $formatted;
    }

    private function getLoadStatus($load)
    {
        if ($load < 1.0) {
            return "🟢 (Low)";
        } elseif ($load < 2.0) {
            return "🟡 (Moderate)";
        } else {
            return "🔴 (High)";
        }
    }

    private function getOverallLoadStatus($loadData)
    {
        $loads = [
            floatval($loadData['one'] ?? 0),
            floatval($loadData['five'] ?? 0),
            floatval($loadData['fifteen'] ?? 0)
        ];
        
        $avgLoad = array_sum($loads) / count($loads);
        
        if ($avgLoad < 1.0) {
            return "🟢 Excellent - Server is running smoothly";
        } elseif ($avgLoad < 2.0) {
            return "🟡 Good - Normal server load";
        } else {
            return "🔴 High - Server may be under heavy load";
        }
    }

    private function determineServiceStatus($serviceData)
    {
        if (!is_array($serviceData) || !isset($serviceData['data'])) {
            return "🔍 Status Unknown";
        }
        
        $data = $serviceData['data'];
        
        if (isset($data['enabled']) && $data['enabled'] == 1) {
            return "✅ Running";
        } elseif (isset($data['status']) && $data['status'] == 'running') {
            return "✅ Running";
        } elseif (isset($data['running']) && $data['running'] == 1) {
            return "✅ Running";
        } else {
            return "❌ Stopped";
        }
    }

    public function formatBandwidthUsage($bandwidthData)
    {
        if (!isset($bandwidthData['data']) || !is_array($bandwidthData['data'])) {
            return "❌ Unable to retrieve bandwidth usage data";
        }

        $data = $bandwidthData['data'];
        $formatted = "📊 **Bandwidth Usage Statistics**\n";
        $formatted .= "═══════════════════════════════════════\n\n";
        
        // Basic Information
        $formatted .= "🔹 **Query Information**\n";
        $formatted .= "   • **Month:** " . ($data['month'] ?? 'Current') . "\n";
        $formatted .= "   • **Year:** " . ($data['year'] ?? date('Y')) . "\n";
        $formatted .= "   • **Reseller:** " . ($data['reseller'] ?? 'root') . "\n";
        
        // Total usage summary
        if (isset($data['totalused'])) {
            $totalUsedGB = round($data['totalused'] / (1024 * 1024 * 1024), 2);
            $formatted .= "   • **Total Usage:** " . number_format($totalUsedGB, 2) . " GB (" . number_format($data['totalused']) . " bytes)\n";
        }
        
        $formatted .= "\n";
        
        // Account-specific bandwidth usage
        if (isset($data['acct']) && is_array($data['acct']) && count($data['acct']) > 0) {
            $formatted .= "🔹 **Account Bandwidth Usage**\n";
            $formatted .= "   📊 **Total Accounts:** " . count($data['acct']) . "\n\n";
            
            // Sort accounts by usage (highest first)
            $accounts = $data['acct'];
            usort($accounts, function($a, $b) {
                $aUsage = isset($a['totalbytes']) ? $a['totalbytes'] : 0;
                $bUsage = isset($b['totalbytes']) ? $b['totalbytes'] : 0;
                return $bUsage <=> $aUsage;
            });
            
            foreach ($accounts as $index => $account) {
                $formatted .= "   🔸 **Account #" . ($index + 1) . "**\n";
                $formatted .= "      • **Domain:** " . ($account['domain'] ?? 'N/A') . "\n";
                $formatted .= "      • **User:** " . ($account['user'] ?? 'N/A') . "\n";
                
                if (isset($account['totalbytes'])) {
                    $usageGB = round($account['totalbytes'] / (1024 * 1024 * 1024), 3);
                    $formatted .= "      • **Total Usage:** " . number_format($usageGB, 3) . " GB\n";
                }
                
                if (isset($account['in'])) {
                    $inGB = round($account['in'] / (1024 * 1024 * 1024), 3);
                    $formatted .= "      • **Incoming:** " . number_format($inGB, 3) . " GB\n";
                }
                
                if (isset($account['out'])) {
                    $outGB = round($account['out'] / (1024 * 1024 * 1024), 3);
                    $formatted .= "      • **Outgoing:** " . number_format($outGB, 3) . " GB\n";
                }
                
                if ($index < count($accounts) - 1) {
                    $formatted .= "      ─────────────────────────\n";
                }
                $formatted .= "\n";
            }
        } else {
            $formatted .= "🔹 **Account Information**\n";
            $formatted .= "   📭 No detailed account bandwidth data available\n\n";
        }
        
        // Analysis and recommendations
        $formatted .= "🔹 **Analysis & Recommendations**\n";
        
        if (isset($data['acct']) && count($data['acct']) > 0) {
            $accounts = $data['acct'];
            $highUsageAccounts = array_filter($accounts, function($account) {
                return isset($account['totalbytes']) && $account['totalbytes'] > (5 * 1024 * 1024 * 1024); // 5GB
            });
            
            if (count($highUsageAccounts) > 0) {
                $formatted .= "   ⚠️ **High Usage:** " . count($highUsageAccounts) . " account(s) using >5GB\n";
            } else {
                $formatted .= "   ✅ **Usage Status:** All accounts within normal usage ranges\n";
            }
            
            // Find top consumer
            $topAccount = reset($accounts); // First account after sorting
            if (isset($topAccount['totalbytes']) && $topAccount['totalbytes'] > 0) {
                $topUsageGB = round($topAccount['totalbytes'] / (1024 * 1024 * 1024), 2);
                $formatted .= "   📈 **Top Consumer:** " . ($topAccount['domain'] ?? 'N/A') . " (" . number_format($topUsageGB, 2) . " GB)\n";
            }
        }
        
        $formatted .= "   💡 **Tip:** Monitor bandwidth usage regularly to optimize server performance\n";
        $formatted .= "   🔧 **Actions:** Consider upgrading plans for high-usage accounts\n";
        
        return $formatted;
    }
} 