<template>
  <div class="notification-dropdown">
    <div class="notification-trigger" @click="$emit('toggle')">
      <div class="notification-icon">
        <span>🔔</span>
        <div v-if="notifications.length > 0" class="notification-badge">
          {{ notifications.length > 9 ? '9+' : notifications.length }}
        </div>
      </div>
    </div>
    
    <div v-if="show" class="notification-panel">
      <div class="notification-header">
        <h3>Notifications</h3>
        <button v-if="notifications.length > 0" @click="$emit('clear-all')" class="clear-button">
          Clear All
        </button>
      </div>
      
      <div class="notification-list">
        <div v-if="notifications.length === 0" class="no-notifications">
          <span>🔕</span>
          <p>No new notifications</p>
        </div>
        
        <div 
          v-for="notification in notifications" 
          :key="notification.id"
          :class="['notification-item', `type-${notification.type}`]"
          @click="$emit('notification-click', notification)"
        >
          <div class="notification-icon-small">
            {{ getNotificationIcon(notification.type) }}
          </div>
          <div class="notification-content">
            <div class="notification-message">{{ notification.message }}</div>
            <div class="notification-time">{{ notification.time }}</div>
          </div>
          <button @click.stop="$emit('dismiss', notification.id)" class="dismiss-button">×</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotificationDropdown',
  props: {
    notifications: { type: Array, default: () => [] },
    show: { type: Boolean, default: false }
  },
  emits: ['toggle', 'clear-all', 'notification-click', 'dismiss'],
  methods: {
    getNotificationIcon(type) {
      const icons = { success: '✅', warning: '⚠️', error: '❌', info: 'ℹ️', default: '🔔' }
      return icons[type] || icons.default
    }
  }
}
</script>

<style scoped>
.notification-dropdown { position: relative; }
.notification-trigger { cursor: pointer; }
.notification-icon {
  width: 40px; height: 40px; background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px;
  display: flex; align-items: center; justify-content: center; position: relative;
}
.notification-icon span { font-size: 16px; color: #dc2626; }
.notification-badge {
  position: absolute; top: -4px; right: -4px; width: 16px; height: 16px;
  background: #dc2626; border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-size: 10px; color: white; font-weight: 700;
}
.notification-panel {
  position: absolute; top: 50px; right: 0; width: 350px; background: white;
  border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  border: 1px solid rgba(226, 232, 240, 0.8); z-index: 1000;
}
.notification-header {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  display: flex; justify-content: space-between; align-items: center;
}
.notification-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
.clear-button {
  background: none; border: none; color: #3b82f6; font-size: 12px;
  font-weight: 600; cursor: pointer; padding: 4px 8px; border-radius: 4px;
}
.no-notifications { padding: 40px 20px; text-align: center; color: #64748b; }
.notification-item {
  padding: 12px 20px; display: flex; align-items: flex-start; gap: 12px;
  cursor: pointer; border-bottom: 1px solid rgba(226, 232, 240, 0.3);
}
.notification-content { flex: 1; }
.notification-message { font-size: 13px; color: #0f172a; font-weight: 500; margin-bottom: 4px; }
.notification-time { font-size: 11px; color: #64748b; }
.dismiss-button {
  background: none; border: none; color: #94a3b8; font-size: 16px;
  cursor: pointer; width: 20px; height: 20px; border-radius: 50%;
}
</style> 