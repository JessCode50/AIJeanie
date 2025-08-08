<template>
  <div class="tickets-view">
    <!-- Fullscreen Navigation Bar -->
    <div class="fullscreen-nav">
      <div class="nav-left">
        <button class="nav-btn back-btn" @click="$router.push('/dashboard')">
          ← Dashboard
        </button>
      </div>
      <div class="nav-center">
        <h1 class="view-title">🎫 Support Tickets</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="$router.push('/servers')">
          🖥️ Servers
        </button>
        <button class="nav-btn" @click="$router.push('/hosting')">
          🏠 Hosting
        </button>
        <button class="nav-btn" @click="$router.push('/ai')">
          🤖 AI Assistant
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="tickets-content">
      <!-- Stats Overview -->
      <div class="stats-overview">
        <div class="stat-card gradient-blue">
          <div class="stat-header">
            <span class="stat-icon">🟦</span>
            <span class="stat-label">Open</span>
          </div>
          <div class="stat-value">{{ ticketStats.open }}</div>
          <div class="stat-foot">Active conversations</div>
        </div>
        <div class="stat-card gradient-purple">
          <div class="stat-header">
            <span class="stat-icon">🟪</span>
            <span class="stat-label">In Progress</span>
          </div>
          <div class="stat-value">{{ ticketStats.inProgress }}</div>
          <div class="stat-foot">Being worked on</div>
        </div>
        <div class="stat-card gradient-green">
          <div class="stat-header">
            <span class="stat-icon">🟩</span>
            <span class="stat-label">Closed</span>
          </div>
          <div class="stat-value">{{ ticketStats.closed }}</div>
          <div class="stat-foot">Resolved</div>
        </div>
        <div class="stat-card gradient-rose">
          <div class="stat-header">
            <span class="stat-icon">📈</span>
            <span class="stat-label">Total</span>
          </div>
          <div class="stat-value">{{ tickets.length }}</div>
          <div class="stat-foot">All records</div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="tickets-toolbar">
        <SearchBar
          v-model="searchQuery"
          placeholder="Search by subject, client or message..."
          @search="applyFilters"
        />
        <div class="toolbar-right">
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
            <option value="feature">Feature</option>
          </select>
          <select v-model="sortBy" class="filter-select">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
            <option value="priority">Priority</option>
          </select>
          <BaseButton @click="$emit('load-tickets')" variant="secondary" size="sm">⟳ Refresh</BaseButton>
          <BaseButton @click="currentView = 'create'" variant="primary" size="sm">➕ New Ticket</BaseButton>
        </div>
      </div>

      <!-- View Switcher -->
      <div class="view-switcher">
        <button 
          :class="['view-btn', { active: currentView === 'list' }]"
          @click="currentView = 'list'"
        >
          📋 List
        </button>
        <button 
          :class="['view-btn', { active: currentView === 'board' }]"
          @click="currentView = 'board'"
        >
          🗂️ Kanban
        </button>
        <button 
          :class="['view-btn', { active: currentView === 'create' }]"
          @click="currentView = 'create'"
        >
          ➕ Create
        </button>
      </div>

      <!-- List View -->
      <div v-if="currentView === 'list'" class="ticket-grid">
        <div v-if="loading" class="loading-state">
          <span class="loading-spinner">⟳</span>
          <p>Loading tickets...</p>
        </div>
        <div v-else-if="sortedTickets.length === 0" class="empty-state">
          <span>🎫</span>
          <p>No tickets found</p>
          <BaseButton @click="currentView = 'create'" variant="primary">
            Create First Ticket
          </BaseButton>
        </div>
        <div v-else class="tickets-list">
          <div
            v-for="ticket in sortedTickets"
            :key="ticket.id"
            class="ticket-card fancy-card"
            @click="selectTicket(ticket)"
          >
            <div class="ticket-header">
              <div class="left">
                <div class="ticket-id">#{{ ticket.id }}</div>
                <div class="ticket-title">{{ ticket.subject }}</div>
              </div>
              <div class="right">
                <span :class="['ticket-priority', ticket.priority]">{{ ticket.priority }}</span>
              </div>
            </div>
            <div class="ticket-meta">
              <span class="ticket-client">{{ ticket.client || 'Unknown Client' }}</span>
              <span class="dot">•</span>
              <span class="ticket-category">{{ ticket.category }}</span>
              <span class="spacer"></span>
              <span class="ticket-time">{{ formatTime(ticket.created_at) }}</span>
            </div>
            <div class="ticket-footer">
              <div class="tags" v-if="ticket.tags && ticket.tags.length">
                <span v-for="tag in ticket.tags" :key="tag" class="tag">{{ tag }}</span>
              </div>
              <span :class="['ticket-status', ticket.status]">{{ ticket.status }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Kanban Board View -->
      <div v-else-if="currentView === 'board'" class="kanban-board">
        <div class="kanban-column">
          <div class="kanban-header">Open ({{ groupedTickets.open.length }})</div>
          <div class="kanban-list">
            <div v-for="t in groupedTickets.open" :key="t.id" class="kanban-card" @click="selectTicket(t)">
              <div class="kc-title">#{{ t.id }} — {{ t.subject }}</div>
              <div class="kc-meta">
                <span :class="['chip', t.priority]">{{ t.priority }}</span>
                <span class="spacer"></span>
                <span class="kc-time">{{ formatTime(t.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="kanban-column">
          <div class="kanban-header">In Progress ({{ groupedTickets.inProgress.length }})</div>
          <div class="kanban-list">
            <div v-for="t in groupedTickets.inProgress" :key="t.id" class="kanban-card" @click="selectTicket(t)">
              <div class="kc-title">#{{ t.id }} — {{ t.subject }}</div>
              <div class="kc-meta">
                <span :class="['chip', t.priority]">{{ t.priority }}</span>
                <span class="spacer"></span>
                <span class="kc-time">{{ formatTime(t.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="kanban-column">
          <div class="kanban-header">Closed ({{ groupedTickets.closed.length }})</div>
          <div class="kanban-list">
            <div v-for="t in groupedTickets.closed" :key="t.id" class="kanban-card" @click="selectTicket(t)">
              <div class="kc-title">#{{ t.id }} — {{ t.subject }}</div>
              <div class="kc-meta">
                <span :class="['chip', t.priority]">{{ t.priority }}</span>
                <span class="spacer"></span>
                <span class="kc-time">{{ formatTime(t.created_at) }}</span>
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
    </div>

    <!-- Details Drawer -->
    <div v-if="drawerOpen && currentSelectedTicket" class="drawer-overlay" @click="closeDrawer">
      <div class="drawer" @click.stop>
        <div class="drawer-header">
          <div class="drawer-title">Ticket #{{ currentSelectedTicket.id }}</div>
          <button class="close-btn" @click="closeDrawer">✕</button>
        </div>
        <div class="drawer-subtitle">{{ currentSelectedTicket.subject }}</div>
        <div class="drawer-section">
          <div class="drawer-row">
            <span class="label">Status</span>
            <span :class="['value badge', currentSelectedTicket.status]">{{ currentSelectedTicket.status }}</span>
          </div>
          <div class="drawer-row">
            <span class="label">Priority</span>
            <span :class="['value badge', currentSelectedTicket.priority]">{{ currentSelectedTicket.priority }}</span>
          </div>
          <div class="drawer-row">
            <span class="label">Category</span>
            <span class="value">{{ currentSelectedTicket.category }}</span>
          </div>
          <div class="drawer-row">
            <span class="label">Client</span>
            <span class="value">{{ currentSelectedTicket.client || 'N/A' }}</span>
          </div>
          <div class="drawer-row">
            <span class="label">Created</span>
            <span class="value">{{ formatTime(currentSelectedTicket.created_at) }}</span>
          </div>
        </div>
        <div class="drawer-message">
          {{ currentSelectedTicket.message }}
        </div>
        <div class="drawer-tags" v-if="currentSelectedTicket.tags?.length">
          <span v-for="t in currentSelectedTicket.tags" :key="t" class="tag">{{ t }}</span>
        </div>
        <div class="drawer-actions">
          <BaseButton @click="sendTicketToAI" variant="primary">🤖 Send to AI</BaseButton>
          <BaseButton @click="editTicket" variant="warning">✏️ Edit</BaseButton>
          <BaseButton @click="deleteTicket" variant="danger">🗑️ Delete</BaseButton>
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
  name: 'TicketsView',
  components: {
    BaseButton,
    DashboardWidget,
    SearchBar
  },
  props: {
    tickets: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    selectedTicket: { type: Object, default: null }
  },
  emits: ['create-ticket', 'update-ticket', 'delete-ticket', 'load-tickets', 'view-change', 'send-to-ai'],
  data() {
    return {
      currentView: 'list',
      currentSelectedTicket: null,
      drawerOpen: false,
      submitting: false,
      searchQuery: '',
      sortBy: 'newest',
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
      const search = this.searchQuery.trim().toLowerCase()
      return this.tickets.filter(ticket => {
        const statusMatch = this.filters.status === 'all' || ticket.status === this.filters.status
        const priorityMatch = this.filters.priority === 'all' || ticket.priority === this.filters.priority
        const categoryMatch = this.filters.category === 'all' || ticket.category === this.filters.category
        const searchMatch = !search ||
          (ticket.subject && ticket.subject.toLowerCase().includes(search)) ||
          (ticket.client && ticket.client.toLowerCase().includes(search)) ||
          (ticket.message && ticket.message.toLowerCase().includes(search))
        return statusMatch && priorityMatch && categoryMatch && searchMatch
      })
    },
    sortedTickets() {
      const tickets = [...this.filteredTickets]
      if (this.sortBy === 'priority') {
        const order = { urgent: 4, high: 3, medium: 2, low: 1 }
        tickets.sort((a, b) => (order[b.priority] || 0) - (order[a.priority] || 0))
      } else if (this.sortBy === 'oldest') {
        tickets.sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0))
      } else {
        tickets.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0))
      }
      return tickets
    },
    groupedTickets() {
      const groups = { open: [], inProgress: [], closed: [] }
      this.filteredTickets.forEach(t => {
        if (t.status === 'in-progress') groups.inProgress.push(t)
        else if (t.status === 'closed') groups.closed.push(t)
        else groups.open.push(t)
      })
      return groups
    },
    ticketStats() {
      return {
        open: this.tickets.filter(t => t.status !== 'closed' && t.status !== 'in-progress' ? t : t.status === 'open').length,
        inProgress: this.tickets.filter(t => t.status === 'in-progress').length,
        closed: this.tickets.filter(t => t.status === 'closed').length
      }
    }
  },
  watch: {
    selectedTicket: {
      immediate: true,
      handler(newTicket) {
        if (newTicket) {
          this.currentSelectedTicket = newTicket
          this.drawerOpen = true
          this.currentView = 'list'
        }
      }
    }
  },
  methods: {
    selectTicket(ticket) {
      this.currentSelectedTicket = ticket
      this.drawerOpen = true
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
      this.currentView = 'create'
      this.ticketForm = { ...this.currentSelectedTicket }
      this.drawerOpen = false
    },
    async deleteTicket() {
      if (confirm('Are you sure you want to delete this ticket?')) {
        try {
          await this.$emit('delete-ticket', this.currentSelectedTicket.id)
          this.currentSelectedTicket = null
          this.drawerOpen = false
          this.currentView = 'list'
        } catch (error) {
          console.error('Error deleting ticket:', error)
        }
      }
    },
    applyFilters() {
      // reactive filters + search cause re-render
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
      if (index > -1) this.ticketForm.tags.splice(index, 1)
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
      const d = new Date(timestamp)
      return d.toLocaleDateString() + ' ' + d.toLocaleTimeString()
    },
    async sendTicketToAI() {
      if (!this.currentSelectedTicket) return
      const t = this.currentSelectedTicket
      const ticketData = {
        id: t.id,
        subject: t.subject,
        message: t.message,
        priority: t.priority,
        status: t.status,
        category: t.category,
        client: t.client,
        created_at: t.created_at,
        updated_at: t.updated_at,
        tags: t.tags
      }
      try {
        this.$emit('send-to-ai', ticketData)
        this.$router.push('/ai')
      } catch (error) {
        console.error('Error sending ticket to AI:', error)
        alert('Failed to send ticket to AI.')
      }
    },
    closeDrawer() {
      this.drawerOpen = false
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
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  padding: 16px 24px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-left, .nav-right { display: flex; gap: 12px; }
.nav-center { flex: 1; text-align: center; }
.view-title { margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; }

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
.nav-btn:hover { background: white; color: #0f172a; border-color: #3b82f6; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
.back-btn { background: #3b82f6; color: white; border-color: #3b82f6; }
.back-btn:hover { background: #2563eb; border-color: #2563eb; }
 
/* Make the content span the entire screen width */
.tickets-content { width: 100%; max-width: none; margin: 0; height: 100%; padding: 24px; }
 
/* Stats */
.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}
.stat-card {
  border-radius: 16px;
  padding: 16px;
  color: white;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.gradient-blue { background: linear-gradient(135deg, #60a5fa, #2563eb); }
.gradient-purple { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
.gradient-green { background: linear-gradient(135deg, #34d399, #059669); }
.gradient-rose { background: linear-gradient(135deg, #fb7185, #e11d48); }
.stat-header { display: flex; align-items: center; gap: 8px; opacity: 0.95; }
.stat-icon { font-size: 18px; }
.stat-label { font-weight: 700; letter-spacing: 0.02em; }
.stat-value { font-size: 32px; font-weight: 800; line-height: 1; margin: 10px 0 4px; }
.stat-foot { font-size: 12px; opacity: 0.9; }

/* Toolbar */
.tickets-toolbar {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  margin-bottom: 16px;
}
.toolbar-right { display: flex; gap: 8px; align-items: center; }
.filter-select {
  padding: 8px 12px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 6px;
  background: white;
  font-size: 13px;
}

/* View switcher */
.view-switcher {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  background: rgba(255, 255, 255, 0.8);
  padding: 8px;
  border-radius: 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
}
.view-btn { flex: 1; padding: 12px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; background: transparent; color: #64748b; }
.view-btn.active { background: white; color: #0f172a; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

/* Ticket list */
.ticket-grid { background: rgba(255, 255, 255, 0.85); border-radius: 12px; padding: 24px; border: 1px solid rgba(226, 232, 240, 0.8); }
.loading-state, .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
.loading-spinner { animation: spin 1s linear infinite; font-size: 32px; display: block; margin-bottom: 16px; }
.empty-state span { font-size: 48px; display: block; margin-bottom: 16px; }

.tickets-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
.fancy-card { background: white; border-radius: 16px; padding: 16px; border: 1px solid rgba(226, 232, 240, 0.6); box-shadow: 0 10px 24px rgba(2,6,23,0.04); transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
.fancy-card:hover { transform: translateY(-4px); box-shadow: 0 14px 28px rgba(2,6,23,0.08); }

.ticket-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.ticket-header .left { min-width: 0; }
.ticket-id { font-weight: 700; color: #3b82f6; font-size: 12px; }
.ticket-title { font-weight: 700; color: #0f172a; line-height: 1.35; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ticket-priority { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.ticket-priority.low { background: #d1fae5; color: #047857; }
.ticket-priority.medium { background: #fef3c7; color: #b45309; }
.ticket-priority.high { background: #fee2e2; color: #b91c1c; }
.ticket-priority.urgent { background: #fecaca; color: #991b1b; }

.ticket-meta { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6b7280; margin-bottom: 10px; }
.ticket-meta .dot { opacity: .6; }
.ticket-meta .spacer { flex: 1; }
.ticket-category { font-size: 11px; color: #6b7280; background: rgba(107, 114, 128, 0.1); padding: 2px 6px; border-radius: 4px; }

.ticket-footer { display: flex; align-items: center; justify-content: space-between; }
.tags { display: flex; gap: 6px; flex-wrap: wrap; }
.tag { background: #3b82f6; color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.ticket-status { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.ticket-status.open { background: #dbeafe; color: #1e40af; }
.ticket-status.closed { background: #d1fae5; color: #059669; }
.ticket-status.in-progress { background: #ede9fe; color: #5b21b6; }

/* Kanban */
.kanban-board { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.kanban-column { background: rgba(255, 255, 255, 0.85); border: 1px solid rgba(226,232,240,0.8); border-radius: 12px; padding: 12px; display: flex; flex-direction: column; max-height: 70vh; }
.kanban-header { font-weight: 800; color: #0f172a; padding: 8px 6px 12px; border-bottom: 1px solid rgba(226,232,240,0.8); margin-bottom: 8px; }
.kanban-list { overflow: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 6px; }
.kanban-card { background: white; border: 1px solid rgba(226,232,240,0.7); border-radius: 10px; padding: 12px; box-shadow: 0 6px 16px rgba(2,6,23,0.04); transition: transform .2s ease; cursor: pointer; }
.kanban-card:hover { transform: translateY(-2px); }
.kc-title { font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.kc-meta { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6b7280; }
.chip { padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.chip.low { background: #e2f7ed; color: #047857; }
.chip.medium { background: #fef3c7; color: #b45309; }
.chip.high { background: #fee2e2; color: #b91c1c; }
.chip.urgent { background: #fecaca; color: #991b1b; }
.kc-meta .spacer { flex: 1; }

/* Create form (inherits most styles from existing app) */
.create-ticket-view { margin-top: 8px; }
.ticket-form { display: flex; flex-direction: column; gap: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-row:last-child { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-weight: 600; color: #374151; font-size: 14px; }
.form-input, .form-select, .form-textarea { padding: 10px 12px; border: 1px solid rgba(209, 213, 219, 0.8); border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.form-textarea { resize: vertical; min-height: 100px; }
.tags-input { border: 1px solid rgba(209,213,219,0.8); border-radius: 8px; padding: 8px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.tags-list { display: flex; flex-wrap: wrap; gap: 6px; }
.tag-remove { background: none; border: none; color: white; cursor: pointer; font-size: 14px; line-height: 1; }
.tag-input { flex: 1; border: none; outline: none; padding: 4px 0; font-size: 14px; min-width: 100px; }
.form-actions { display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid rgba(226,232,240,0.6); }

/* Drawer */
.drawer-overlay { position: fixed; inset: 0; background: rgba(2,6,23,0.4); display: flex; justify-content: flex-end; z-index: 120; }
.drawer { width: min(560px, 92vw); background: #ffffff; height: 100%; box-shadow: -10px 0 30px rgba(2,6,23,0.2); animation: slideIn .2s ease; display: flex; flex-direction: column; }
.drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid rgba(226,232,240,0.8); }
.drawer-title { font-size: 18px; font-weight: 800; color: #0f172a; }
.close-btn { background: transparent; border: none; font-size: 18px; cursor: pointer; color: #6b7280; }
.drawer-subtitle { padding: 12px 20px; color: #374151; font-weight: 600; }
.drawer-section { padding: 8px 20px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.drawer-row { display: flex; align-items: center; gap: 8px; }
.drawer-row .label { width: 90px; color: #64748b; font-weight: 600; font-size: 13px; }
.drawer-row .value { color: #0f172a; font-size: 14px; }
.badge { padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.badge.open { background: #dbeafe; color: #1e40af; }
.badge.closed { background: #d1fae5; color: #059669; }
.badge.in-progress { background: #ede9fe; color: #5b21b6; }
.badge.low { background: #e2f7ed; color: #047857; }
.badge.medium { background: #fef3c7; color: #b45309; }
.badge.high { background: #fee2e2; color: #b91c1c; }
.badge.urgent { background: #fecaca; color: #991b1b; }
.drawer-message { margin: 12px 20px; background: rgba(248,250,252,0.9); border: 1px solid rgba(226,232,240,0.8); border-radius: 8px; padding: 12px; white-space: pre-wrap; line-height: 1.5; }
.drawer-tags { display: flex; gap: 6px; flex-wrap: wrap; margin: 0 20px; }
.drawer-actions { display: flex; gap: 10px; justify-content: flex-end; margin: 16px 20px 20px; padding-top: 12px; border-top: 1px solid rgba(226,232,240,0.8); }

@keyframes slideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 1024px) {
  .kanban-board { grid-template-columns: 1fr; }
  .drawer-section { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .tickets-toolbar { grid-template-columns: 1fr; }
  .toolbar-right { flex-wrap: wrap; }
}
</style> 