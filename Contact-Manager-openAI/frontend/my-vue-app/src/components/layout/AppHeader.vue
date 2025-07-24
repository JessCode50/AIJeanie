<template>
  <div class="app-header">
    <div class="header-left">
      <div class="brand">
        <div class="brand-icon">
          <span>{{ icon }}</span>
        </div>
        <div class="brand-text">
          <h1>{{ title }}</h1>
          <p>{{ subtitle }}</p>
        </div>
      </div>
      
      <NavigationMenu 
        :current-view="currentView" 
        @view-change="$emit('view-change', $event)"
      />
    </div>
    
    <div class="header-right">
      <SearchBar 
        v-if="showSearch"
        :model-value="searchQuery"
        :placeholder="searchPlaceholder"
        @update:model-value="$emit('search', $event)"
        @search="$emit('search', $event)"
      />
      
      <NotificationDropdown 
        :notifications="notifications"
        :show="showNotifications"
        @toggle="$emit('toggle-notifications')"
      />
      
      <BaseButton
        v-if="showRefresh"
        variant="success"
        size="sm"
        :loading="refreshLoading"
        @click="$emit('refresh')"
      >
        <span v-if="refreshLoading">⟳</span>
        <span v-else>🔄</span>
      </BaseButton>
      
      <div class="user-profile">
        <div class="user-avatar">
          <span>A</span>
        </div>
        <div class="user-info">
          <div class="user-name">Admin User</div>
          <div class="user-status">Administrator {{ dataStatus }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import NavigationMenu from './NavigationMenu.vue'
import SearchBar from '../ui/SearchBar.vue'
import NotificationDropdown from './NotificationDropdown.vue'
import BaseButton from '../ui/BaseButton.vue'

export default {
  name: 'AppHeader',
  components: {
    NavigationMenu,
    SearchBar,
    NotificationDropdown,
    BaseButton
  },
  props: {
    title: {
      type: String,
      default: 'Admin Dashboard'
    },
    subtitle: {
      type: String,
      default: 'Hosting & Client Management'
    },
    icon: {
      type: String,
      default: '🏠'
    },
    currentView: {
      type: String,
      required: true
    },
    searchQuery: {
      type: String,
      default: ''
    },
    searchPlaceholder: {
      type: String,
      default: 'Search tickets, clients, domains...'
    },
    showSearch: {
      type: Boolean,
      default: true
    },
    showRefresh: {
      type: Boolean,
      default: true
    },
    refreshLoading: {
      type: Boolean,
      default: false
    },
    notifications: {
      type: Array,
      default: () => []
    },
    showNotifications: {
      type: Boolean,
      default: false
    },
    dataStatus: {
      type: String,
      default: '• Sample Data'
    }
  },
  emits: ['view-change', 'search', 'toggle-notifications', 'refresh']
}
</script>

<style scoped>
.app-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  padding: 16px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 16px;
}

.brand-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.brand-icon span {
  font-size: 18px;
  color: white;
}

.brand-text h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.025em;
}

.brand-text p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(71, 85, 105, 0.1);
  padding: 8px 16px;
  border-radius: 12px;
  cursor: pointer;
}

.user-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #6b7280, #4b5563);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-avatar span {
  color: white;
  font-size: 14px;
  font-weight: 700;
}

.user-info {
  text-align: left;
}

.user-name {
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
}

.user-status {
  font-size: 10px;
  color: #64748b;
}
</style> 