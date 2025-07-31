<template>
  <div class="quick-actions-bar">
    <h3 class="actions-title">Quick Actions</h3>
    <div class="actions-container">
      <button 
        v-for="action in quickActions" 
        :key="action.id"
        @click="$emit('action-click', action.id)"
        :style="`background: ${action.color}15; color: ${action.color}; border: 1px solid ${action.color}25;`"
        class="action-button"
      >
        <span class="action-icon">{{ action.icon }}</span>
        <span class="action-label">{{ action.label }}</span>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'QuickActionsBar',
  props: {
    quickActions: {
      type: Array,
      default: () => [
        { id: 'create-ticket', label: 'New Ticket', icon: '🎫', color: '#3b82f6' },
        { id: 'add-client', label: 'Add Client', icon: '👤', color: '#10b981' },
        { id: 'create-invoice', label: 'Invoice', icon: '💰', color: '#f59e0b' },
        { id: 'backup-account', label: 'Backup', icon: '💾', color: '#8b5cf6' },
        { id: 'check-ssl', label: 'SSL Check', icon: '🔒', color: '#ef4444' },
        { id: 'system-status', label: 'System', icon: '⚡', color: '#06b6d4' }
      ]
    }
  },
  emits: ['action-click']
}
</script>

<style scoped>
.quick-actions-bar {
  padding: 20px 32px;
  background: rgba(255, 255, 255, 0.6);
  border-bottom: 1px solid rgba(226, 232, 240, 0.5);
  display: flex;
  gap: 12px;
  align-items: center;
}

.actions-title {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.actions-container {
  flex: 1;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.action-button {
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-button:hover {
  transform: translateY(-1px);
  opacity: 0.9;
}

.action-icon {
  font-size: 14px;
}

.action-label {
  white-space: nowrap;
}
</style> 