<template>
  <nav class="navigation-menu">
    <button
      v-for="item in navigationItems"
      :key="item.view"
      @click="$emit('view-change', item.view)"
      :class="['nav-button', { 'active': currentView === item.view }]"
      :style="{ 
        background: currentView === item.view ? item.color + '25' : item.color + '15',
        color: item.color,
        borderColor: currentView === item.view ? item.color + '40' : item.color + '25'
      }"
    >
      <span class="nav-icon">{{ item.icon }}</span>
      <span class="nav-label">{{ item.label }}</span>
    </button>
  </nav>
</template>

<script>
export default {
  name: 'NavigationMenu',
  props: {
    currentView: {
      type: String,
      required: true
    }
  },
  emits: ['view-change'],
  data() {
    return {
      navigationItems: [
        { 
          view: 'tickets', 
          label: 'Tickets', 
          icon: '🎫', 
          color: '#3b82f6' 
        },
        { 
          view: 'clients', 
          label: 'Clients', 
          icon: '👥', 
          color: '#059669' 
        },
        { 
          view: 'hosting', 
          label: 'Hosting', 
          icon: '🏠', 
          color: '#8b5cf6' 
        },
        { 
          view: 'servers', 
          label: 'Servers', 
          icon: '🖥️', 
          color: '#8b5cf6' 
        },
        { 
          view: 'AI', 
          label: 'AI Assistant', 
          icon: '🤖', 
          color: '#d97706' 
        }
      ]
    }
  }
}
</script>

<style scoped>
.navigation-menu {
  display: flex;
  gap: 8px;
}

.nav-button {
  border: 1px solid;
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

.nav-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.nav-button.active {
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.nav-icon {
  font-size: 14px;
}

.nav-label {
  font-size: 12px;
}
</style> 