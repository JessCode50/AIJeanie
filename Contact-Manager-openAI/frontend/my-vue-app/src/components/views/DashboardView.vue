<template>
  <div class="dashboard-view">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <div class="hero-text">
          <h1 class="hero-title">
            <span class="gradient-text">AI-Powered</span>
            Contact Manager
          </h1>
          <p class="hero-subtitle">
            Streamline your customer support with intelligent automation, 
            powerful hosting management, and seamless integrations.
          </p>
          <div class="hero-stats">
            <div class="stat-chip">
              <span class="stat-icon">🚀</span>
              <span>99.9% Uptime</span>
            </div>
            <div class="stat-chip">
              <span class="stat-icon">⚡</span>
              <span>AI-Powered</span>
            </div>
            <div class="stat-chip">
              <span class="stat-icon">🔧</span>
              <span>Auto-Managed</span>
            </div>
          </div>
        </div>
        <div class="hero-visual">
          <div class="floating-card">
            <div class="card-header">
              <div class="card-dots">
                <span class="dot red"></span>
                <span class="dot yellow"></span>
                <span class="dot green"></span>
              </div>
              <span class="card-title">AI Assistant</span>
            </div>
            <div class="card-content">
              <div class="chat-bubble ai">
                <span class="avatar">🤖</span>
                <div class="message">
                  <p>How can I help you manage your servers today?</p>
                  <span class="timestamp">Just now</span>
                </div>
              </div>
              <div class="chat-bubble user">
                <div class="message">
                  <p>Check server status</p>
                  <span class="timestamp">Just now</span>
                </div>
                <span class="avatar">👤</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

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
        class="enhanced-widget"
      >
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading tickets...</p>
        </div>
        <div v-else-if="tickets.length === 0" class="empty-state">
          <div class="empty-icon">🎫</div>
          <p>No recent tickets</p>
          <button class="create-button" @click="$emit('view-change', 'tickets')">
            Create First Ticket
          </button>
        </div>
        <div v-else class="ticket-list">
          <div 
            v-for="ticket in tickets.slice(0, 5)" 
            :key="ticket.id"
            class="ticket-item"
          >
            <div class="ticket-priority" :class="ticket.priority || 'medium'"></div>
            <div class="ticket-info">
              <div class="ticket-title">#{{ ticket.id }} - {{ ticket.subject }}</div>
              <div class="ticket-meta">
                <span class="client-name">{{ ticket.client }}</span>
                <span class="separator">•</span>
                <span class="timestamp">{{ ticket.time }}</span>
              </div>
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
        class="enhanced-widget"
      >
        <div class="server-stats">
          <div class="stat-item">
            <div class="stat-header">
              <div class="stat-label">CPU Usage</div>
              <div class="stat-value">{{ serverStats.cpuUsage }}%</div>
            </div>
            <div class="stat-bar">
              <div 
                class="stat-fill cpu" 
                :style="`width: ${serverStats.cpuUsage}%`"
              ></div>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-header">
              <div class="stat-label">Memory</div>
              <div class="stat-value">{{ serverStats.memoryUsage }}%</div>
            </div>
            <div class="stat-bar">
              <div 
                class="stat-fill memory" 
                :style="`width: ${serverStats.memoryUsage}%`"
              ></div>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-header">
              <div class="stat-label">Disk Space</div>
              <div class="stat-value">{{ serverStats.diskUsage }}%</div>
            </div>
            <div class="stat-bar">
              <div 
                class="stat-fill disk" 
                :style="`width: ${serverStats.diskUsage}%`"
              ></div>
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
        class="enhanced-widget activities-widget"
      >
        <div v-if="activities.length === 0" class="empty-state">
          <div class="empty-icon">📋</div>
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
              <div class="activity-description">{{ activity.description }}</div>
              <div class="activity-time">{{ activity.time }}</div>
            </div>
            <span :class="['activity-priority', activity.priority]">{{ activity.priority }}</span>
          </div>
        </div>
      </DashboardWidget>

      <!-- AI Assistant Widget -->
      <DashboardWidget
        title="AI Assistant"
        subtitle="Get instant help"
        icon="🤖"
        icon-color="#f59e0b"
        :show-action="true"
        action-label="Open Chat"
        @action-click="$emit('view-change', 'ai-assistant')"
        class="enhanced-widget ai-widget"
      >
        <div class="ai-preview">
          <div class="ai-message">
            <div class="ai-avatar">🤖</div>
            <div class="ai-text">
              <p>Hi! I'm your AI assistant. I can help you:</p>
              <ul class="ai-capabilities">
                <li>• Manage server resources</li>
                <li>• Analyze tickets</li>
                <li>• Troubleshoot issues</li>
                <li>• Generate reports</li>
              </ul>
            </div>
          </div>
          <button class="chat-start-button" @click="$emit('view-change', 'ai-assistant')">
            Start Conversation
            <span class="button-icon">💬</span>
          </button>
        </div>
      </DashboardWidget>

      <!-- Performance Metrics Widget -->
      <DashboardWidget
        title="Performance Metrics"
        subtitle="Real-time insights"
        icon="📊"
        icon-color="#ef4444"
        class="enhanced-widget metrics-widget"
      >
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon">⚡</div>
            <div class="metric-content">
              <div class="metric-value">99.9%</div>
              <div class="metric-label">Uptime</div>
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">🚀</div>
            <div class="metric-content">
              <div class="metric-value">2.1s</div>
              <div class="metric-label">Response</div>
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
              <div class="metric-value">{{ tickets.length }}</div>
              <div class="metric-label">Active Tickets</div>
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">🔧</div>
            <div class="metric-content">
              <div class="metric-value">127</div>
              <div class="metric-label">Servers</div>
            </div>
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
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow-x: hidden;
  margin: 0;
  padding: 0;
  width: 100vw;
}

.dashboard-view::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
    radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
  pointer-events: none;
}

/* Hero Section */
.hero-section {
  padding: 3rem 1rem 4rem;
  position: relative;
  z-index: 1;
  width: 100%;
  margin: 0;
}

.hero-content {
  max-width: 100%;
  margin: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
  padding: 0 2rem;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 1.5rem;
  color: white;
}

.gradient-text {
  background: linear-gradient(135deg, #60a5fa, #34d399, #fbbf24);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  display: inline-block;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.hero-stats {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.stat-chip {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50px;
  padding: 0.75rem 1.5rem;
  color: white;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stat-icon {
  font-size: 1.2em;
}

/* Hero Visual */
.hero-visual {
  display: flex;
  justify-content: center;
  align-items: center;
  padding-right: 1rem;
}

.floating-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 
    0 10px 25px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.2);
  max-width: 400px;
  width: 100%;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.card-dots {
  display: flex;
  gap: 0.5rem;
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.dot.red { background: #ef4444; }
.dot.yellow { background: #f59e0b; }
.dot.green { background: #10b981; }

.card-title {
  font-weight: 600;
  color: #374151;
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chat-bubble {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.chat-bubble.ai {
  flex-direction: row;
}

.chat-bubble.user {
  flex-direction: row-reverse;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2em;
  flex-shrink: 0;
}

.message {
  background: #f3f4f6;
  padding: 0.75rem 1rem;
  border-radius: 15px;
  max-width: 250px;
}

.chat-bubble.user .message {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.message p {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  line-height: 1.4;
}

.timestamp {
  font-size: 0.75rem;
  opacity: 0.7;
}

/* Enhanced Widgets */
.enhanced-widget {
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
  height: 350px !important;
  display: flex !important;
  flex-direction: column !important;
}

.enhanced-widget:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 0.5fr 0.5fr;
  grid-template-rows: 1fr 1fr;
  gap: 2rem;
  padding: 0 2rem 3rem;
  max-width: 100%;
  margin: 0;
  position: relative;
  z-index: 1;
  width: 100%;
  height: calc(100vh - 200px);
}

/* Recent Tickets - Large square on the left taking full height */
.dashboard-grid .enhanced-widget:first-child {
  grid-row: 1 / 3;
  grid-column: 1;
  height: 100% !important;
}

/* Server Status - Top right, first column */
.dashboard-grid .enhanced-widget:nth-child(2) {
  grid-row: 1;
  grid-column: 2;
}

/* Recent Activities - Top right, second column */
.dashboard-grid .enhanced-widget:nth-child(3) {
  grid-row: 1;
  grid-column: 3;
}

/* AI Assistant - Bottom right, first column */
.dashboard-grid .enhanced-widget:nth-child(4) {
  grid-row: 2;
  grid-column: 2;
}

/* Performance Metrics - Bottom right, second column */
.dashboard-grid .enhanced-widget:nth-child(5) {
  grid-row: 2;
  grid-column: 3;
}

/* Ensure all widgets have consistent styling */
.enhanced-widget {
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
}

/* Quick Actions Bar Override */
:deep(.quick-actions-bar) {
  margin: 0 1rem 1.5rem;
}

/* Loading Spinner */
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.create-button {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1rem;
  transition: all 0.3s ease;
}

.create-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
}

/* Ticket List */
.ticket-list {
  overflow-y: auto;
  height: 100%;
  flex: 1;
  padding-right: 8px;
  display: flex;
  flex-direction: column;
}

.ticket-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: 10px;
  background: rgba(248, 250, 252, 0.8);
  margin-bottom: 0.75rem;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
  position: relative;
  min-height: 60px;
  flex-shrink: 0;
}

.ticket-item:hover {
  background: rgba(241, 245, 249, 0.9);
  transform: translateX(5px);
}

.ticket-priority {
  width: 4px;
  height: 100%;
  border-radius: 2px;
  position: absolute;
  left: 0;
}

.ticket-priority.high { background: #ef4444; }
.ticket-priority.medium { background: #f59e0b; }
.ticket-priority.low { background: #10b981; }

.ticket-info {
  flex: 1;
}

.ticket-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.ticket-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.client-name {
  font-weight: 500;
  color: #4f46e5;
}

.separator {
  opacity: 0.5;
}

.ticket-status {
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.ticket-status.open { background: #dbeafe; color: #1d4ed8; }
.ticket-status.pending { background: #fef3c7; color: #d97706; }
.ticket-status.closed { background: #d1fae5; color: #065f46; }

/* Server Stats */
.server-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  overflow-y: auto;
  max-height: 100%;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  font-weight: 500;
  color: #374151;
}

.stat-value {
  font-weight: 700;
  color: #1f2937;
}

.stat-bar {
  height: 8px;
  background: rgba(229, 231, 235, 0.8);
  border-radius: 4px;
  overflow: hidden;
  position: relative;
}

.stat-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 1s ease-in-out;
  position: relative;
}

.stat-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  animation: shimmer-bar 2s infinite;
}

@keyframes shimmer-bar {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.stat-fill.cpu { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
.stat-fill.memory { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
.stat-fill.disk { background: linear-gradient(90deg, #10b981, #059669); }

/* Activity List */
.activity-list {
  overflow-y: auto;
  max-height: 100%;
  padding-right: 8px;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 0.75rem;
  border-radius: 10px;
  background: rgba(248, 250, 252, 0.8);
  margin-bottom: 0.75rem;
  transition: all 0.3s ease;
  min-height: 60px;
}

.activity-item:hover {
  background: rgba(241, 245, 249, 0.9);
  transform: translateX(5px);
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2em;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.activity-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.activity-time {
  color: #9ca3af;
  font-size: 0.75rem;
}

.activity-priority {
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.activity-priority.high { background: #fee2e2; color: #dc2626; }
.activity-priority.medium { background: #fef3c7; color: #d97706; }
.activity-priority.low { background: #d1fae5; color: #065f46; }

/* AI Widget */
.ai-preview {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  height: 100%;
  overflow-y: auto;
}

.ai-message {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: rgba(239, 246, 255, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.ai-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 16px;
}

.ai-text {
  flex: 1;
}

.ai-text p {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.ai-capabilities {
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.4;
}

.ai-capabilities li {
  margin-bottom: 0.25rem;
}

.chat-start-button {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: auto;
  font-size: 14px;
}

.chat-start-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(245, 158, 11, 0.4);
}

.button-icon {
  font-size: 16px;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  overflow-y: auto;
  max-height: 100%;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: rgba(248, 250, 252, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.6);
  transition: all 0.3s ease;
}

.metric-card:hover {
  background: rgba(241, 245, 249, 0.9);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.metric-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.metric-content {
  flex: 1;
}

.metric-value {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .hero-content {
    padding: 0 1rem;
    gap: 2rem;
  }
  
  .dashboard-grid {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1rem;
    padding: 0 0.5rem 2rem;
  }
}

@media (max-width: 1024px) {
  .hero-content {
    grid-template-columns: 1fr;
    text-align: center;
    gap: 2rem;
    padding: 0 1rem;
  }
  
  .hero-title {
    font-size: 2.5rem;
  }
  
  .dashboard-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    padding: 0 0.5rem 2rem;
  }
  
  :deep(.quick-actions-bar) {
    margin: 0 0.5rem 1.5rem;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 2rem 0.5rem 3rem;
  }
  
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-stats {
    justify-content: center;
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
    padding: 0 0.5rem 2rem;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  :deep(.quick-actions-bar) {
    margin: 0 0.5rem 1rem;
  }
}

@media (max-width: 480px) {
  .hero-section {
    padding: 1.5rem 0.25rem 2rem;
  }
  
  .hero-content {
    padding: 0 0.5rem;
  }
  
  .hero-title {
    font-size: 1.75rem;
  }
  
  .hero-subtitle {
    font-size: 1rem;
  }
  
  .dashboard-grid {
    padding: 0 0.25rem 1rem;
    gap: 0.75rem;
  }
  
  :deep(.quick-actions-bar) {
    margin: 0 0.25rem 1rem;
  }
}
</style> 