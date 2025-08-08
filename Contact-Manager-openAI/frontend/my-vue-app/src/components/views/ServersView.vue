<template>
  <div class="servers-view">
    <!-- Fullscreen Navigation Bar -->
    <div class="fullscreen-nav">
      <div class="nav-left">
        <button class="nav-btn back-btn" @click="$router.push('/dashboard')">
          ← Dashboard
        </button>
      </div>
      <div class="nav-center">
        <h1 class="view-title">🖥️ Server Management</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="$router.push('/tickets')">
          🎫 Tickets
        </button>
        <button class="nav-btn" @click="$router.push('/hosting')">
          🏠 Hosting
        </button>
        <button class="nav-btn" @click="$router.push('/ai')">
          🤖 AI Assistant
        </button>
      </div>
    </div>

    <div class="servers-content">
      <!-- Server Quick Actions -->
      <div class="server-actions">
        <h3>Server Actions</h3>
        
        <!-- Action Results Notifications -->
        <div v-if="recentActivities.length > 0 && recentActivities[0].time === 'Just now'" class="action-notification">
          <div :class="['notification', getNotificationClass(recentActivities[0])]">
            <span class="notification-icon">{{ recentActivities[0].icon }}</span>
            <span class="notification-text">{{ recentActivities[0].title }}</span>
            <button @click="clearNotification" class="notification-close">×</button>
          </div>
        </div>
        
        <div class="actions-grid">
          <BaseButton @click="executeAction('disk-usage')" variant="info" class="action-btn">
            💾 Disk Usage
          </BaseButton>
          <BaseButton @click="executeAction('system-load')" variant="success" class="action-btn">
            📊 System Load
          </BaseButton>
          <BaseButton @click="executeAction('account-list')" variant="warning" class="action-btn">
            👥 Accounts
          </BaseButton>
          <BaseButton @click="executeAction('email-accounts')" variant="info" class="action-btn">
            📧 Email
          </BaseButton>
          <BaseButton @click="executeAction('ssl-status')" variant="danger" class="action-btn">
            🔒 SSL Status
          </BaseButton>
          <BaseButton @click="executeAction('backup-status')" variant="secondary" class="action-btn">
            💿 Backups
          </BaseButton>
        </div>
      </div>

      <!-- Server Dashboard Grid -->
      <div class="dashboard-grid">
        <!-- System Resources Widget -->
        <DashboardWidget title="System Resources" subtitle="Real-time monitoring" icon="⚡" icon-color="#3b82f6">
          <div class="resource-metrics">
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">CPU Usage</span>
                <span class="metric-value">{{ dynamicServerStats.cpuUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill cpu" :style="`width: ${dynamicServerStats.cpuUsage}%`"></div>
                <div class="metric-percent">{{ dynamicServerStats.cpuUsage }}%</div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Memory Usage</span>
                <span class="metric-value">{{ dynamicServerStats.memoryUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill memory" :style="`width: ${dynamicServerStats.memoryUsage}%`"></div>
                <div class="metric-percent">{{ dynamicServerStats.memoryUsage }}%</div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Disk Usage</span>
                <span class="metric-value">{{ dynamicServerStats.diskUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill disk" :style="`width: ${dynamicServerStats.diskUsage}%`"></div>
                <div class="metric-percent">{{ dynamicServerStats.diskUsage }}%</div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Network I/O</span>
                <span class="metric-value">{{ dynamicServerStats.networkIO }} MB/s</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill network" :style="`width: ${Math.min(dynamicServerStats.networkIO * 10, 100)}%`"></div>
                <div class="metric-percent">{{ dynamicServerStats.networkIO }} MB/s</div>
              </div>
            </div>
          </div>
        </DashboardWidget>

        <!-- Active Accounts Widget -->
        <DashboardWidget title="Active Accounts" subtitle="cPanel accounts overview" icon="👥" icon-color="#10b981">
          <div v-if="loadingAccounts" class="loading-state">
            <span class="loading-spinner">⟳</span>
            <p>Loading accounts...</p>
          </div>
          <div v-else-if="cpanelAccounts.length === 0" class="empty-state">
            <span>👥</span>
            <p>No accounts found</p>
          </div>
          <div v-else class="accounts-list">
            <div v-for="account in cpanelAccounts.slice(0, 5)" :key="account.Username" class="account-item">
              <div class="account-info">
                <div class="account-name">{{ account.Username || 'Unknown' }}</div>
                <div class="account-domain">{{ account.Domain || 'No domain' }}</div>
              </div>
              <div class="account-stats">
                <span class="disk-usage">{{ account.Email || 'No email' }}</span>
                <span :class="['account-status', account.Status === 'Suspended' ? 'suspended' : 'active']">
                  {{ account.Status || 'Active' }}
                </span>
              </div>
            </div>
            <BaseButton @click="showAllAccounts" variant="secondary" size="sm" class="view-all-btn">
              View All ({{ cpanelAccounts.length }})
            </BaseButton>
          </div>
        </DashboardWidget>

        <!-- Recent Activities Widget -->
        <DashboardWidget title="Server Activities" subtitle="Recent server events" icon="📋" icon-color="#f59e0b">
          <div class="activities-list">
            <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
              <div class="activity-icon">{{ activity.icon }}</div>
              <div class="activity-content">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-time">{{ activity.time }}</div>
              </div>
              <span :class="['activity-type', activity.type]">{{ activity.type }}</span>
            </div>
          </div>
        </DashboardWidget>
      </div>

      <!-- Detailed Server Information -->
      <div class="server-details">
        <DashboardWidget title="Server Information" subtitle="System details and configuration" icon="🖥️" icon-color="#8b5cf6">
          <div v-if="hostingDataLoading" class="loading-state">
            <span class="loading-spinner">⟳</span>
            <p>Loading system information...</p>
          </div>
          <div v-else class="system-info-grid">
            <div class="info-section">
              <h4>System Details</h4>
              <div class="info-item">
                <span class="info-label">Hostname:</span>
                <span class="info-value">{{ hostingSystemInfo.hostname || 'server.easyonnet.io' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">OS Version:</span>
                <span class="info-value">{{ hostingSystemInfo.os || 'CentOS Linux 8.4' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Kernel:</span>
                <span class="info-value">{{ hostingSystemInfo.kernel || '4.18.0-305.x86_64' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Uptime:</span>
                <span class="info-value">{{ hostingSystemInfo.uptime || '15 days, 4 hours, 23 mins' }}</span>
              </div>
            </div>

            <div class="info-section">
              <h4>Hardware</h4>
              <div class="info-item">
                <span class="info-label">CPU Cores:</span>
                <span class="info-value">{{ hostingSystemInfo.cpuCores || '8 cores (Intel Xeon)' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Total RAM:</span>
                <span class="info-value">{{ hostingSystemInfo.totalRam || '32 GB DDR4' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Total Disk:</span>
                <span class="info-value">{{ hostingSystemInfo.totalDisk || '1TB NVMe SSD' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Load Average:</span>
                <span class="info-value">{{ hostingSystemInfo.loadAverage || realTimeStats.loadAverage || '0.85, 0.72, 0.68' }}</span>
              </div>
            </div>

            <div class="info-section">
              <h4>Services</h4>
              <div class="service-status">
                <div v-for="service in displayServices" :key="service.name" class="service-item">
                  <span class="service-name">{{ service.name }}</span>
                  <span :class="['service-status-badge', service.status]">{{ service.status }}</span>
                </div>
              </div>
            </div>
          </div>
        </DashboardWidget>
      </div>
    </div>

    <!-- Popup for detailed data -->
    <div v-if="showPopup" class="popup-overlay" @click="closePopup">
      <div class="popup-content" @click.stop>
        <div class="popup-header">
          <h3>{{ popupTitle }}</h3>
          <button @click="closePopup" class="close-popup-btn">×</button>
        </div>
        
        <!-- Disk Usage Popup -->
        <div v-if="popupType === 'disk-usage'" class="popup-body">
          <div class="disk-usage-summary">
            <div class="summary-stats">
              <div class="stat-card">
                <span class="stat-label">Total Accounts</span>
                <span class="stat-value">{{ popupData.length }}</span>
              </div>
              <div class="stat-card">
                <span class="stat-label">Total Usage</span>
                <span class="stat-value">{{ getTotalDiskUsage() }}</span>
              </div>
            </div>
          </div>
          <div class="accounts-grid">
            <div v-for="(item, index) in popupData" :key="index" class="disk-account-card">
              <div class="account-header">
                <div class="account-name">
                  <span class="username">{{ item.User }}</span>
                  <span class="usage-badge">{{ formatDiskUsage(item['Disk Blocks Used']) }}</span>
                </div>
              </div>
              <div class="account-details">
                <div class="detail-row">
                  <span class="detail-label">📁 Blocks Used:</span>
                  <span class="detail-value">{{ formatNumber(item['Disk Blocks Used']) }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">📄 Inodes Used:</span>
                  <span class="detail-value">{{ formatNumber(item['Inodes Used']) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- System Load Popup -->
        <div v-else-if="popupType === 'system-load'" class="popup-body">
          <div v-for="(item, index) in popupData" :key="index" class="load-info-card">
            <div class="load-header">
              <h4>🔥 System Load Averages</h4>
              <span class="load-status-badge">{{ getLoadStatus(item.data?.one) }}</span>
            </div>
            <div class="load-metrics">
              <div class="load-metric">
                <span class="metric-period">1 min</span>
                <span class="metric-value">{{ item.data?.one || 'N/A' }}</span>
                <div class="metric-bar">
                  <div class="metric-fill" :style="`width: ${Math.min(parseFloat(item.data?.one || 0) * 25, 100)}%`"></div>
                </div>
              </div>
              <div class="load-metric">
                <span class="metric-period">5 min</span>
                <span class="metric-value">{{ item.data?.five || 'N/A' }}</span>
                <div class="metric-bar">
                  <div class="metric-fill" :style="`width: ${Math.min(parseFloat(item.data?.five || 0) * 25, 100)}%`"></div>
                </div>
              </div>
              <div class="load-metric">
                <span class="metric-period">15 min</span>
                <span class="metric-value">{{ item.data?.fifteen || 'N/A' }}</span>
                <div class="metric-bar">
                  <div class="metric-fill" :style="`width: ${Math.min(parseFloat(item.data?.fifteen || 0) * 25, 100)}%`"></div>
                </div>
              </div>
            </div>
            <div class="api-status">
              <span class="status-label">API Status:</span>
              <span class="status-value">{{ item.metadata?.reason || 'OK' }}</span>
            </div>
          </div>
        </div>

        <!-- SSL Status Popup -->
        <div v-else-if="popupType === 'ssl-status'" class="popup-body">
          <div class="ssl-grid">
            <div v-for="(item, index) in popupData" :key="index" class="ssl-card">
              <div class="ssl-header">
                <span class="ssl-domain">🔒 {{ item.domain || item.name }}</span>
                <span :class="['ssl-status-badge', getSSLStatusClass(item.status || item.ssl_status)]">
                  {{ item.status || item.ssl_status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Email Accounts Popup -->
        <div v-else-if="popupType === 'email-accounts'" class="popup-body">
          <div class="email-summary">
            <div class="summary-header">
              <span class="summary-title">📧 Email Accounts Overview</span>
              <span class="summary-count">{{ popupData.length }} accounts</span>
            </div>
          </div>
          <div class="email-accounts-grid">
            <div v-for="(item, index) in popupData" :key="index" class="email-account-card">
              <div class="email-header">
                <span class="email-address">{{ item.email || item.account || item.user }}</span>
                <span :class="['email-status-badge', (item.status || 'active').toLowerCase()]">
                  {{ item.status || 'Active' }}
                </span>
              </div>
              <div class="email-details">
                <div class="email-detail">
                  <span class="detail-label">📬 Mailbox:</span>
                  <span class="detail-value">{{ item.mailbox || item.quota || 'Unlimited' }}</span>
                </div>
                <div class="email-detail" v-if="item.usage">
                  <span class="detail-label">💾 Usage:</span>
                  <span class="detail-value">{{ item.usage }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Backup Status Popup -->
        <div v-else-if="popupType === 'backup-status'" class="popup-body">
          <div class="backup-grid">
            <div v-for="(item, index) in popupData" :key="index" class="backup-card">
              <div class="backup-header">
                <span class="backup-name">💿 {{ item.name || item.backup_name }}</span>
                <span :class="['backup-status-badge', (item.status || item.backup_status || 'unknown').toLowerCase()]">
                  {{ item.status || item.backup_status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Server Accounts Popup -->
        <div v-else-if="popupType === 'accounts'" class="popup-body">
          <div class="accounts-summary">
            <div class="summary-header">
              <span class="summary-title">👥 Server Accounts</span>
              <span class="summary-count">{{ popupData.length }} accounts</span>
            </div>
          </div>
          <div class="server-accounts-grid">
            <div v-for="(account, index) in popupData" :key="index" class="server-account-card">
              <div class="account-main-info">
                <div class="account-identity">
                  <span class="account-username">{{ account.Username || 'Unknown' }}</span>
                  <span class="account-domain">{{ account.Domain || 'No domain' }}</span>
                </div>
                <span :class="['account-status-badge', (account.Status || 'active').toLowerCase()]">
                  {{ account.Status || 'Active' }}
                </span>
              </div>
              <div class="account-contact-info">
                <div class="contact-detail">
                  <span class="contact-label">📧 Email:</span>
                  <span class="contact-value">{{ account.Email || 'No email' }}</span>
                </div>
                <div class="contact-detail" v-if="account.Plan">
                  <span class="contact-label">📋 Plan:</span>
                  <span class="contact-value">{{ account.Plan }}</span>
                </div>
                <div class="contact-detail" v-if="account.DiskUsed">
                  <span class="contact-label">💾 Disk:</span>
                  <span class="contact-value">{{ account.DiskUsed }} / {{ account.DiskLimit || 'Unlimited' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Fallback for unknown types -->
        <div v-else class="popup-body">
          <div class="raw-data-section">
            <h4>📋 Raw Data</h4>
            <pre class="raw-data-content">{{ JSON.stringify(popupData, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'
import DashboardWidget from '../ui/DashboardWidget.vue'

export default {
  name: 'ServersView',
  components: {
    BaseButton,
    DashboardWidget
  },
  props: {
    serverStats: {
      type: Object,
      default: () => ({
        cpuUsage: 23,
        memoryUsage: 67,
        diskUsage: 45,
        networkIO: 1.2
      })
    },
    cpanelAccounts: {
      type: Array,
      default: () => []
    },
    hostingSystemInfo: {
      type: Object,
      default: () => ({
        hostname: '',
        os: '',
        kernel: '',
        uptime: '',
        cpuCores: '',
        totalRam: '',
        totalDisk: '',
        loadAverage: '',
        services: []
      })
    },
    loadingAccounts: {
      type: Boolean,
      default: false
    },
    hostingDataLoading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['execute-action', 'load-accounts', 'load-system-info', 'refresh-data', 'view-change'],
  data() {
    return {
      recentActivities: [
        { id: 1, icon: '🔄', title: 'System backup completed', time: '5 min ago', type: 'backup' },
        { id: 2, icon: '⚠️', title: 'High memory usage detected', time: '12 min ago', type: 'warning' },
        { id: 3, icon: '✅', title: 'SSL certificate renewed', time: '1 hour ago', type: 'ssl' },
        { id: 4, icon: '👤', title: 'New account created', time: '2 hours ago', type: 'account' },
        { id: 5, icon: '🔧', title: 'Server configuration updated', time: '3 hours ago', type: 'config' }
      ],
      realTimeStats: {
        cpuUsage: 0,
        memoryUsage: 0,
        diskUsage: 0,
        networkIO: 0,
        loadAverage: 'N/A'
      },
      actionResults: {},
      // Popup system
      showPopup: false,
      popupTitle: '',
      popupData: [],
      popupType: ''
    }
  },
  computed: {
    dynamicServerStats() {
      // Use real-time stats if available, otherwise fallback to props
      return {
        cpuUsage: this.realTimeStats.cpuUsage || this.serverStats.cpuUsage,
        memoryUsage: this.realTimeStats.memoryUsage || this.serverStats.memoryUsage,
        diskUsage: this.realTimeStats.diskUsage || this.serverStats.diskUsage,
        networkIO: this.realTimeStats.networkIO || this.serverStats.networkIO
      }
    },
    displayServices() {
      // Show real services if available, otherwise show default services
      if (this.hostingSystemInfo.services && this.hostingSystemInfo.services.length > 0) {
        return this.hostingSystemInfo.services
      }
      return [
        { name: 'Apache HTTP Server', status: 'running' },
        { name: 'MySQL Database', status: 'running' },
        { name: 'PHP-FPM', status: 'running' },
        { name: 'SSH Daemon', status: 'running' },
        { name: 'DNS Server', status: 'running' },
        { name: 'Mail Server', status: 'running' },
        { name: 'FTP Server', status: 'stopped' },
        { name: 'Firewall', status: 'running' }
      ]
    }
  },
  methods: {
    async executeAction(actionId) {
      console.log('Executing server action:', actionId)
      
      try {
        switch(actionId) {
          case 'disk-usage':
            await this.getDiskUsage()
            break
          case 'system-load':
            await this.getSystemLoad()
            break
          case 'account-list':
            // Show all server accounts in popup
            this.showActionResult('account-list', `Found ${this.cpanelAccounts.length} server accounts`, 'success')
            this.showDataPopup('All Server Accounts', this.cpanelAccounts, 'accounts')
            break
          case 'email-accounts':
            await this.getEmailAccounts()
            break
          case 'ssl-status':
            await this.getSSLStatus()
            break
          case 'backup-status':
            await this.getBackupStatus()
            break
          default:
            this.$emit('execute-action', actionId)
        }
      } catch (error) {
        console.error(`Error executing ${actionId}:`, error)
        this.showActionResult(actionId, `Error: ${error.message}`, 'error')
      }
    },

    async getDiskUsage() {
      try {
        const response = await fetch('http://localhost:8080/contacts/hosting/disk-usage')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        
        console.log('Disk usage API response:', data) // Debug log
        
        if (data.success) {
          // Parse the disk usage data properly
          let diskUsagePercent = this.serverStats.diskUsage
          let summaryMessage = 'Disk usage data refreshed'
          
          if (data.usagePercentage) {
            // Use the calculated percentage from the API
            diskUsagePercent = Math.min(Math.round(data.usagePercentage), 100)
            summaryMessage = `Disk usage: ${diskUsagePercent}% (${data.accounts} accounts)`
          } else if (data.diskUsage && Array.isArray(data.diskUsage)) {
            // Fallback calculation
            const totalUsage = data.diskUsage.reduce((sum, account) => {
              const usage = parseFloat(account['Disk Blocks Used']) || 0
              return sum + usage
            }, 0)
            diskUsagePercent = Math.min(Math.round(totalUsage / 10000), 100) // Convert blocks to rough percentage
            summaryMessage = `Found ${data.diskUsage.length} accounts with disk usage data`
          }
          
          // Update real-time stats
          this.realTimeStats.diskUsage = diskUsagePercent
          this.showActionResult('disk-usage', summaryMessage, 'success')
          
          // Show detailed popup
          this.showDataPopup('Disk Usage Details', data.diskUsage || [], 'disk-usage')
        } else {
          throw new Error(data.error || 'Failed to fetch disk usage')
        }
      } catch (error) {
        this.showActionResult('disk-usage', `Error: ${error.message}`, 'error')
      }
    },

    async getSystemLoad() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/load')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        
        console.log('System load API response:', data) // Debug log
        
        if (data.success) {
          // Extract load averages from the response
          let loadMessage = 'System load data refreshed'
          
          if (data.data && data.data.data) {
            const loadData = data.data.data
            const oneMin = parseFloat(loadData.one) || 0
            const fiveMin = parseFloat(loadData.five) || 0
            const fifteenMin = parseFloat(loadData.fifteen) || 0
            
            // Convert load to rough CPU percentage (assuming 4 cores)
            const cpuPercent = Math.min(Math.round(oneMin * 25), 100)
            
            this.realTimeStats.cpuUsage = cpuPercent
            this.realTimeStats.loadAverage = `${oneMin}, ${fiveMin}, ${fifteenMin}`
            
            loadMessage = `Load avg: ${oneMin}, ${fiveMin}, ${fifteenMin}`
          }
          
          this.showActionResult('system-load', loadMessage, 'success')
          
          // Show detailed popup with system load data
          this.showDataPopup('System Load Details', [data.data], 'system-load')
        } else {
          throw new Error(data.error || 'Failed to fetch system load')
        }
      } catch (error) {
        this.showActionResult('system-load', `Error: ${error.message}`, 'error')
      }
    },

    async getEmailAccounts() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/email')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        
        console.log('Email accounts API response:', data) // Debug log
        
        if (data.success) {
          const accountCount = data.accounts?.length || 0
          this.showActionResult('email-accounts', `Found ${accountCount} email accounts`, 'success')
          
          // Show detailed popup
          this.showDataPopup('Email Accounts', data.accounts || [], 'email-accounts')
        } else {
          throw new Error(data.error || 'Failed to fetch email accounts')
        }
      } catch (error) {
        this.showActionResult('email-accounts', `Error: ${error.message}`, 'error')
      }
    },

    async getSSLStatus() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/ssl')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        
        console.log('SSL status API response:', data) // Debug log
        
        if (data.success) {
          this.showActionResult('ssl-status', 'SSL status checked successfully', 'success')
          
          // Show detailed popup
          this.showDataPopup('SSL Status', data.certificates || data.ssl || [], 'ssl-status')
        } else {
          throw new Error(data.error || 'Failed to check SSL status')
        }
      } catch (error) {
        this.showActionResult('ssl-status', `Error: ${error.message}`, 'error')
      }
    },

    async getBackupStatus() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/backup')
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const data = await response.json()
        
        console.log('Backup status API response:', data) // Debug log
        
        if (data.success) {
          this.showActionResult('backup-status', 'Backup status retrieved', 'success')
          
          // Show detailed popup
          this.showDataPopup('Backup Status', data.backups || data.status || [], 'backup-status')
        } else {
          throw new Error(data.error || 'Failed to get backup status')
        }
      } catch (error) {
        this.showActionResult('backup-status', `Error: ${error.message}`, 'error')
      }
    },

    showActionResult(actionId, message, type) {
      // Add to recent activities
      const newActivity = {
        id: Date.now(),
        icon: type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️',
        title: message,
        time: 'Just now',
        type: actionId.replace('-', '')
      }
      this.recentActivities.unshift(newActivity)
      
      // Keep only last 5 activities
      if (this.recentActivities.length > 5) {
        this.recentActivities.pop()
      }
    },

    showAllAccounts() {
      console.log('Show all accounts view')
      this.showDataPopup('All Server Accounts', this.cpanelAccounts, 'accounts')
    },

    // Auto-refresh system stats every 30 seconds
    startAutoRefresh() {
      this.refreshInterval = setInterval(() => {
        this.getSystemLoadSilently()
        this.getDiskUsageSilently()
      }, 30000) // 30 seconds
    },

    // Silent versions that don't show notifications
    async getSystemLoadSilently() {
      try {
        const response = await fetch('http://localhost:8080/contacts/server/load')
        if (!response.ok) return
        const data = await response.json()
        
        if (data.success && data.data && data.data.data) {
          const loadData = data.data.data
          const oneMin = parseFloat(loadData.one) || 0
          const cpuPercent = Math.min(Math.round(oneMin * 25), 100)
          
          this.realTimeStats.cpuUsage = cpuPercent
          this.realTimeStats.loadAverage = `${oneMin}, ${loadData.five}, ${loadData.fifteen}`
        }
      } catch (error) {
        // Silent fail for auto-refresh
      }
    },

    async getDiskUsageSilently() {
      try {
        const response = await fetch('http://localhost:8080/contacts/hosting/disk-usage')
        if (!response.ok) return
        const data = await response.json()
        
        if (data.success && data.usagePercentage) {
          this.realTimeStats.diskUsage = Math.min(Math.round(data.usagePercentage), 100)
        }
      } catch (error) {
        // Silent fail for auto-refresh
      }
    },

    showDataPopup(title, data, type) {
      this.popupTitle = title
      this.popupData = data
      this.popupType = type
      this.showPopup = true
    },

    closePopup() {
      this.showPopup = false
      this.popupTitle = ''
      this.popupData = []
      this.popupType = ''
    },

    getNotificationClass(activity) {
      if (activity.type === 'diskusage' || activity.type === 'systemload') {
        return 'success'
      }
      return 'info'
    },

    clearNotification() {
      this.recentActivities.shift() // Remove the first activity
    },

    formatDiskUsage(bytes) {
      if (bytes === null || bytes === undefined) return '0 MB'
      const kb = bytes / 1024
      if (kb < 1024) return `${kb.toFixed(2)} KB`
      const mb = kb / 1024
      if (mb < 1024) return `${mb.toFixed(2)} MB`
      const gb = mb / 1024
      return `${gb.toFixed(2)} GB`
    },

    formatNumber(num) {
      if (num === null || num === undefined) return '0'
      return num.toLocaleString()
    },

    getTotalDiskUsage() {
      if (!this.popupData || !Array.isArray(this.popupData)) return '0 MB'
      const totalBytes = this.popupData.reduce((sum, account) => {
        const usage = parseFloat(account['Disk Blocks Used']) || 0
        return sum + usage
      }, 0)
      return this.formatDiskUsage(totalBytes)
    },

    getLoadStatus(load) {
      if (load === null || load === undefined) return 'N/A'
      const loadValue = parseFloat(load)
      if (loadValue < 0.5) return 'normal'
      if (loadValue < 1.0) return 'warning'
      return 'critical'
    },

    getSSLStatusClass(status) {
      if (!status) return 'unknown'
      const statusLower = status.toLowerCase()
      if (statusLower === 'valid' || statusLower === 'active' || statusLower === 'running') return 'running'
      if (statusLower === 'expired' || statusLower === 'inactive' || statusLower === 'stopped') return 'stopped'
      return 'unknown'
    }
  },
  mounted() {
    this.$emit('load-accounts')
    this.$emit('load-system-info')
    this.startAutoRefresh() // Start auto-refresh on mount
  },
  beforeUnmount() {
    clearInterval(this.refreshInterval) // Clear interval on unmount
  }
}
</script>

<style scoped>
.servers-view {
  background: radial-gradient(1100px 700px at -10% 0%, rgba(99,102,241,0.06), transparent 60%),
              radial-gradient(1100px 700px at 110% 100%, rgba(147,51,234,0.06), transparent 60%),
              linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  height: 100%;
  padding: 0;
  overflow: auto;
}

.fullscreen-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  padding: 16px 24px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left, .nav-right { display: flex; gap: 12px; }
.nav-center { flex: 1; text-align: center; }
.view-title { margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; }

.nav-btn { padding: 8px 16px; border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 8px; background: rgba(255, 255, 255, 0.8); color: #64748b; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
.nav-btn:hover { background: white; color: #0f172a; border-color: #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.back-btn { background: #3b82f6; color: white; border-color: #3b82f6; }
.back-btn:hover { background: #2563eb; border-color: #2563eb; }

.servers-content {
  width: 100%;
  max-width: none;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 24px;
  height: 100%;
  padding: 24px;
}

.server-actions {
  background: rgba(255, 255, 255, 0.7);
  padding: 16px 24px;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.5);
  box-shadow: 0 10px 20px rgba(2,6,23,0.06);
}

.server-actions h3 { margin: 0 0 12px 0; font-size: 18px; font-weight: 800; color: #0f172a; }
.actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
.action-btn { padding: 12px 16px; justify-content: center; border-radius: 12px; }

.dashboard-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }

.resource-metrics { display: flex; flex-direction: column; gap: 20px; }
.metric-item { display: flex; flex-direction: column; gap: 8px; }
.metric-header { display: flex; justify-content: space-between; align-items: center; }
.metric-label { font-size: 14px; color: #64748b; font-weight: 600; }
.metric-value { font-size: 16px; font-weight: 800; color: #0f172a; }
.metric-bar { height: 10px; background: rgba(226, 232, 240, 0.9); border-radius: 6px; overflow: hidden; position: relative; }
.metric-fill { height: 100%; border-radius: 6px; transition: width 0.6s ease; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.3); }
.metric-fill.cpu { background: linear-gradient(90deg, #3b82f6, #1e40af); }
.metric-fill.memory { background: linear-gradient(90deg, #10b981, #059669); }
.metric-fill.disk { background: linear-gradient(90deg, #f59e0b, #d97706); }
.metric-fill.network { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
.metric-percent { position: absolute; top: 50%; right: 8px; transform: translateY(-50%); font-size: 11px; font-weight: 800; color: #0f172a; opacity: 0.8; }

.loading-state, .empty-state { text-align: center; padding: 40px 20px; color: #64748b; }
.loading-spinner { animation: spin 1s linear infinite; font-size: 24px; display: block; margin-bottom: 8px; }
.empty-state span { font-size: 32px; display: block; margin-bottom: 8px; }

.accounts-list { display: flex; flex-direction: column; gap: 12px; max-height: 300px; overflow: auto; padding-right: 6px; }
.account-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.3); }
.account-item:hover { background: rgba(248,250,252,0.6); border-radius: 8px; padding-left: 8px; }
.account-info { flex: 1; }
.account-name { font-weight: 700; color: #0f172a; font-size: 14px; }
.account-domain { font-size: 12px; color: #64748b; }
.account-stats { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.disk-usage { font-size: 12px; color: #64748b; }
.account-status { padding: 2px 6px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.account-status.active { background: #d1fae5; color: #059669; }
.account-status.suspended { background: #fef3c7; color: #d97706; }
.view-all-btn { margin-top: 8px; align-self: flex-start; }

.activities-list { display: flex; flex-direction: column; gap: 12px; max-height: 300px; overflow: auto; padding-right: 6px; }
.activity-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; }
.activity-icon { font-size: 16px; width: 24px; text-align: center; }
.activity-content { flex: 1; }
.activity-title { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
.activity-time { font-size: 11px; color: #64748b; }
.activity-type { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.activity-type.backup { background: #dbeafe; color: #1e40af; }
.activity-type.warning { background: #fef3c7; color: #d97706; }
.activity-type.ssl { background: #d1fae5; color: #059669; }
.activity-type.account { background: #ede9fe; color: #7c3aed; }
.activity-type.config { background: #f3f4f6; color: #6b7280; }

.server-details { grid-column: 1 / -1; }
.system-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.info-section h4 { margin: 0 0 16px 0; font-size: 16px; font-weight: 800; color: #374151; border-bottom: 1px solid rgba(226, 232, 240, 0.6); padding-bottom: 8px; }
.info-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(226, 232, 240, 0.3); }
.info-item:last-child { border-bottom: none; }
.info-label { font-size: 14px; color: #64748b; font-weight: 600; }
.info-value { font-size: 14px; color: #0f172a; font-weight: 700; }

.service-status { display: flex; flex-direction: column; gap: 8px; max-height: 260px; overflow: auto; padding-right: 6px; }
.service-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; }
.service-name { font-size: 14px; color: #0f172a; font-weight: 600; }
.service-status-badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.service-status-badge.running { background: #d1fae5; color: #059669; }
.service-status-badge.stopped { background: #fee2e2; color: #dc2626; }
.service-status-badge.disabled { background: #f3f4f6; color: #6b7280; }

.action-notification { margin-bottom: 16px; display: flex; justify-content: center; }
.notification { display: flex; align-items: center; padding: 10px 15px; border-radius: 10px; font-size: 14px; font-weight: 700; color: white; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: relative; }
.notification.success { background: linear-gradient(135deg, #10b981, #059669); }
.notification.info { background: linear-gradient(135deg, #3b82f6, #1e40af); }
.notification-icon { margin-right: 8px; font-size: 18px; }
.notification-text { flex: 1; }
.notification-close { position: absolute; top: 5px; right: 5px; background: rgba(255, 255, 255, 0.5); border: none; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; color: #64748b; transition: background 0.2s ease; }
.notification-close:hover { background: rgba(255, 255, 255, 0.8); }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Popup Styles */
.popup-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.popup-content { background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 1000px; max-height: 90%; overflow-y: auto; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); position: relative; }
.popup-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.popup-header h3 { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; }
.close-popup-btn { background: #f3f4f6; color: #64748b; border: none; border-radius: 6px; padding: 8px 12px; cursor: pointer; font-size: 14px; font-weight: 700; transition: background 0.2s ease; }
.close-popup-btn:hover { background: #e2e8f0; }
.popup-body { display: flex; flex-direction: column; gap: 20px; }

.disk-usage-summary, .email-summary, .accounts-summary { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 15px; display: flex; justify-content: space-between; align-items: center; gap: 15px; }
.summary-stats, .summary-header { display: flex; justify-content: space-between; align-items: center; width: 100%; }
.stat-card, .summary-title { font-size: 14px; font-weight: 700; color: #374151; }
.stat-value, .summary-count { font-size: 18px; font-weight: 800; color: #111827; }

.accounts-grid, .email-accounts-grid, .server-accounts-grid, .ssl-grid, .backup-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; }
.load-info-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; display: flex; flex-direction: column; gap: 15px; }
.disk-account-card, .email-account-card, .server-account-card, .ssl-card, .backup-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 15px; display: flex; flex-direction: column; gap: 10px; }
.account-header, .email-header, .ssl-header, .backup-header { display: flex; justify-content: space-between; align-items: center; }
.load-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.load-header h4 { margin: 0; font-size: 16px; font-weight: 800; color: #374151; }
.account-name, .email-address, .ssl-domain, .backup-name { font-size: 16px; font-weight: 800; color: #111827; }
.usage-badge { background: #e0f2fe; color: #1e40af; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-left: 8px; }
.account-details, .email-details { display: flex; flex-direction: column; gap: 8px; }
.load-metrics { display: flex; flex-direction: column; gap: 12px; }
.load-metric { display: flex; flex-direction: column; gap: 6px; }
.metric-period { font-size: 13px; color: #6b7280; font-weight: 600; }
.metric-value { font-size: 18px; font-weight: 800; color: #111827; }
.metric-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.metric-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; background: linear-gradient(135deg, #3b82f6, #1e40af); }
.detail-row, .email-detail, .contact-detail { display: flex; justify-content: space-between; align-items: center; }
.detail-label, .contact-label { font-size: 13px; color: #6b7280; font-weight: 600; }
.detail-value, .contact-value { font-size: 14px; color: #111827; font-weight: 700; text-align: right; }
.email-status-badge, .account-status-badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
.email-status-badge.active, .account-status-badge.active { background: #d1fae5; color: #059669; }
.email-status-badge.suspended, .account-status-badge.suspended { background: #fef3c7; color: #d97706; }
.load-status-badge, .ssl-status-badge, .backup-status-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
.load-status-badge.normal { background: #d1fae5; color: #059669; }
.load-status-badge.Warning { background: #fef3c7; color: #d97706; }
.load-status-badge.Critical { background: #fee2e2; color: #dc2626; }
.ssl-status-badge.running { background: #d1fae5; color: #059669; }
.ssl-status-badge.stopped { background: #fee2e2; color: #dc2626; }
.ssl-status-badge.unknown { background: #f3f4f6; color: #6b7280; }
.backup-status-badge.running { background: #d1fae5; color: #059669; }
.backup-status-badge.stopped { background: #fee2e2; color: #dc2626; }
.backup-status-badge.unknown { background: #f3f4f6; color: #6b7280; }

.raw-data-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(226, 232, 240, 0.3); }
.raw-data-section h4 { margin-top: 0; margin-bottom: 10px; font-size: 16px; font-weight: 800; color: #374151; }
.raw-data-content { background: #f3f4f6; padding: 10px; border-radius: 6px; font-size: 12px; color: #374151; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }

.account-main-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.account-identity { display: flex; flex-direction: column; gap: 4px; }
.account-username { font-size: 16px; font-weight: 800; color: #111827; }
.account-domain { font-size: 14px; color: #6b7280; }
.account-contact-info { display: flex; flex-direction: column; gap: 8px; }
.api-status { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid #e5e7eb; }
.status-label { font-size: 13px; color: #6b7280; font-weight: 600; }
.status-value { font-size: 14px; color: #111827; font-weight: 700; }

/* Scrollbars */
.accounts-list::-webkit-scrollbar, .activities-list::-webkit-scrollbar, .service-status::-webkit-scrollbar { width: 8px; height: 8px; }
.accounts-list::-webkit-scrollbar-thumb, .activities-list::-webkit-scrollbar-thumb, .service-status::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.5); border-radius: 999px; }
.accounts-list::-webkit-scrollbar-track, .activities-list::-webkit-scrollbar-track, .service-status::-webkit-scrollbar-track { background: transparent; }

@media (max-width: 1024px) {
  .dashboard-grid { grid-template-columns: 1fr; }
}
</style> 