<template>
  <div class="dashboard-view">
    <!-- Quick Actions -->
    <QuickActionsBar 
      :quick-actions="quickActions"
      @action-click="handleQuickAction"
    />

    <!-- Dashboard Content Grid -->
    <div class="dashboard-grid">
      <!-- Recent Tickets Widget -->
      <DashboardWidget
        title="Recent Tickets"
        subtitle="Latest support requests"
        icon="🎫"
        icon-color="#3b82f6"
        :show-action="true"
        action-label="View All"
        @action-click="$emit('view-change', 'tickets')"
      >
        <div v-if="loading" class="loading-state">
          <span class="loading-spinner">⟳</span>
          <p>Loading tickets...</p>
        </div>
        <div v-else-if="tickets.length === 0" class="empty-state">
          <span>🎫</span>
          <p>No recent tickets</p>
        </div>
        <div v-else class="ticket-list">
          <div 
            v-for="ticket in tickets.slice(0, 5)" 
            :key="ticket.id"
            class="ticket-item"
          >
            <div class="ticket-info">
              <div class="ticket-title">#{{ ticket.id }} - {{ ticket.subject }}</div>
              <div class="ticket-meta">{{ ticket.client }} • {{ ticket.time }}</div>
            </div>
            <span :class="['ticket-status', ticket.status]">{{ ticket.status }}</span>
          </div>
        </div>
      </DashboardWidget>

      <!-- Server Status Widget -->
      <DashboardWidget
        title="Server Status"
        subtitle="System performance"
        icon="🖥️"
        icon-color="#8b5cf6"
        :show-action="true"
        action-label="View Servers"
        @action-click="$emit('view-change', 'servers')"
      >
        <div class="server-stats">
          <div class="stat-item">
            <div class="stat-label">CPU Usage</div>
            <div class="stat-value">{{ serverStats.cpuUsage }}%</div>
            <div class="stat-bar">
              <div class="stat-fill" :style="`width: ${serverStats.cpuUsage}%`"></div>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-label">Memory</div>
            <div class="stat-value">{{ serverStats.memoryUsage }}%</div>
            <div class="stat-bar">
              <div class="stat-fill" :style="`width: ${serverStats.memoryUsage}%`"></div>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-label">Disk Space</div>
            <div class="stat-value">{{ serverStats.diskUsage }}%</div>
            <div class="stat-bar">
              <div class="stat-fill" :style="`width: ${serverStats.diskUsage}%`"></div>
            </div>
          </div>
        </div>
      </DashboardWidget>

      <!-- Recent Activities Widget -->
      <DashboardWidget
        title="Recent Activities"
        subtitle="System activities"
        icon="📋"
        icon-color="#10b981"
      >
        <div v-if="activities.length === 0" class="empty-state">
          <span>📋</span>
          <p>No recent activities</p>
        </div>
        <div v-else class="activity-list">
          <div 
            v-for="activity in activities.slice(0, 5)" 
            :key="activity.id"
            class="activity-item"
          >
            <div class="activity-icon">{{ activity.icon || '📋' }}</div>
            <div class="activity-content">
              <div class="activity-title">{{ activity.title }}</div>
              <div class="activity-description">{{ activity.description }} • {{ activity.time }}</div>
            </div>
            <span :class="['activity-priority', activity.priority]">{{ activity.priority }}</span>
          </div>
        </div>
      </DashboardWidget>
    </div>
  </div>
</template>

<script>
import QuickActionsBar from '../layout/QuickActionsBar.vue'
import DashboardWidget from '../ui/DashboardWidget.vue'

export default {
  name: 'DashboardView',
  components: {
    QuickActionsBar,
    DashboardWidget
  },
  props: {
    quickActions: {
      type: Array,
      default: () => []
    },
    tickets: {
      type: Array,
      default: () => []
    },
    serverStats: {
      type: Object,
      default: () => ({
        cpuUsage: 23,
        memoryUsage: 67,
        diskUsage: 45
      })
    },
    activities: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['view-change', 'quick-action'],
  methods: {
    handleQuickAction(actionId) {
      this.$emit('quick-action', actionId)
    }
  }
}
</script>

<style scoped>
.dashboard-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  min-height: 100vh;
  width: 100vw;
  margin: 0;
  padding: 0;
  position: fixed;
  top: 0;
  left: 0;
  overflow-y: auto;
}

.dashboard-grid {
  padding: 32px;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 32px;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 20px;
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

.ticket-list, .activity-list {
  display: grid;
  gap: 16px;
}

.ticket-item, .activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
}

.ticket-info, .activity-content {
  flex: 1;
}

.ticket-title, .activity-title {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
  margin-bottom: 2px;
}

.ticket-meta, .activity-description {
  font-size: 12px;
  color: #64748b;
}

.ticket-status, .activity-priority {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
}

.ticket-status.open, .activity-priority.high {
  background: #fef3c7;
  color: #d97706;
}

.ticket-status.closed, .activity-priority.low {
  background: #d1fae5;
  color: #059669;
}

.server-stats {
  display: grid;
  gap: 16px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.stat-bar {
  height: 6px;
  background: rgba(226, 232, 240, 0.8);
  border-radius: 3px;
  overflow: hidden;
}

.stat-fill {
  height: 100%;
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  transition: width 0.3s ease;
}

.activity-icon {
  font-size: 16px;
  margin-top: 2px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 