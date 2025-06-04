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
        if (is_array($data)) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        return strval($data);
    }
} 