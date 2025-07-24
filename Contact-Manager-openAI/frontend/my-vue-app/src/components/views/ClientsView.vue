<template>
  <div class="clients-view">
    <div class="clients-content">
      <!-- Client Stats Overview -->
      <div class="stats-grid">
        <DashboardWidget title="Total Clients" subtitle="Active accounts" icon="👥" icon-color="#10b981">
          <div class="stat-value">{{ clientStats.total }}</div>
        </DashboardWidget>
        <DashboardWidget title="Active Clients" subtitle="Currently active" icon="✅" icon-color="#059669">
          <div class="stat-value">{{ clientStats.active }}</div>
        </DashboardWidget>
        <DashboardWidget title="New This Month" subtitle="Recent signups" icon="📈" icon-color="#3b82f6">
          <div class="stat-value">{{ clientStats.newThisMonth }}</div>
        </DashboardWidget>
      </div>

      <!-- Client Search & Filters -->
      <div class="client-filters">
        <SearchBar 
          v-model="searchQuery"
          placeholder="Search clients by name, email, or company..."
          @search="performSearch"
        />
        <select v-model="filters.status" class="filter-select">
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="suspended">Suspended</option>
          <option value="inactive">Inactive</option>
        </select>
        <select v-model="filters.group" class="filter-select">
          <option value="all">All Groups</option>
          <option value="premium">Premium</option>
          <option value="standard">Standard</option>
          <option value="basic">Basic</option>
        </select>
        <BaseButton @click="applyFilters" variant="primary" size="sm">
          🔍 Search
        </BaseButton>
        <BaseButton @click="showCreateForm = true" variant="success" size="sm">
          👤 Add Client
        </BaseButton>
      </div>

      <!-- Clients Table -->
      <DashboardWidget title="Client Management" subtitle="WHMCS Client Administration">
        <div v-if="loading" class="loading-state">
          <span class="loading-spinner">⟳</span>
          <p>Loading clients...</p>
        </div>
        <div v-else-if="filteredClients.length === 0" class="empty-state">
          <span>👥</span>
          <p>No clients found</p>
          <BaseButton @click="showCreateForm = true" variant="primary">
            Add First Client
          </BaseButton>
        </div>
        <div v-else class="clients-table">
          <div class="table-header">
            <div class="header-cell">Client</div>
            <div class="header-cell">Email</div>
            <div class="header-cell">Status</div>
            <div class="header-cell">Group</div>
            <div class="header-cell">Products</div>
            <div class="header-cell">Actions</div>
          </div>
          <div class="table-body">
            <div v-for="client in filteredClients" :key="client.id" class="table-row">
              <div class="table-cell client-info">
                <div class="client-avatar">{{ getInitials(client.name) }}</div>
                <div class="client-details">
                  <div class="client-name">{{ client.name }}</div>
                  <div class="client-company">{{ client.company || 'No Company' }}</div>
                </div>
              </div>
              <div class="table-cell">{{ client.email }}</div>
              <div class="table-cell">
                <span :class="['status-badge', client.status]">{{ client.status }}</span>
              </div>
              <div class="table-cell">{{ client.group || 'Standard' }}</div>
              <div class="table-cell">{{ client.products || 0 }}</div>
              <div class="table-cell actions">
                <BaseButton @click="viewClient(client)" variant="info" size="sm">View</BaseButton>
                <BaseButton @click="editClient(client)" variant="warning" size="sm">Edit</BaseButton>
              </div>
            </div>
          </div>
        </div>
      </DashboardWidget>

      <!-- Create Client Modal/Form -->
      <div v-if="showCreateForm" class="modal-overlay" @click="showCreateForm = false">
        <div class="modal-content" @click.stop>
          <DashboardWidget title="Add New Client" subtitle="Create a new client account">
            <form @submit.prevent="createClient" class="client-form">
              <div class="form-row">
                <div class="form-group">
                  <label>Full Name *</label>
                  <input v-model="clientForm.name" type="text" class="form-input" required />
                </div>
                <div class="form-group">
                  <label>Email *</label>
                  <input v-model="clientForm.email" type="email" class="form-input" required />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Company</label>
                  <input v-model="clientForm.company" type="text" class="form-input" />
                </div>
                <div class="form-group">
                  <label>Phone</label>
                  <input v-model="clientForm.phone" type="tel" class="form-input" />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Client Group</label>
                  <select v-model="clientForm.group" class="form-select">
                    <option value="basic">Basic</option>
                    <option value="standard">Standard</option>
                    <option value="premium">Premium</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="clientForm.status" class="form-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div class="form-actions">
                <BaseButton @click="showCreateForm = false" variant="secondary">Cancel</BaseButton>
                <BaseButton type="submit" variant="primary" :loading="submitting">Create Client</BaseButton>
              </div>
            </form>
          </DashboardWidget>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'
import DashboardWidget from '../ui/DashboardWidget.vue'
import SearchBar from '../ui/SearchBar.vue'

export default {
  name: 'ClientsView',
  components: {
    BaseButton,
    DashboardWidget,
    SearchBar
  },
  props: {
    clients: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false }
  },
  emits: ['create-client', 'update-client', 'load-clients'],
  data() {
    return {
      searchQuery: '',
      showCreateForm: false,
      submitting: false,
      filters: {
        status: 'all',
        group: 'all'
      },
      clientForm: {
        name: '',
        email: '',
        company: '',
        phone: '',
        group: 'standard',
        status: 'active'
      }
    }
  },
  computed: {
    filteredClients() {
      return this.clients.filter(client => {
        const searchMatch = !this.searchQuery || 
          client.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          client.email.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          (client.company && client.company.toLowerCase().includes(this.searchQuery.toLowerCase()))
        
        const statusMatch = this.filters.status === 'all' || client.status === this.filters.status
        const groupMatch = this.filters.group === 'all' || client.group === this.filters.group
        
        return searchMatch && statusMatch && groupMatch
      })
    },
    clientStats() {
      return {
        total: this.clients.length,
        active: this.clients.filter(c => c.status === 'active').length,
        newThisMonth: this.clients.filter(c => {
          const created = new Date(c.created_at || Date.now())
          const now = new Date()
          return created.getMonth() === now.getMonth() && created.getFullYear() === now.getFullYear()
        }).length
      }
    }
  },
  methods: {
    performSearch() {
      console.log('Searching for:', this.searchQuery)
    },
    applyFilters() {
      console.log('Filters applied:', this.filters)
    },
    viewClient(client) {
      console.log('Viewing client:', client)
    },
    editClient(client) {
      console.log('Editing client:', client)
    },
    async createClient() {
      this.submitting = true
      try {
        await this.$emit('create-client', { ...this.clientForm })
        this.resetForm()
        this.showCreateForm = false
      } catch (error) {
        console.error('Error creating client:', error)
      } finally {
        this.submitting = false
      }
    },
    resetForm() {
      this.clientForm = {
        name: '',
        email: '',
        company: '',
        phone: '',
        group: 'standard',
        status: 'active'
      }
    },
    getInitials(name) {
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    }
  },
  mounted() {
    this.$emit('load-clients')
  }
}
</script>

<style scoped>
.clients-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  padding: 32px;
}

.clients-content {
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

.client-filters {
  display: flex;
  gap: 12px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  align-items: center;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 6px;
  background: white;
  font-size: 13px;
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

.clients-table {
  display: flex;
  flex-direction: column;
}

.table-header {
  display: grid;
  grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
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
  grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
  gap: 16px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
  transition: all 0.2s ease;
  align-items: center;
}

.table-row:hover {
  background: rgba(248, 250, 252, 0.5);
}

.table-cell {
  font-size: 14px;
  color: #0f172a;
}

.client-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.client-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #10b981, #059669);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.client-details {
  flex: 1;
}

.client-name {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
}

.client-company {
  font-size: 12px;
  color: #64748b;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.active {
  background: #d1fae5;
  color: #059669;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.suspended {
  background: #fef3c7;
  color: #d97706;
}

.actions {
  display: flex;
  gap: 8px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.client-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.form-input, .form-select {
  padding: 10px 12px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid rgba(226, 232, 240, 0.6);
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 