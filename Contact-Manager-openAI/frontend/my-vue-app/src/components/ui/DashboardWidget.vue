<template>
  <div class="dashboard-widget">
    <div class="widget-header">
      <div class="widget-info">
        <div class="widget-icon" :style="`background: ${iconColor}15; border: 1px solid ${iconColor}25;`">
          <span :style="`color: ${iconColor}`">{{ icon }}</span>
        </div>
        <div class="widget-text">
          <h3>{{ title }}</h3>
          <p>{{ subtitle }}</p>
        </div>
      </div>
      <BaseButton 
        v-if="showAction"
        size="sm" 
        :variant="actionVariant"
        @click="$emit('action-click')"
      >
        {{ actionLabel }}
      </BaseButton>
    </div>
    
    <div class="widget-content">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import BaseButton from './BaseButton.vue'

export default {
  name: 'DashboardWidget',
  components: {
    BaseButton
  },
  props: {
    title: {
      type: String,
      required: true
    },
    subtitle: {
      type: String,
      default: ''
    },
    icon: {
      type: String,
      default: '📊'
    },
    iconColor: {
      type: String,
      default: '#3b82f6'
    },
    showAction: {
      type: Boolean,
      default: false
    },
    actionLabel: {
      type: String,
      default: 'View All'
    },
    actionVariant: {
      type: String,
      default: 'primary'
    }
  },
  emits: ['action-click']
}
</script>

<style scoped>
.dashboard-widget {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05);
  border: 1px solid rgba(226,232,240,0.5);
  overflow: hidden;
  height: 450px;
  display: flex;
  flex-direction: column;
}

.widget-header {
  padding: 32px 36px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.widget-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.widget-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  flex-shrink: 0;
}

.widget-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 2px;
}

.widget-subtitle {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.widget-action {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 8px;
  padding: 10px 18px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.widget-action:hover {
  background: rgba(59, 130, 246, 0.15);
  transform: translateY(-1px);
}

.widget-content {
  flex: 1;
  padding: 32px 36px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

/* Custom scrollbar for widget content */
.widget-content::-webkit-scrollbar {
  width: 6px;
}

.widget-content::-webkit-scrollbar-track {
  background: rgba(0,0,0,0.03);
  border-radius: 3px;
}

.widget-content::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, rgba(99, 102, 241, 0.3), rgba(99, 102, 241, 0.6));
  border-radius: 3px;
}

.widget-content::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, rgba(99, 102, 241, 0.5), rgba(99, 102, 241, 0.8));
}
</style> 