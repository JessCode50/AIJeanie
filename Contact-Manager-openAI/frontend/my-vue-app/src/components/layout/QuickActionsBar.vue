<template>
  <div class="quick-actions-bar">
    <div class="actions-content">
      <div class="actions-header">
        <h3 class="actions-title">
          <span class="title-icon">⚡</span>
          Quick Actions
        </h3>
        <div class="actions-subtitle">Get things done faster</div>
      </div>
      <div class="actions-container">
        <button 
          v-for="action in quickActions" 
          :key="action.id"
          @click="$emit('action-click', action.id)"
          class="action-button"
          :class="`action-${action.id}`"
        >
          <div class="action-icon-wrapper">
            <span class="action-icon">{{ action.icon }}</span>
          </div>
          <div class="action-content">
            <span class="action-label">{{ action.label }}</span>
            <span class="action-description">{{ action.description || getActionDescription(action.id) }}</span>
          </div>
          <div class="action-arrow">→</div>
        </button>
      </div>
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
        { id: 'create-ticket', label: 'Tickets', icon: '🎫', color: '#3b82f6' },
        { id: 'add-client', label: 'Clients', icon: '👤', color: '#10b981' },
        { id: 'create-invoice', label: 'Invoices', icon: '💰', color: '#f59e0b' },
        { id: 'backup-account', label: 'Backup', icon: '💾', color: '#8b5cf6' },
        { id: 'check-ssl', label: 'SSL Check', icon: '🔒', color: '#ef4444' },
        { id: 'system-status', label: 'System', icon: '⚡', color: '#06b6d4' }
      ]
    }
  },
  emits: ['action-click'],
  methods: {
    getActionDescription(actionId) {
      const descriptions = {
        'create-ticket': 'Manage support tickets',
        'add-client': 'Manage client accounts',
        'create-invoice': 'Generate invoices',
        'backup-account': 'Backup system',
        'check-ssl': 'Verify SSL certificates',
        'system-status': 'Check system health'
      }
      return descriptions[actionId] || 'Quick action'
    }
  }
}
</script>

<style scoped>
.quick-actions-bar {
  margin: 0;
  position: relative;
  z-index: 2;
  width: 100%;
  padding: 0 1rem;
}

.actions-content {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
  margin: 0 0 1.5rem 0;
}

.actions-header {
  margin-bottom: 2rem;
  text-align: center;
}

.actions-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.title-icon {
  font-size: 1.2em;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.actions-subtitle {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.95rem;
  font-weight: 500;
}

.actions-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.action-button {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 16px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-align: left;
  position: relative;
  overflow: hidden;
}

.action-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  transition: left 0.5s ease;
  opacity: 0;
}

.action-button:hover::before {
  left: 100%;
  opacity: 1;
}

.action-button:hover {
  transform: translateY(-2px);
  box-shadow: 
    0 8px 16px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.95);
}

.action-button:active {
  transform: translateY(-2px);
}

.action-icon-wrapper {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}

.action-icon {
  font-size: 1.8rem;
  position: relative;
  z-index: 1;
}

.action-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.action-label {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.2;
}

.action-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.3;
}

.action-arrow {
  font-size: 1.2rem;
  color: #9ca3af;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.action-button:hover .action-arrow {
  color: #4f46e5;
  transform: translateX(5px);
}

/* Specific action button styles */
.action-create-ticket .action-icon-wrapper {
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
}

.action-create-ticket:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.action-create-ticket:hover .action-icon {
  color: white;
}

.action-add-client .action-icon-wrapper {
  background: linear-gradient(135deg, #d1fae5, #a7f3d0);
}

.action-add-client:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #10b981, #059669);
}

.action-add-client:hover .action-icon {
  color: white;
}

.action-create-invoice .action-icon-wrapper {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
}

.action-create-invoice:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.action-create-invoice:hover .action-icon {
  color: white;
}

.action-backup-account .action-icon-wrapper {
  background: linear-gradient(135deg, #ede9fe, #ddd6fe);
}

.action-backup-account:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.action-backup-account:hover .action-icon {
  color: white;
}

.action-check-ssl .action-icon-wrapper {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
}

.action-check-ssl:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.action-check-ssl:hover .action-icon {
  color: white;
}

.action-system-status .action-icon-wrapper {
  background: linear-gradient(135deg, #cffafe, #a5f3fc);
}

.action-system-status:hover .action-icon-wrapper {
  background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.action-system-status:hover .action-icon {
  color: white;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .quick-actions-bar {
    padding: 0 0.5rem;
  }
  
  .actions-container {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
}

@media (max-width: 1024px) {
  .actions-container {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
  
  .quick-actions-bar {
    padding: 0 0.5rem;
  }
  
  .actions-content {
    padding: 1.5rem;
    margin: 0 0 1.5rem 0;
  }
}

@media (max-width: 768px) {
  .quick-actions-bar {
    padding: 0 0.5rem;
  }
  
  .actions-container {
    grid-template-columns: 1fr;
  }
  
  .action-button {
    padding: 1rem;
  }
  
  .action-icon-wrapper {
    width: 50px;
    height: 50px;
  }
  
  .action-icon {
    font-size: 1.5rem;
  }
  
  .actions-title {
    font-size: 1.25rem;
  }
  
  .actions-content {
    padding: 1.25rem;
    margin: 0 0 1rem 0;
  }
}

@media (max-width: 480px) {
  .quick-actions-bar {
    padding: 0 0.25rem;
  }
  
  .actions-content {
    padding: 1rem;
    margin: 0 0 0.75rem 0;
    border-radius: 15px;
  }
  
  .actions-title {
    font-size: 1.1rem;
  }
  
  .actions-subtitle {
    font-size: 0.85rem;
  }
  
  .action-button {
    padding: 0.75rem;
  }
  
  .actions-header {
    margin-bottom: 1.5rem;
  }
}

/* Loading animation for action buttons */
.action-button.loading {
  pointer-events: none;
}

.action-button.loading .action-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 