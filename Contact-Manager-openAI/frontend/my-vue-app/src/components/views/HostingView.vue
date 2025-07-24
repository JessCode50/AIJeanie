<template>
  <div class="hosting-view">
    <div class="hosting-content">
      <!-- Hosting Summary Stats -->
      <div class="stats-grid">
        <DashboardWidget title="Total Accounts" subtitle="Hosting accounts" icon="🏠" icon-color="#8b5cf6">
          <div class="stat-value">{{ hostingSummary.totalAccounts }}</div>
        </DashboardWidget>
        <DashboardWidget title="Active Domains" subtitle="Domain count" icon="🌐" icon-color="#10b981">
          <div class="stat-value">{{ hostingSummary.totalDomains }}</div>
        </DashboardWidget>
        <DashboardWidget title="Disk Usage" subtitle="Total usage (GB)" icon="💾" icon-color="#f59e0b">
          <div class="stat-value">{{ hostingSummary.totalDiskUsageGB }}GB</div>
        </DashboardWidget>
        <DashboardWidget title="Active Services" subtitle="Running services" icon="⚡" icon-color="#3b82f6">
          <div class="stat-value">{{ hostingSummary.activeServices }}</div>
        </DashboardWidget>
      </div>

      <!-- Hosting Management Tabs -->
      <div class="hosting-tabs">
        <button 
          :class="['tab-btn', { active: activeTab === 'accounts' }]"
          @click="activeTab = 'accounts'"
        >
          🏠 Hosting Accounts
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'domains' }]"
          @click="activeTab = 'domains'"
        >
          🌐 Domains
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'diskusage' }]"
          @click="activeTab = 'diskusage'"
        >
          💾 Disk Usage
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'analytics' }]"
          @click="activeTab = 'analytics'"
        >
          📊 Analytics
        </button>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Hosting Accounts Tab -->
        <div v-if="activeTab === 'accounts'" class="accounts-tab">
          <DashboardWidget title="Hosting Accounts" subtitle="cPanel account management">
            <div v-if="loadingAccounts" class="loading-state">
              <span class="loading-spinner">⟳</span>
              <p>Loading hosting accounts...</p>
            </div>
            <div v-else-if="hostingAccounts.length === 0" class="empty-state">
              <span>🏠</span>
              <p>No hosting accounts found</p>
              <BaseButton @click="createAccount" variant="primary">
                Create First Account
              </BaseButton>
            </div>
            <div v-else class="accounts-grid">
              <div v-for="account in hostingAccounts" :key="account.user" class="account-card">
                <div class="account-header">
                  <div class="account-user">{{ account.user }}</div>
                  <span :class="['account-status', account.suspended ? 'suspended' : 'active']">
                    {{ account.suspended ? 'Suspended' : 'Active' }}
                  </span>
                </div>
                <div class="account-details">
                  <div class="detail-item">
                    <span class="detail-label">Domain:</span>
                    <span class="detail-value">{{ account.domain }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value">{{ account.plan || 'Default' }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Disk Used:</span>
                    <span class="detail-value">{{ account.diskused }}MB / {{ account.disklimit }}MB</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Bandwidth:</span>
                    <span class="detail-value">{{ account.bwused || 0 }}MB / {{ account.bwlimit || 'Unlimited' }}</span>
                  </div>
                </div>
                <div class="account-actions">
                  <BaseButton @click="manageAccount(account)" variant="info" size="sm">
                    Manage
                  </BaseButton>
                  <BaseButton @click="suspendAccount(account)" :variant="account.suspended ? 'success' : 'warning'" size="sm">
                    {{ account.suspended ? 'Unsuspend' : 'Suspend' }}
                  </BaseButton>
                </div>
              </div>
            </div>
          </DashboardWidget>
        </div>

        <!-- Domains Tab -->
        <div v-if="activeTab === 'domains'" class="domains-tab">
          <DashboardWidget title="Domain Management" subtitle="Domain overview and management">
            <div v-if="loadingDomains" class="loading-state">
              <span class="loading-spinner">⟳</span>
              <p>Loading domains...</p>
            </div>
            <div v-else-if="hostingDomains.length === 0" class="empty-state">
              <span>🌐</span>
              <p>No domains found</p>
            </div>
            <div v-else class="domains-table">
              <div class="table-header">
                <div class="header-cell">Domain</div>
                <div class="header-cell">Type</div>
                <div class="header-cell">Document Root</div>
                <div class="header-cell">SSL Status</div>
                <div class="header-cell">Actions</div>
              </div>
              <div class="table-body">
                <div v-for="domain in hostingDomains" :key="domain.domain" class="table-row">
                  <div class="table-cell domain-name">
                    <span class="domain-text">{{ domain.domain }}</span>
                    <span v-if="domain.is_main" class="main-badge">Main</span>
                  </div>
                  <div class="table-cell">{{ domain.type || 'subdomain' }}</div>
                  <div class="table-cell document-root">{{ domain.docroot || '/' }}</div>
                  <div class="table-cell">
                    <span :class="['ssl-status', domain.ssl_enabled ? 'enabled' : 'disabled']">
                      {{ domain.ssl_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                  </div>
                  <div class="table-cell actions">
                    <BaseButton @click="manageDomain(domain)" variant="info" size="sm">
                      Manage
                    </BaseButton>
                  </div>
                </div>
              </div>
            </div>
          </DashboardWidget>
        </div>

        <!-- Disk Usage Tab -->
        <div v-if="activeTab === 'diskusage'" class="diskusage-tab">
          <DashboardWidget title="Disk Usage Analysis" subtitle="Storage utilization by account">
            <div v-if="loadingDiskUsage" class="loading-state">
              <span class="loading-spinner">⟳</span>
              <p>Loading disk usage data...</p>
            </div>
            <div v-else class="disk-usage-grid">
              <div v-for="usage in hostingDiskUsage" :key="usage.user" class="usage-card">
                <div class="usage-header">
                  <div class="usage-user">{{ usage.user }}</div>
                  <div class="usage-percentage">{{ getUsagePercentage(usage) }}%</div>
                </div>
                <div class="usage-bar">
                  <div class="usage-fill" :style="`width: ${getUsagePercentage(usage)}%`"></div>
                </div>
                <div class="usage-details">
                  <span class="usage-text">
                    {{ formatBytes(usage.diskused) }} / {{ formatBytes(usage.disklimit) }}
                  </span>
                  <span class="usage-domain">{{ usage.domain }}</span>
                </div>
              </div>
            </div>
          </DashboardWidget>
        </div>

        <!-- Analytics Tab -->
        <div v-if="activeTab === 'analytics'" class="analytics-tab">
          <div class="analytics-grid">
            <DashboardWidget title="Bandwidth Usage" subtitle="Monthly bandwidth statistics" icon="📊" icon-color="#3b82f6">
              <div class="bandwidth-chart">
                <div class="chart-placeholder">
                  <span>📈</span>
                  <p>Bandwidth usage chart</p>
                  <p class="chart-note">Integration with analytics service required</p>
                </div>
              </div>
            </DashboardWidget>

            <DashboardWidget title="Resource Limits" subtitle="Account resource monitoring" icon="⚠️" icon-color="#f59e0b">
              <div class="resource-warnings">
                <div v-for="warning in resourceWarnings" :key="warning.account" class="warning-item">
                  <div class="warning-icon">⚠️</div>
                  <div class="warning-content">
                    <div class="warning-title">{{ warning.account }}</div>
                    <div class="warning-message">{{ warning.message }}</div>
                  </div>
                  <span :class="['warning-level', warning.level]">{{ warning.level }}</span>
                </div>
              </div>
            </DashboardWidget>

            <DashboardWidget title="Performance Metrics" subtitle="System performance overview" icon="⚡" icon-color="#10b981">
              <div class="performance-metrics">
                <div class="metric-item">
                  <span class="metric-label">Avg Response Time</span>
                  <span class="metric-value">245ms</span>
                </div>
                <div class="metric-item">
                  <span class="metric-label">Uptime</span>
                  <span class="metric-value">99.97%</span>
                </div>
                <div class="metric-item">
                  <span class="metric-label">Active Connections</span>
                  <span class="metric-value">1,247</span>
                </div>
                <div class="metric-item">
                  <span class="metric-label">Cache Hit Rate</span>
                  <span class="metric-value">89.3%</span>
                </div>
              </div>
            </DashboardWidget>
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
  name: 'HostingView',
  components: {
    BaseButton,
    DashboardWidget
  },
  props: {
    hostingAccounts: { type: Array, default: () => [] },
    hostingDomains: { type: Array, default: () => [] },
    hostingDiskUsage: { type: Array, default: () => [] },
    hostingSummary: {
      type: Object,
      default: () => ({
        totalAccounts: 0,
        totalDomains: 0,
        totalDiskUsageGB: 0,
        activeServices: 0
      })
    },
    loadingAccounts: { type: Boolean, default: false },
    loadingDomains: { type: Boolean, default: false },
    loadingDiskUsage: { type: Boolean, default: false }
  },
  emits: ['load-hosting-data', 'manage-account', 'suspend-account', 'manage-domain'],
  data() {
    return {
      activeTab: 'accounts',
      resourceWarnings: [
        { account: 'user1.domain.com', message: 'Approaching disk limit (95% used)', level: 'high' },
        { account: 'user2.domain.com', message: 'High bandwidth usage this month', level: 'medium' },
        { account: 'user3.domain.com', message: 'CPU usage spike detected', level: 'low' }
      ]
    }
  },
  methods: {
    createAccount() {
      console.log('Create new hosting account')
    },
    manageAccount(account) {
      this.$emit('manage-account', account)
    },
    suspendAccount(account) {
      this.$emit('suspend-account', account)
    },
    manageDomain(domain) {
      this.$emit('manage-domain', domain)
    },
    getUsagePercentage(usage) {
      if (!usage.disklimit || usage.disklimit === 0) return 0
      return Math.round((usage.diskused / usage.disklimit) * 100)
    },
    formatBytes(bytes) {
      if (bytes === 0) return '0 MB'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }
  },
  mounted() {
    this.$emit('load-hosting-data')
  }
}
</script>

<style scoped>
.hosting-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  padding: 32px;
}

.hosting-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #0f172a;
}

.hosting-tabs {
  display: flex;
  gap: 4px;
  background: rgba(255, 255, 255, 0.8);
  padding: 6px;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.tab-btn {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
  color: #64748b;
}

.tab-btn.active {
  background: white;
  color: #0f172a;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tab-content {
  min-height: 400px;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
}

.loading-spinner {
  animation: spin 1s linear infinite;
  font-size: 32px;
  display: block;
  margin-bottom: 16px;
}

.empty-state span {
  font-size: 48px;
  display: block;
  margin-bottom: 16px;
}

.accounts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.account-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border: 1px solid rgba(226, 232, 240, 0.6);
  transition: all 0.2s ease;
}

.account-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.account-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.account-user {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.account-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
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

.account-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.detail-value {
  font-size: 13px;
  color: #0f172a;
  font-weight: 500;
}

.account-actions {
  display: flex;
  gap: 8px;
}

.domains-table {
  display: flex;
  flex-direction: column;
}

.table-header {
  display: grid;
  grid-template-columns: 2fr 1fr 2fr 1fr 1fr;
  gap: 16px;
  padding: 16px 20px;
  background: rgba(248, 250, 252, 0.8);
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  font-weight: 600;
  color: #374151;
  font-size: 13px;
}

.table-body {
  display: flex;
  flex-direction: column;
}

.table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 2fr 1fr 1fr;
  gap: 16px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
  align-items: center;
}

.table-row:hover {
  background: rgba(248, 250, 252, 0.5);
}

.domain-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.domain-text {
  font-weight: 500;
  color: #0f172a;
}

.main-badge {
  background: #3b82f6;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}

.document-root {
  font-family: monospace;
  font-size: 12px;
  color: #6b7280;
}

.ssl-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.ssl-status.enabled {
  background: #d1fae5;
  color: #059669;
}

.ssl-status.disabled {
  background: #fee2e2;
  color: #dc2626;
}

.disk-usage-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.usage-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.usage-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.usage-user {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
}

.usage-percentage {
  font-weight: 700;
  color: #3b82f6;
  font-size: 14px;
}

.usage-bar {
  height: 8px;
  background: rgba(226, 232, 240, 0.8);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.usage-fill {
  height: 100%;
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  transition: width 0.3s ease;
}

.usage-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.usage-text {
  font-size: 12px;
  color: #64748b;
}

.usage-domain {
  font-size: 11px;
  color: #94a3b8;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 24px;
}

.chart-placeholder {
  text-align: center;
  padding: 40px;
  color: #64748b;
}

.chart-placeholder span {
  font-size: 48px;
  display: block;
  margin-bottom: 12px;
}

.chart-note {
  font-size: 12px;
  color: #94a3b8;
}

.resource-warnings {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.warning-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: rgba(248, 250, 252, 0.8);
  border-radius: 8px;
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.warning-icon {
  font-size: 20px;
}

.warning-content {
  flex: 1;
}

.warning-title {
  font-weight: 600;
  color: #0f172a;
  font-size: 13px;
}

.warning-message {
  font-size: 12px;
  color: #64748b;
}

.warning-level {
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.warning-level.high {
  background: #fee2e2;
  color: #dc2626;
}

.warning-level.medium {
  background: #fef3c7;
  color: #d97706;
}

.warning-level.low {
  background: #dbeafe;
  color: #2563eb;
}

.performance-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: rgba(248, 250, 252, 0.8);
  border-radius: 8px;
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.metric-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.metric-value {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 