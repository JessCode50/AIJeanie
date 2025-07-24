<template>
  <div class="app">
    <!-- Global Header (hidden for fullscreen views) -->
    <AppHeader 
      v-if="!isFullscreenView"
      :title="getViewTitle()"
      :subtitle="getViewSubtitle()"
      :icon="getViewIcon()"
      :current-view="view"
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
    <div class="app-content" :class="{ 'fullscreen': isFullscreenView }">
      <!-- Dashboard View -->
      <DashboardView 
        v-if="view === 'dashboard'"
        :quick-actions="quickActions"
        :tickets="tickets"
        :server-stats="serverStats"
        :activities="recentActivities"
        :loading="dashboardLoading"
        @view-change="handleViewChange"
        @quick-action="executeQuickAction"
      />

      <!-- AI Assistant View -->
      <AIAssistantView 
        v-else-if="view === 'AI'"
        :ai-response="aiResponse"
        :loading="loading"
        @send-message="sendAIMessage"
        @clear-chat="aiClear"
        @export-chat="exportChat"
        @view-change="handleViewChange"
        @submit-ticket="handleSubmitTicket"
        @lookup-ticket="handleLookupTicket"
      />

      <!-- Tickets View -->
      <TicketsView 
        v-else-if="view === 'tickets'"
        :tickets="tickets"
        :loading="loading"
        @create-ticket="createTicket"
        @update-ticket="updateTicket"
        @delete-ticket="deleteTicket"
        @load-tickets="loadRealTickets"
      />

      <!-- Clients View -->
      <ClientsView 
        v-else-if="view === 'clients'"
        :clients="clients"
        :loading="loading"
        @create-client="createClient"
        @update-client="updateClient"
        @load-clients="loadRealClients"
      />

      <!-- Servers View -->
      <ServersView 
        v-else-if="view === 'servers'"
        :server-stats="serverStats"
        :accounts="cpanelAccounts"
        :system-info="hostingSystemInfo"
        :loading-accounts="loading"
        :loading-system-info="loading"
        @execute-action="executeServerAction"
        @load-accounts="loadRealServerAccounts"
        @load-system-info="loadRealSystemInfo"
        @refresh-data="refreshServerData"
      />

      <!-- Hosting View -->
      <HostingView 
        v-else-if="view === 'hosting'"
        :hosting-accounts="hostingAccounts"
        :hosting-domains="hostingDomains"
        :hosting-disk-usage="hostingDiskUsage"
        :hosting-summary="hostingSummary"
        :loading-accounts="hostingDataLoading"
        :loading-domains="hostingDataLoading"
        :loading-disk-usage="hostingDataLoading"
        @load-hosting-data="loadRealHostingData"
        @manage-account="manageHostingAccount"
        @suspend-account="suspendHostingAccount"
        @manage-domain="manageDomain"
      />

      <!-- Simple Views -->
      <div v-else-if="view === 'session'" class="simple-view">
        <div class="simple-container">
          <h1>Session Viewer</h1>
          <p>ID Number: <input type="text" v-model="id" class="simple-input"></p>
          <BaseButton @click="aiView">Submit</BaseButton>
          <p class="simple-text">Message History: {{ messageHistory }}</p>
          <BaseButton @click="view = 'AI'" variant="secondary">Back</BaseButton>
        </div>
      </div>
      
      <div v-else-if="view === 'log'" class="simple-view">
        <div class="simple-container">
          <h1>History Log</h1>
          <pre class="log-content">{{ historyLog }}</pre>
          <BaseButton @click="view = 'AI'" variant="secondary">Back</BaseButton>
      </div>
    </div>

      <div v-else-if="view === 'clientInput'" class="simple-view">
        <div class="simple-container">
          <h1>Client Info Input</h1>
          <div class="client-form">
            <p>Client ID: <input type="text" v-model="clientId" class="simple-input"></p>
            <p>Product: <input type="text" v-model="product" class="simple-input"></p>
            <p>Server: <input type="text" v-model="server" class="simple-input"></p>
            <BaseButton @click="enterClientInfo">Submit</BaseButton>
            <BaseButton @click="view = 'AI'" variant="secondary">Back</BaseButton>
        </div>
        </div>
        </div>
    </div>
  </div>
</template>

<script>
import AppHeader from './components/layout/AppHeader.vue'
import DashboardView from './components/views/DashboardView.vue'
import AIAssistantView from './components/views/AIAssistantView.vue'
import TicketsView from './components/views/TicketsView.vue'
import ClientsView from './components/views/ClientsView.vue'
import ServersView from './components/views/ServersView.vue'
import HostingView from './components/views/HostingView.vue'
import BaseButton from './components/ui/BaseButton.vue'

export default {
  name: 'App',
  components: {
    AppHeader,
    DashboardView,
    AIAssistantView,
    TicketsView,
    ClientsView,
    ServersView,
    HostingView,
    BaseButton
  },
  computed: {
    isFullscreenView() {
      return this.view === 'servers' || this.view === 'tickets' || this.view === 'AI'
    }
  },
  data() {
    return {
      view: 'dashboard',
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
        { id: 'create-ticket', label: 'New Ticket', icon: '🎫', color: '#3b82f6' },
        { id: 'add-client', label: 'Add Client', icon: '👤', color: '#10b981' },
        { id: 'ai-assistant', label: 'AI Assistant', icon: '🤖', color: '#8b5cf6' },
        { id: 'create-invoice', label: 'Invoice', icon: '💰', color: '#f59e0b' },
        { id: 'backup-account', label: 'Backup', icon: '💾', color: '#059669' },
        { id: 'check-ssl', label: 'SSL Check', icon: '🔒', color: '#ef4444' },
        { id: 'system-status', label: 'System', icon: '⚡', color: '#06b6d4' }
      ],
      recentActivities: [],
      realDataLoaded: false,
      dashboardLoading: false
    }
  },
  methods: {
    handleViewChange(newView) {
      this.view = newView
    },
    getViewTitle() {
      const titles = {
        dashboard: 'Admin Dashboard',
        AI: 'AI Assistant',
        tickets: 'Support Tickets',
        clients: 'Client Management',
        servers: 'Server Management',
        hosting: 'Hosting Management',
        session: 'Session Viewer',
        log: 'History Log',
        clientInput: 'Client Information'
      }
      return titles[this.view] || 'Admin Dashboard'
    },
    getViewSubtitle() {
      const subtitles = {
        dashboard: 'Hosting & Client Management',
        AI: 'AI-Powered Customer Support',
        tickets: 'Manage customer support requests',
        clients: 'WHMCS Client Administration',
        servers: 'cPanel & WHM Administration',
        hosting: 'Hosting Account Management'
      }
      return subtitles[this.view] || 'System Management'
    },
    getViewIcon() {
      const icons = {
        dashboard: '🏠',
        AI: '🤖',
        tickets: '🎫',
        clients: '👥',
        servers: '🖥️',
        hosting: '🏠'
      }
      return icons[this.view] || '🏠'
    },
    handleSearch(query) {
      this.searchQuery = query
      console.log('Searching for:', query)
    },
    executeQuickAction(actionId) {
      switch(actionId) {
        case 'create-ticket':
          this.view = 'tickets'
          break
        case 'add-client':
          this.view = 'clients'
          break
        case 'ai-assistant':
          this.view = 'AI'
          break
        default:
          console.log('Action:', actionId)
      }
    },
    sendAIMessage(message) {
      this.aiResponse.push({
        role: 'user',
        content: message,
        timestamp: Date.now()
      })
      // AI response logic would go here
    },
    aiClear() {
      this.aiResponse = []
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
    aiView() {
      console.log('AI View for ID:', this.id)
    },
    enterClientInfo() {
      console.log('Client info entered')
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
      console.log('Loading clients...')
    },
    executeServerAction(action) {
      console.log('Server action:', action)
    },
    async loadRealServerAccounts() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/accounts');
        if (response.ok) {
          const data = await response.json();
          this.cpanelAccounts = data.accounts || [];
          console.log('Server accounts loaded:', this.cpanelAccounts.length);
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
  watch: {
    view(newView) {
      if (newView === 'tickets') {
        this.loadRealTickets()
      }
    }
  },
  mounted() {
    this.loadRealDashboardData()
  }
}
</script>

<style scoped>
.app {
  margin: 0;
  padding: 0;
  width: 100%;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
}

.app-content {
  width: 100%;
}

.app-content.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 1000;
  background: #f8fafc;
  overflow: auto;
}

.simple-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
  padding: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.simple-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  max-width: 500px;
  width: 100%;
}

.simple-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  margin-left: 8px;
}

.log-content {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  font-family: monospace;
  font-size: 12px;
  max-height: 400px;
  overflow-y: auto;
  margin: 16px 0;
}

.client-form p {
  margin: 12px 0;
  display: flex;
  align-items: center;
}
</style>
