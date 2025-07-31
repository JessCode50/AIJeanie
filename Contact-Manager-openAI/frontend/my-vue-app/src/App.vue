<template>
  <div class="app">
    <!-- Global Header (hidden for fullscreen views) -->
    <AppHeader 
      v-if="!isFullscreenView && !isDashboardView"
      :title="getViewTitle()"
      :subtitle="getViewSubtitle()"
      :icon="getViewIcon()"
      :current-view="currentRouteName"
      :search-query="searchQuery"
      :notifications="notifications"
      :show-notifications="showNotifications"
      :refresh-loading="dashboardLoading"
      :data-status="realDataLoaded ? '• Live Data' : '• Sample Data'"
      @view-change="handleViewChange"
      @search="handleSearch"
      @toggle-notifications="showNotifications = !showNotifications"
      @refresh="loadRealDashboardData"
    />

    <!-- Main Content Views -->
    <div class="app-content" :class="{ 'fullscreen': isFullscreenView || isDashboardView }">
      <!-- Router View - This will display the current route component -->
      <router-view 
        :quick-actions="quickActions"
        :tickets="tickets"
        :server-stats="serverStats"
        :activities="recentActivities"
        :loading="dashboardLoading"
        :clients="clients"
        :cpanel-accounts="cpanelAccounts"
        :hosting-accounts="hostingAccounts"
        :hosting-domains="hostingDomains"
        :hosting-disk-usage="hostingDiskUsage"
        :hosting-summary="hostingSummary"
        :hosting-system-info="hostingSystemInfo"
        :loading-accounts="loading"
        :loading-domains="hostingDataLoading"
        :loading-disk-usage="hostingDataLoading"
        :hosting-data-loading="hostingDataLoading"
        :aiResponse="aiResponse"
        :selectedTicket="selectedTicketForAI"
        :ai-loading="loading"
        @view-change="handleViewChange"
        @quick-action="executeQuickAction"
        @send-message="sendAIMessage"
        @clear-chat="aiClear"
        @export-chat="exportChat"
        @submit-ticket="handleSubmitTicket"
        @lookup-ticket="handleLookupTicket"
        @create-ticket="createTicket"
        @update-ticket="updateTicket"
        @delete-ticket="deleteTicket"
        @load-tickets="loadRealTickets"
        @send-to-ai="handleSendTicketToAI"
        @create-client="createClient"
        @update-client="updateClient"
        @load-clients="loadRealClients"
        @execute-action="executeServerAction"
        @load-accounts="loadRealServerAccounts"
        @load-system-info="loadRealSystemInfo"
        @refresh-data="refreshServerData"
        @load-hosting-data="loadRealHostingData"
        @manage-account="manageHostingAccount"
        @suspend-account="suspendHostingAccount"
        @manage-domain="manageDomain"
      />
    </div>
  </div>
</template>

<script>
import AppHeader from './components/layout/AppHeader.vue'

export default {
  name: 'App',
  components: {
    AppHeader
  },
  computed: {
    currentRouteName() {
      return this.$route.name?.toLowerCase() || 'dashboard'
    },
    isFullscreenView() {
      return this.$route.meta?.fullscreen || false
    },
    isDashboardView() {
      return this.currentRouteName === 'dashboard' || this.$route.name === 'Dashboard'
    }
  },
  data() {
    return {
      searchQuery: '',
      showNotifications: false,
      aiResponse: [],
      userMessage: '',
      loading: false,
      messageHistory: '',
      historyLog: '',
      clientId: '',
      product: '',
      server: '',
      id: '',
      tickets: [],
      clients: [],
      cpanelAccounts: [],
      serverStats: {
        cpuUsage: 23,
        memoryUsage: 67,
        diskUsage: 45,
        networkIO: 1.2
      },
      hostingAccounts: [],
      hostingDomains: [],
      hostingDiskUsage: [],
      hostingSummary: {
        totalAccounts: 0,
        totalDomains: 0,
        totalDiskUsageGB: 0,
        activeServices: 0
      },
      hostingSystemInfo: {},
      hostingDataLoading: false,
      notifications: [
        { id: 1, type: 'warning', message: 'High memory usage detected', time: '5 min ago' },
        { id: 2, type: 'info', message: 'New ticket #1003 created', time: '10 min ago' },
        { id: 3, type: 'success', message: 'Backup completed successfully', time: '1 hour ago' }
      ],
      quickActions: [
        { id: 'create-ticket', label: 'Tickets', icon: '🎫', color: '#3b82f6' },
        { id: 'add-client', label: 'Clients', icon: '👤', color: '#10b981' },
        { id: 'ai-assistant', label: 'AI Assistant', icon: '🤖', color: '#8b5cf6' },
        { id: 'create-invoice', label: 'Invoices', icon: '💰', color: '#f59e0b' },
        { id: 'backup-account', label: 'Backup', icon: '💾', color: '#059669' },
        { id: 'check-ssl', label: 'SSL Check', icon: '🔒', color: '#ef4444' },
        { id: 'system-status', label: 'System', icon: '⚡', color: '#06b6d4' }
      ],
      recentActivities: [],
      realDataLoaded: false,
      dashboardLoading: false,
      selectedTicketForAI: null
    }
  },
  methods: {
    handleViewChange(newView) {
      // Convert view names to route names
      const routeMap = {
        'dashboard': '/dashboard',
        'AI': '/ai',
        'tickets': '/tickets',
        'clients': '/clients',
        'servers': '/servers',
        'hosting': '/hosting',
        'session': '/session',
        'log': '/log',
        'clientInput': '/client-input'
      }
      
      const routePath = routeMap[newView] || '/dashboard'
      this.$router.push(routePath)
    },
    getViewTitle() {
      return this.$route.meta?.title || 'Admin Dashboard'
    },
    getViewSubtitle() {
      const subtitles = {
        dashboard: 'Hosting & Client Management',
        ai: 'AI-Powered Customer Support',
        tickets: 'Manage customer support requests',
        clients: 'WHMCS Client Administration',
        servers: 'cPanel & WHM Administration',
        hosting: 'Hosting Account Management'
      }
      return subtitles[this.currentRouteName] || 'System Management'
    },
    getViewIcon() {
      return this.$route.meta?.icon || '🏠'
    },
    handleSearch(query) {
      this.searchQuery = query
      console.log('Searching for:', query)
    },
    executeQuickAction(actionId) {
      switch(actionId) {
        case 'create-ticket':
          this.$router.push('/tickets')
          break
        case 'add-client':
          this.$router.push('/clients')
          break
        case 'ai-assistant':
          this.$router.push('/ai')
          break
        default:
          console.log('Action:', actionId)
      }
    },
    sendAIMessage(message) {
      // Add user message to aiResponse as string
      this.aiResponse.push(`👤 **You:** ${message}`)
      
      this.loading = true
      
      // Make API call to backend
      fetch('http://localhost:8080/contacts/ai', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: message })
      })
      .then(response => response.json())
      .then(data => {
        console.log('AI Response Data:', data); // Debug log
        
        // Handle different response types
        if (data === 'alert' || (typeof data === 'string' && data.includes('alert'))) {
          this.aiResponse.push(`AI: I'm having trouble processing that request right now. Could you try rephrasing your question or asking about something specific like 'show me server accounts' or 'check disk usage'?`);
        } else if (data.pending_functions && data.pending_functions.length > 0) {
          // Extract the AI response from pending_functions
          const aiResponse = data.pending_functions[0].description;
          if (aiResponse) {
            this.aiResponse.push(`AI: ${aiResponse}`);
          }
        } else if (data.response && data.response.trim()) {
          // Fallback to response field if available
          this.aiResponse.push(`AI: ${data.response}`);
        } else if (typeof data === 'object' && data.message) {
          // Handle error objects
          this.aiResponse.push(`AI: ${data.message}`);
        } else {
          // Default response if no content found
          this.aiResponse.push('AI: I received your message. How can I help you with your hosting and server management needs today?');
        }
        
        this.loading = false
      })
      .catch(error => {
        console.error('Error sending AI message:', error)
        this.aiResponse.push('AI: Sorry, there was an error processing your request. Please try again.')
        this.loading = false
      })
    },
    aiClear() {
      this.aiResponse = []
      this.selectedTicketForAI = null
    },
    exportChat() {
      console.log('Exporting chat...')
    },
    handleSubmitTicket(data) {
      console.log('Submit ticket:', data)
    },
    handleLookupTicket(id) {
      console.log('Lookup ticket:', id)
    },
    handleSendTicketToAI(ticket) {
      console.log('Sending ticket to AI:', ticket)
      
      // Clear previous AI conversation
      this.aiResponse = []
      
      // Set the selected ticket for AI processing
      this.selectedTicketForAI = ticket
      
      // Switch to AI view and let it handle the ticket submission
      this.$router.push('/ai').then(() => {
        // Emit an event to the AI view to auto-submit the ticket
        this.$nextTick(() => {
          // The AIAssistantView will handle the auto-submission
        })
      })
    },
    async createTicket(data) {
      try {
        const response = await fetch('http://localhost:8080/contacts/tickets/create', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });
        
        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            this.tickets.unshift(result.ticket);
            console.log('Ticket created successfully:', result.ticket);
          }
        }
      } catch (error) {
        console.error('Error creating ticket:', error);
      }
    },
    async updateTicket(data) {
      try {
        const response = await fetch('http://localhost:8080/contacts/tickets/update', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });
        
        if (response.ok) {
          const result = await response.json();
          console.log('Ticket updated successfully:', result);
          this.loadRealTickets(); // Refresh the list
        }
      } catch (error) {
        console.error('Error updating ticket:', error);
      }
    },
    async deleteTicket(id) {
      try {
        const response = await fetch('http://localhost:8080/contacts/tickets/delete', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id: id })
        });
        
        if (response.ok) {
          const result = await response.json();
          console.log('Ticket deleted successfully:', result);
          this.tickets = this.tickets.filter(ticket => ticket.id !== id);
        }
      } catch (error) {
        console.error('Error deleting ticket:', error);
      }
    },
    async loadRealTickets() {
      try {
        const response = await fetch('http://localhost:8080/contacts/tickets/list', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.tickets = data.tickets || [];
          console.log('Real tickets loaded:', this.tickets.length);
        } else {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
      } catch (error) {
        console.error('Error loading tickets:', error);
        // Fallback to sample data if API fails
        this.tickets = [
          { id: 1001, subject: 'Server connectivity issue', client: 'John Doe', status: 'open', priority: 'high', time: '2 hours ago' },
          { id: 1002, subject: 'Email not working', client: 'Jane Smith', status: 'closed', priority: 'medium', time: '1 day ago' }
        ];
      }
    },
    async createClient(data) {
      console.log('Creating client:', data)
    },
    async updateClient(data) {
      console.log('Updating client:', data)
    },
    async loadRealClients() {
      try {
        const response = await fetch('http://localhost:8080/contacts/clients/list');
        if (response.ok) {
          const data = await response.json();
          this.clients = data.clients || [];
          console.log('Clients loaded:', this.clients.length);
        }
      } catch (error) {
        console.error('Error loading clients:', error);
      }
    },
    executeServerAction(action) {
      console.log('Server action:', action)
      // Most actions are now handled directly in ServersView component
      // This is kept for any additional app-level server actions
    },
    async loadRealServerAccounts() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/accounts');
        if (response.ok) {
          const data = await response.json();
          console.log('Server accounts API response:', data); // Debug log
          this.cpanelAccounts = data.accounts || [];
          console.log('Server accounts loaded:', this.cpanelAccounts.length);
          console.log('First account structure:', this.cpanelAccounts[0]); // Debug first account
        }
      } catch (error) {
        console.error('Error loading server accounts:', error);
      }
    },
    async loadRealSystemInfo() {
      try {
        const response = await fetch('http://localhost:8080/contacts/hosting/system-info');
        if (response.ok) {
          const data = await response.json();
          this.hostingSystemInfo = data.systemInfo || {};
          console.log('System info loaded:', this.hostingSystemInfo);
          
          // Also update server stats if available
          if (data.systemInfo) {
            // Extract real metrics from system info
            if (data.systemInfo.cpuUsage) {
              this.serverStats.cpuUsage = parseInt(data.systemInfo.cpuUsage) || this.serverStats.cpuUsage;
            }
            if (data.systemInfo.memoryUsage) {
              this.serverStats.memoryUsage = parseInt(data.systemInfo.memoryUsage) || this.serverStats.memoryUsage;
            }
            if (data.systemInfo.diskUsage) {
              this.serverStats.diskUsage = parseInt(data.systemInfo.diskUsage) || this.serverStats.diskUsage;
            }
            if (data.systemInfo.networkIO) {
              this.serverStats.networkIO = parseFloat(data.systemInfo.networkIO) || this.serverStats.networkIO;
            }
          }
        }
        
        // Try to get additional system load data
        try {
          const loadResponse = await fetch('http://localhost:8080/contacts/server/load');
          if (loadResponse.ok) {
            const loadData = await loadResponse.json();
            if (loadData.success && loadData.formatted) {
              // Update server stats with load data
              this.serverStats.cpuUsage = loadData.formatted.cpuUsage || this.serverStats.cpuUsage;
              this.serverStats.memoryUsage = loadData.formatted.memoryUsage || this.serverStats.memoryUsage;
              console.log('System load data integrated:', loadData.formatted);
            }
          }
        } catch (loadError) {
          console.log('System load data not available, using defaults');
        }
      } catch (error) {
        console.error('Error loading system info:', error);
      }
    },
    refreshServerData() {
      this.loadRealServerAccounts()
      this.loadRealSystemInfo()
    },
    manageHostingAccount(account) {
      console.log('Managing account:', account)
    },
    suspendHostingAccount(account) {
      console.log('Suspending account:', account)
    },
    manageDomain(domain) {
      console.log('Managing domain:', domain)
    },
    async loadRealHostingData() {
      console.log('Loading hosting data...');
      this.hostingDataLoading = true;
      
      try {
        // Load hosting accounts
        const accountsResponse = await fetch('http://localhost:8080/contacts/hosting/accounts');
        if (accountsResponse.ok) {
          const accountsData = await accountsResponse.json();
          this.hostingAccounts = accountsData.accounts || [];
        }
        
        // Load hosting domains
        const domainsResponse = await fetch('http://localhost:8080/contacts/hosting/domains');
        if (domainsResponse.ok) {
          const domainsData = await domainsResponse.json();
          this.hostingDomains = domainsData.domains || [];
        }
        
        // Load disk usage
        const diskResponse = await fetch('http://localhost:8080/contacts/hosting/disk-usage');
        if (diskResponse.ok) {
          const diskData = await diskResponse.json();
          this.hostingDiskUsage = diskData.diskUsage || [];
        }
        
        // Load hosting summary
        const summaryResponse = await fetch('http://localhost:8080/contacts/hosting/summary');
        if (summaryResponse.ok) {
          const summaryData = await summaryResponse.json();
          this.hostingSummary = summaryData.summary || {};
        }
        
        console.log('All hosting data loaded successfully');
      } catch (error) {
        console.error('Error loading hosting data:', error);
      } finally {
        this.hostingDataLoading = false;
      }
    },
    async loadRealDashboardData() {
      this.dashboardLoading = true;
      
      try {
        await Promise.all([
          this.loadRealTickets(),
          this.loadRealClients(),
          this.loadRealServerAccounts(),
          this.loadRealHostingData(),
          this.loadRealActivities()
        ]);
        this.realDataLoaded = true;
        console.log('Dashboard data loaded successfully');
      } catch (error) {
        console.error('Error loading dashboard data:', error);
      } finally {
        this.dashboardLoading = false;
      }
    },
    async loadRealActivities() {
      try {
        const response = await fetch('http://localhost:8080/contacts/monitoring/status');
        if (response.ok) {
          const data = await response.json();
          this.recentActivities = data.activities || [];
          console.log('Activities loaded:', this.recentActivities.length);
        }
      } catch (error) {
        console.error('Error loading activities:', error);
      }
    }
  },
  mounted() {
    this.loadRealDashboardData()
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

body {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  overflow-x: hidden;
}

#app {
  margin: 0;
  padding: 0;
  width: 100vw;
  min-height: 100vh;
  overflow-x: hidden;
}

.app {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
}

.app-content {
  width: 100%;
  margin: 0;
  padding: 0;
}

.app-content.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 1000;
  overflow: auto;
  margin: 0;
  padding: 0;
}
</style>
