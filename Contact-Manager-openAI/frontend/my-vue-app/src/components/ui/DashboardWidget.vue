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
}

.widget-header {
  padding: 24px 28px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.widget-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.widget-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.widget-icon span {
  font-size: 16px;
}

.widget-text h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.widget-text p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.widget-content {
  padding: 24px 28px;
}
</style> 