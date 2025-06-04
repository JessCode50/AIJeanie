<?php

namespace App\Utils;

class ContextManager
{
    private $maxHistoryEntries = 20; // Keep last 20 interactions
    private $contextKeywords = [
        'email' => ['email', 'mail', 'pop', 'inbox'],
        'domain' => ['domain', 'website', 'site'],
        'account' => ['account', 'user', 'cpanel'],
        'client' => ['client', 'customer', 'billing'],
        'invoice' => ['invoice', 'bill', 'payment']
    ];

    public function getChatHistory($session)
    {
        $history = $session->get('history') ?? [];
        
        // Limit history to prevent token overflow
        if (count($history) > $this->maxHistoryEntries) {
            $history = array_slice($history, -$this->maxHistoryEntries);
            $session->set('history', $history);
        }
        
        return $history;
    }

    public function updateContext($session, $function, $result)
    {
        $chatHistory = $this->getChatHistory($session);
        
        // Add context markers for better AI understanding
        $contextEntry = [
            "role" => "system",
            "content" => "FUNCTION_EXECUTED: {$function} completed successfully",
            "timestamp" => time(),
            "function" => $function,
            "success" => true
        ];
        
        $chatHistory[] = $contextEntry;
        $session->set('history', $chatHistory);
        
        // Update conversation context
        $this->updateConversationContext($session, $function, $result);
    }

    public function analyzeConversationContext($chatHistory)
    {
        $context = [
            'recent_topics' => [],
            'recent_functions' => [],
            'conversation_flow' => 'new',
            'user_intent' => 'unknown',
            'domain_focus' => null,
            'client_focus' => null
        ];

        if (empty($chatHistory)) {
            return $context;
        }

        // Analyze last 5 messages for topics
        $recentMessages = array_slice($chatHistory, -5);
        
        foreach ($recentMessages as $message) {
            if ($message['role'] === 'user') {
                $topics = $this->extractTopics($message['content']);
                $context['recent_topics'] = array_merge($context['recent_topics'], $topics);
            }
            
            if (isset($message['function'])) {
                $context['recent_functions'][] = $message['function'];
            }
        }

        // Determine conversation flow
        $context['conversation_flow'] = $this->determineConversationFlow($recentMessages);
        $context['user_intent'] = $this->analyzeUserIntent($recentMessages);
        
        // Extract specific focuses
        $context['domain_focus'] = $this->extractDomainFocus($recentMessages);
        $context['client_focus'] = $this->extractClientFocus($recentMessages);

        return $context;
    }

    private function extractTopics($message)
    {
        $topics = [];
        $message = strtolower($message);
        
        foreach ($this->contextKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $topics[] = $topic;
                    break;
                }
            }
        }
        
        return array_unique($topics);
    }

    private function determineConversationFlow($recentMessages)
    {
        if (count($recentMessages) <= 1) {
            return 'new';
        }
        
        $systemMessages = array_filter($recentMessages, function($msg) {
            return $msg['role'] === 'system' && isset($msg['function']);
        });
        
        if (count($systemMessages) > 0) {
            return 'ongoing_task';
        }
        
        return 'continuation';
    }

    private function analyzeUserIntent($recentMessages)
    {
        $intents = [
            'create' => ['create', 'add', 'new', 'make'],
            'read' => ['show', 'list', 'get', 'view', 'see'],
            'update' => ['update', 'change', 'modify', 'edit'],
            'delete' => ['delete', 'remove', 'destroy']
        ];
        
        $lastUserMessage = '';
        foreach (array_reverse($recentMessages) as $message) {
            if ($message['role'] === 'user') {
                $lastUserMessage = strtolower($message['content']);
                break;
            }
        }
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lastUserMessage, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'unknown';
    }

    private function extractDomainFocus($recentMessages)
    {
        $domainPattern = '/([a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*\.([a-zA-Z]{2,})+)/';
        
        foreach (array_reverse($recentMessages) as $message) {
            if ($message['role'] === 'user') {
                if (preg_match($domainPattern, $message['content'], $matches)) {
                    return $matches[1];
                }
            }
        }
        
        return null;
    }

    private function extractClientFocus($recentMessages)
    {
        $clientPattern = '/client\s*(?:id\s*)?[:#]?\s*(\d+)/i';
        
        foreach (array_reverse($recentMessages) as $message) {
            if ($message['role'] === 'user') {
                if (preg_match($clientPattern, $message['content'], $matches)) {
                    return $matches[1];
                }
            }
        }
        
        return null;
    }

    private function updateConversationContext($session, $function, $result)
    {
        $context = $session->get('conversation_context') ?? [
            'recent_domains' => [],
            'recent_clients' => [],
            'recent_functions' => [],
            'session_start' => time()
        ];
        
        // Track recent functions
        $context['recent_functions'][] = [
            'function' => $function,
            'timestamp' => time()
        ];
        
        // Keep only last 10 functions
        if (count($context['recent_functions']) > 10) {
            $context['recent_functions'] = array_slice($context['recent_functions'], -10);
        }
        
        // Extract and track domains/clients from results
        $this->extractAndTrackEntities($context, $function, $result);
        
        $session->set('conversation_context', $context);
    }

    private function extractAndTrackEntities(&$context, $function, $result)
    {
        switch ($function) {
            case 'listaccts':
                if (isset($result['data']['acct'])) {
                    foreach ($result['data']['acct'] as $account) {
                        if (isset($account['domain'])) {
                            $context['recent_domains'][] = $account['domain'];
                        }
                    }
                }
                break;
                
            case 'client':
                if (isset($result['client']['client_id'])) {
                    $context['recent_clients'][] = $result['client']['client_id'];
                }
                break;
        }
        
        // Keep only last 5 domains/clients
        if (count($context['recent_domains']) > 5) {
            $context['recent_domains'] = array_slice($context['recent_domains'], -5);
        }
        if (count($context['recent_clients']) > 5) {
            $context['recent_clients'] = array_slice($context['recent_clients'], -5);
        }
    }

    public function buildEnhancedHistory($chatHistory, $userMessage, $conversationContext)
    {
        $historyString = [];
        
        // Add conversation context if available
        if (!empty($conversationContext['recent_topics'])) {
            $topics = implode(', ', array_unique($conversationContext['recent_topics']));
            $historyString[] = [
                "role" => "system",
                "content" => "CONTEXT: Recent conversation topics include: {$topics}"
            ];
        }
        
        // Add recent successful functions for context
        if (!empty($conversationContext['recent_functions'])) {
            $functions = array_slice(array_unique($conversationContext['recent_functions']), -3);
            $functionList = implode(', ', $functions);
            $historyString[] = [
                "role" => "system", 
                "content" => "CONTEXT: Recently executed functions: {$functionList}"
            ];
        }
        
        // Add domain/client focus context
        if ($conversationContext['domain_focus']) {
            $historyString[] = [
                "role" => "system",
                "content" => "CONTEXT: Currently focused on domain: {$conversationContext['domain_focus']}"
            ];
        }
        
        if ($conversationContext['client_focus']) {
            $historyString[] = [
                "role" => "system",
                "content" => "CONTEXT: Currently focused on client ID: {$conversationContext['client_focus']}"
            ];
        }
        
        // Add chat history
        foreach ($chatHistory as $entry) {
            if (!isset($entry['function'])) { // Skip system function entries
                $historyString[] = ["role" => $entry['role'], "content" => $entry['content']];
            }
        }
        
        // Add current user message
        $historyString[] = ["role" => "user", "content" => $userMessage];
        
        return $historyString;
    }

    public function shouldSuggestFollowUp($conversationContext, $lastFunction)
    {
        $followUpSuggestions = [
            'listaccts' => ['view specific account details', 'create new account'],
            'client' => ['view client invoices', 'view client services'],
            'list_pops' => ['add new email account', 'check email quotas'],
            'count_pops' => ['list all email accounts', 'add new email']
        ];
        
        return isset($followUpSuggestions[$lastFunction]) 
            ? $followUpSuggestions[$lastFunction] 
            : [];
    }

    public function getSmartSuggestions($conversationContext)
    {
        $suggestions = [];
        
        // Based on recent topics
        if (in_array('email', $conversationContext['recent_topics'])) {
            $suggestions[] = "Manage email accounts and forwarders";
        }
        
        if (in_array('client', $conversationContext['recent_topics'])) {
            $suggestions[] = "View client billing and invoices";
        }
        
        if (in_array('domain', $conversationContext['recent_topics'])) {
            $suggestions[] = "Check domain accounts and settings";
        }
        
        // Based on conversation flow
        if ($conversationContext['conversation_flow'] === 'new') {
            $suggestions[] = "List server accounts to get started";
            $suggestions[] = "Search for a specific client";
        }
        
        return $suggestions;
    }

    public function clearContext($session)
    {
        $session->remove('conversation_context');
        $session->remove('history');
    }

    public function getContextSummary($session)
    {
        $context = $session->get('conversation_context') ?? [];
        $history = $this->getChatHistory($session);
        
        return [
            'session_duration' => isset($context['session_start']) 
                ? time() - $context['session_start'] 
                : 0,
            'total_interactions' => count($history),
            'recent_domains' => $context['recent_domains'] ?? [],
            'recent_clients' => $context['recent_clients'] ?? [],
            'recent_functions' => array_slice($context['recent_functions'] ?? [], -5)
        ];
    }
} 