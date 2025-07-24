<template>
  <div class="servers-view">
    <!-- Fullscreen Navigation Bar -->
    <div class="fullscreen-nav">
      <div class="nav-left">
        <button class="nav-btn back-btn" @click="$emit('view-change', 'dashboard')">
          ← Dashboard
        </button>
      </div>
      <div class="nav-center">
        <h1 class="view-title">🖥️ Server Management</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="$emit('view-change', 'tickets')">
          🎫 Tickets
        </button>
        <button class="nav-btn" @click="$emit('view-change', 'hosting')">
          🏠 Hosting
        </button>
        <button class="nav-btn" @click="$emit('view-change', 'AI')">
          🤖 AI Assistant
        </button>
      </div>
    </div>

    <div class="servers-content">
      <!-- Server Quick Actions -->
      <div class="server-actions">
        <h3>Server Actions</h3>
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
                <span class="metric-value">{{ serverStats.cpuUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill cpu" :style="`width: ${serverStats.cpuUsage}%`"></div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Memory Usage</span>
                <span class="metric-value">{{ serverStats.memoryUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill memory" :style="`width: ${serverStats.memoryUsage}%`"></div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Disk Usage</span>
                <span class="metric-value">{{ serverStats.diskUsage }}%</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill disk" :style="`width: ${serverStats.diskUsage}%`"></div>
              </div>
            </div>
            
            <div class="metric-item">
              <div class="metric-header">
                <span class="metric-label">Network I/O</span>
                <span class="metric-value">{{ serverStats.networkIO }} MB/s</span>
              </div>
              <div class="metric-bar">
                <div class="metric-fill network" :style="`width: ${Math.min(serverStats.networkIO * 10, 100)}%`"></div>
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
          <div v-else-if="accounts.length === 0" class="empty-state">
            <span>👥</span>
            <p>No accounts found</p>
          </div>
          <div v-else class="accounts-list">
            <div v-for="account in accounts.slice(0, 5)" :key="account.user" class="account-item">
              <div class="account-info">
                <div class="account-name">{{ account.user }}</div>
                <div class="account-domain">{{ account.domain }}</div>
              </div>
              <div class="account-stats">
                <span class="disk-usage">{{ account.diskused }}MB</span>
                <span :class="['account-status', account.suspended ? 'suspended' : 'active']">
                  {{ account.suspended ? 'Suspended' : 'Active' }}
                </span>
              </div>
            </div>
            <BaseButton @click="showAllAccounts" variant="secondary" size="sm" class="view-all-btn">
              View All ({{ accounts.length }})
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
          <div v-if="loadingSystemInfo" class="loading-state">
            <span class="loading-spinner">⟳</span>
            <p>Loading system information...</p>
          </div>
          <div v-else class="system-info-grid">
            <div class="info-section">
              <h4>System Details</h4>
              <div class="info-item">
                <span class="info-label">Hostname:</span>
                <span class="info-value">{{ systemInfo.hostname || 'Loading...' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">OS Version:</span>
                <span class="info-value">{{ systemInfo.os || 'Loading...' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Kernel:</span>
                <span class="info-value">{{ systemInfo.kernel || 'Loading...' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Uptime:</span>
                <span class="info-value">{{ systemInfo.uptime || 'Loading...' }}</span>
              </div>
            </div>

            <div class="info-section">
              <h4>Hardware</h4>
              <div class="info-item">
                <span class="info-label">CPU Cores:</span>
                <span class="info-value">{{ systemInfo.cpuCores || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Total RAM:</span>
                <span class="info-value">{{ systemInfo.totalRam || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Total Disk:</span>
                <span class="info-value">{{ systemInfo.totalDisk || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Load Average:</span>
                <span class="info-value">{{ systemInfo.loadAverage || 'N/A' }}</span>
              </div>
            </div>

            <div class="info-section">
              <h4>Services</h4>
              <div class="service-status">
                <div v-for="service in systemInfo.services" :key="service.name" class="service-item">
                  <span class="service-name">{{ service.name }}</span>
                  <span :class="['service-status-badge', service.status]">{{ service.status }}</span>
                </div>
              </div>
            </div>
          </div>
        </DashboardWidget>
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
    accounts: {
      type: Array,
      default: () => []
    },
    systemInfo: {
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
    loadingSystemInfo: {
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
      ]
    }
  },
  methods: {
    executeAction(actionId) {
      this.$emit('execute-action', actionId)
    },
    showAllAccounts() {
      console.log('Show all accounts view')
    }
  },
  mounted() {
    this.$emit('load-accounts')
    this.$emit('load-system-info')
  }
}
</script>

<style scoped>
.servers-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
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

.nav-left, .nav-right {
  display: flex;
  gap: 12px;
}

.nav-center {
  flex: 1;
  text-align: center;
}

.view-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.nav-btn {
  padding: 8px 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.8);
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-btn:hover {
  background: white;
  color: #0f172a;
  border-color: #3b82f6;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.back-btn {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.back-btn:hover {
  background: #2563eb;
  border-color: #2563eb;
}

.servers-content {
  max-width: 1600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
  height: 100%;
  padding: 24px;
}

.server-actions {
  background: rgba(255, 255, 255, 0.6);
  padding: 24px;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.5);
}

.server-actions h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.action-btn {
  padding: 12px 16px;
  justify-content: center;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 32px;
}

.resource-metrics {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.metric-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.metric-value {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.metric-bar {
  height: 8px;
  background: rgba(226, 232, 240, 0.8);
  border-radius: 4px;
  overflow: hidden;
}

.metric-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.metric-fill.cpu {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
}

.metric-fill.memory {
  background: linear-gradient(135deg, #10b981, #059669);
}

.metric-fill.disk {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.metric-fill.network {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.loading-state, .empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}

.loading-spinner {
  animation: spin 1s linear infinite;
  font-size: 24px;
  display: block;
  margin-bottom: 8px;
}

.empty-state span {
  font-size: 32px;
  display: block;
  margin-bottom: 8px;
}

.accounts-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.account-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
}

.account-item:last-child {
  border-bottom: none;
}

.account-info {
  flex: 1;
}

.account-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
}

.account-domain {
  font-size: 12px;
  color: #64748b;
}

.account-stats {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.disk-usage {
  font-size: 12px;
  color: #64748b;
}

.account-status {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.account-status.active {
  background: #d1fae5;
  color: #059669;
}

.account-status.suspended {
  background: #fef3c7;
  color: #d97706;
}

.view-all-btn {
  margin-top: 8px;
  align-self: flex-start;
}

.activities-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.activity-icon {
  font-size: 16px;
  width: 24px;
  text-align: center;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-size: 13px;
  font-weight: 500;
  color: #0f172a;
  margin-bottom: 2px;
}

.activity-time {
  font-size: 11px;
  color: #64748b;
}

.activity-type {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 600;
  text-transform: uppercase;
}

.activity-type.backup {
  background: #dbeafe;
  color: #1e40af;
}

.activity-type.warning {
  background: #fef3c7;
  color: #d97706;
}

.activity-type.ssl {
  background: #d1fae5;
  color: #059669;
}

.activity-type.account {
  background: #ede9fe;
  color: #7c3aed;
}

.activity-type.config {
  background: #f3f4f6;
  color: #6b7280;
}

.server-details {
  grid-column: 1 / -1;
}

.system-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 32px;
}

.info-section h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  padding-bottom: 8px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
}

.service-status {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.service-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.service-name {
  font-size: 14px;
  color: #0f172a;
  font-weight: 500;
}

.service-status-badge {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.service-status-badge.running {
  background: #d1fae5;
  color: #059669;
}

.service-status-badge.stopped {
  background: #fee2e2;
  color: #dc2626;
}

.service-status-badge.disabled {
  background: #f3f4f6;
  color: #6b7280;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 