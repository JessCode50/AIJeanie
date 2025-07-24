<template>
  <div class="tickets-view">
    <!-- Fullscreen Navigation Bar -->
    <div class="fullscreen-nav">
      <div class="nav-left">
        <button class="nav-btn back-btn" @click="$emit('view-change', 'dashboard')">
          ← Dashboard
        </button>
      </div>
      <div class="nav-center">
        <h1 class="view-title">🎫 Support Tickets</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="$emit('view-change', 'servers')">
          🖥️ Servers
        </button>
        <button class="nav-btn" @click="$emit('view-change', 'hosting')">
          🏠 Hosting
        </button>
        <button class="nav-btn" @click="$emit('view-change', 'AI')">
          🤖 AI Assistant
        </button>
      </div>
    </div>

    <!-- Ticket Management Content -->
    <div class="tickets-content">
      <!-- Ticket View Switcher -->
      <div class="view-switcher">
        <button 
          :class="['view-btn', { active: currentView === 'list' }]"
          @click="currentView = 'list'"
        >
          📋 Ticket List
        </button>
        <button 
          :class="['view-btn', { active: currentView === 'create' }]"
          @click="currentView = 'create'"
        >
          ➕ Create Ticket
        </button>
        <button 
          v-if="selectedTicket"
          :class="['view-btn', { active: currentView === 'view' }]"
          @click="currentView = 'view'"
        >
          👁️ View Ticket
        </button>
      </div>

      <!-- Ticket List View -->
      <div v-if="currentView === 'list'" class="ticket-list-view">
        <!-- Filters -->
        <div class="ticket-filters">
          <select v-model="filters.status" class="filter-select">
            <option value="all">All Status</option>
            <option value="open">Open</option>
            <option value="in-progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
          <select v-model="filters.priority" class="filter-select">
            <option value="all">All Priority</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <select v-model="filters.category" class="filter-select">
            <option value="all">All Categories</option>
            <option value="technical">Technical</option>
            <option value="billing">Billing</option>
            <option value="general">General</option>
          </select>
          <BaseButton @click="applyFilters" variant="primary" size="sm">
            Apply Filters
          </BaseButton>
        </div>

        <!-- Ticket Grid -->
        <div class="ticket-grid">
          <div v-if="loading" class="loading-state">
            <span class="loading-spinner">⟳</span>
            <p>Loading tickets...</p>
          </div>
          <div v-else-if="filteredTickets.length === 0" class="empty-state">
            <span>🎫</span>
            <p>No tickets found</p>
            <BaseButton @click="currentView = 'create'" variant="primary">
              Create First Ticket
            </BaseButton>
          </div>
          <div v-else class="tickets-list">
            <div 
              v-for="ticket in filteredTickets" 
              :key="ticket.id"
              class="ticket-card"
              @click="selectTicket(ticket)"
            >
              <div class="ticket-header">
                <div class="ticket-id">#{{ ticket.id }}</div>
                <span :class="['ticket-priority', ticket.priority]">{{ ticket.priority }}</span>
              </div>
              <div class="ticket-title">{{ ticket.subject }}</div>
              <div class="ticket-meta">
                <span class="ticket-client">{{ ticket.client || 'Unknown Client' }}</span>
                <span class="ticket-time">{{ formatTime(ticket.created_at) }}</span>
              </div>
              <div class="ticket-footer">
                <span :class="['ticket-status', ticket.status]">{{ ticket.status }}</span>
                <span class="ticket-category">{{ ticket.category }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Ticket View -->
      <div v-else-if="currentView === 'create'" class="create-ticket-view">
        <DashboardWidget
          title="Create New Ticket"
          subtitle="Submit a new support request"
          icon="🎫"
          icon-color="#10b981"
        >
          <form @submit.prevent="createTicket" class="ticket-form">
            <div class="form-row">
              <div class="form-group">
                <label>Subject *</label>
                <input 
                  v-model="ticketForm.subject" 
                  type="text" 
                  class="form-input"
                  placeholder="Brief description of the issue"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Category</label>
                <select v-model="ticketForm.category" class="form-select">
                  <option value="general">General Support</option>
                  <option value="technical">Technical Issue</option>
                  <option value="billing">Billing Question</option>
                  <option value="feature">Feature Request</option>
                </select>
              </div>
              <div class="form-group">
                <label>Priority</label>
                <select v-model="ticketForm.priority" class="form-select">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Client ID</label>
                <input 
                  v-model="ticketForm.clientId" 
                  type="text" 
                  class="form-input"
                  placeholder="Optional client identifier"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Message *</label>
                <textarea 
                  v-model="ticketForm.message" 
                  class="form-textarea"
                  placeholder="Detailed description of the issue or request..."
                  rows="6"
                  required
                ></textarea>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tags</label>
                <div class="tags-input">
                  <div class="tags-list">
                    <span 
                      v-for="tag in ticketForm.tags" 
                      :key="tag"
                      class="tag"
                    >
                      {{ tag }}
                      <button @click="removeTag(tag)" type="button" class="tag-remove">×</button>
                    </span>
                  </div>
                  <input 
                    v-model="tagInput"
                    @keydown="handleTagInput"
                    type="text" 
                    class="tag-input"
                    placeholder="Add tags..."
                  />
                </div>
              </div>
            </div>

            <div class="form-actions">
              <BaseButton @click="currentView = 'list'" variant="secondary">
                Cancel
              </BaseButton>
              <BaseButton type="submit" variant="primary" :loading="submitting">
                Create Ticket
              </BaseButton>
            </div>
          </form>
        </DashboardWidget>
      </div>

      <!-- View Ticket Details -->
      <div v-else-if="currentView === 'view' && selectedTicket" class="view-ticket">
        <DashboardWidget
          :title="`Ticket #${selectedTicket.id}`"
          :subtitle="selectedTicket.subject"
          icon="👁️"
          icon-color="#3b82f6"
        >
          <div class="ticket-details">
            <div class="detail-row">
              <span class="detail-label">Status:</span>
              <span :class="['detail-value', 'status', selectedTicket.status]">
                {{ selectedTicket.status }}
              </span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Priority:</span>
              <span :class="['detail-value', 'priority', selectedTicket.priority]">
                {{ selectedTicket.priority }}
              </span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Category:</span>
              <span class="detail-value">{{ selectedTicket.category }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Client:</span>
              <span class="detail-value">{{ selectedTicket.client || 'N/A' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Created:</span>
              <span class="detail-value">{{ formatTime(selectedTicket.created_at) }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Message:</span>
              <div class="ticket-message">{{ selectedTicket.message }}</div>
            </div>
          </div>

          <div class="ticket-actions">
            <BaseButton @click="currentView = 'list'" variant="secondary">
              Back to List
            </BaseButton>
            <BaseButton @click="editTicket" variant="warning">
              Edit Ticket
            </BaseButton>
            <BaseButton @click="deleteTicket" variant="danger">
              Delete Ticket
            </BaseButton>
          </div>
        </DashboardWidget>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'
import DashboardWidget from '../ui/DashboardWidget.vue'

export default {
  name: 'TicketsView',
  components: {
    BaseButton,
    DashboardWidget
  },
  props: {
    tickets: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['create-ticket', 'update-ticket', 'delete-ticket', 'load-tickets', 'view-change'],
  data() {
    return {
      currentView: 'list',
      selectedTicket: null,
      submitting: false,
      filters: {
        status: 'all',
        priority: 'all',
        category: 'all'
      },
      ticketForm: {
        subject: '',
        message: '',
        category: 'general',
        priority: 'medium',
        clientId: '',
        tags: []
      },
      tagInput: ''
    }
  },
  computed: {
    filteredTickets() {
      return this.tickets.filter(ticket => {
        const statusMatch = this.filters.status === 'all' || ticket.status === this.filters.status
        const priorityMatch = this.filters.priority === 'all' || ticket.priority === this.filters.priority
        const categoryMatch = this.filters.category === 'all' || ticket.category === this.filters.category
        
        return statusMatch && priorityMatch && categoryMatch
      })
    }
  },
  methods: {
    selectTicket(ticket) {
      this.selectedTicket = ticket
      this.currentView = 'view'
    },
    async createTicket() {
      this.submitting = true
      try {
        await this.$emit('create-ticket', { ...this.ticketForm })
        this.resetForm()
        this.currentView = 'list'
      } catch (error) {
        console.error('Error creating ticket:', error)
      } finally {
        this.submitting = false
      }
    },
    editTicket() {
      // Switch to edit mode (could extend the form)
      this.currentView = 'create'
      this.ticketForm = { ...this.selectedTicket }
    },
    async deleteTicket() {
      if (confirm('Are you sure you want to delete this ticket?')) {
        try {
          await this.$emit('delete-ticket', this.selectedTicket.id)
          this.selectedTicket = null
          this.currentView = 'list'
        } catch (error) {
          console.error('Error deleting ticket:', error)
        }
      }
    },
    applyFilters() {
      // Filters are reactive, so this just triggers a re-render
      console.log('Filters applied:', this.filters)
    },
    handleTagInput(event) {
      if (event.key === 'Enter' && this.tagInput.trim()) {
        event.preventDefault()
        if (!this.ticketForm.tags.includes(this.tagInput.trim())) {
          this.ticketForm.tags.push(this.tagInput.trim())
        }
        this.tagInput = ''
      }
    },
    removeTag(tag) {
      const index = this.ticketForm.tags.indexOf(tag)
      if (index > -1) {
        this.ticketForm.tags.splice(index, 1)
      }
    },
    resetForm() {
      this.ticketForm = {
        subject: '',
        message: '',
        category: 'general',
        priority: 'medium',
        clientId: '',
        tags: []
      }
      this.tagInput = ''
    },
    formatTime(timestamp) {
      if (!timestamp) return 'N/A'
      return new Date(timestamp).toLocaleDateString() + ' ' + new Date(timestamp).toLocaleTimeString()
    }
  },
  mounted() {
    this.$emit('load-tickets')
  }
}
</script>

<style scoped>
.tickets-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', sans-serif;
  min-height: 100vh;
  height: 100%;
  padding: 0;
  overflow: auto;
}

.fullscreen-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  padding: 16px 24px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left, .nav-right {
  display: flex;
  gap: 12px;
}

.nav-center {
  flex: 1;
  text-align: center;
}

.view-title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.nav-btn {
  padding: 8px 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.8);
  color: #64748b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-btn:hover {
  background: white;
  color: #0f172a;
  border-color: #3b82f6;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.back-btn {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.back-btn:hover {
  background: #2563eb;
  border-color: #2563eb;
}

.tickets-content {
  max-width: 1400px;
  margin: 0 auto;
  height: 100%;
  padding: 24px;
}

.view-switcher {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  background: rgba(255, 255, 255, 0.8);
  padding: 8px;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.view-btn {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  background: transparent;
  color: #64748b;
}

.view-btn.active {
  background: white;
  color: #0f172a;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ticket-filters {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 6px;
  background: white;
  font-size: 13px;
}

.ticket-grid {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 24px;
  border: 1px solid rgba(226, 232, 240, 0.8);
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

.tickets-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.ticket-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.ticket-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.ticket-id {
  font-weight: 700;
  color: #3b82f6;
  font-size: 14px;
}

.ticket-priority {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.ticket-priority.low {
  background: #d1fae5;
  color: #059669;
}

.ticket-priority.medium {
  background: #fef3c7;
  color: #d97706;
}

.ticket-priority.high {
  background: #fed7d7;
  color: #dc2626;
}

.ticket-priority.urgent {
  background: #fee2e2;
  color: #b91c1c;
}

.ticket-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 8px;
  line-height: 1.4;
}

.ticket-meta {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 12px;
}

.ticket-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ticket-status {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.ticket-status.open {
  background: #dbeafe;
  color: #1e40af;
}

.ticket-status.closed {
  background: #d1fae5;
  color: #059669;
}

.ticket-category {
  font-size: 11px;
  color: #6b7280;
  background: rgba(107, 114, 128, 0.1);
  padding: 2px 6px;
  border-radius: 4px;
}

.ticket-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-row:last-child {
  grid-template-columns: 1fr;
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

.form-input, .form-select, .form-textarea {
  padding: 10px 12px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.tags-input {
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 8px;
  padding: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.tags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.tag {
  background: #3b82f6;
  color: white;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.tag-remove {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
}

.tag-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 4px 0;
  font-size: 14px;
  min-width: 100px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid rgba(226, 232, 240, 0.6);
}

.ticket-details {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.detail-label {
  font-weight: 600;
  color: #64748b;
  min-width: 80px;
  font-size: 14px;
}

.detail-value {
  color: #0f172a;
  font-size: 14px;
}

.detail-value.status.open {
  color: #1e40af;
}

.detail-value.status.closed {
  color: #059669;
}

.detail-value.priority.high {
  color: #dc2626;
}

.detail-value.priority.urgent {
  color: #b91c1c;
  font-weight: 600;
}

.ticket-message {
  background: rgba(248, 250, 252, 0.8);
  padding: 12px;
  border-radius: 8px;
  border: 1px solid rgba(226, 232, 240, 0.6);
  white-space: pre-wrap;
  line-height: 1.5;
}

.ticket-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid rgba(226, 232, 240, 0.6);
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 