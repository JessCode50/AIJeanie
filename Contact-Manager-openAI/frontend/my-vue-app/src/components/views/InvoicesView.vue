<template>
  <div class="invoices-view">
    <!-- Fullscreen Navigation Bar -->
    <div class="fullscreen-nav">
      <div class="nav-left">
        <button class="nav-btn back-btn" @click="$router.push('/dashboard')">
          ← Dashboard
        </button>
      </div>
      <div class="nav-center">
        <h1 class="view-title">💰 Invoice Management</h1>
      </div>
      <div class="nav-right">
        <button class="nav-btn" @click="$router.push('/tickets')">
          🎫 Tickets
        </button>
        <button class="nav-btn" @click="$router.push('/clients')">
          👥 Clients
        </button>
        <button class="nav-btn" @click="$router.push('/ai')">
          🤖 AI Assistant
        </button>
      </div>
    </div>

    <!-- Invoice Management Content -->
    <div class="invoices-content">
      <!-- Invoice Stats Dashboard -->
      <div class="invoice-stats">
        <div class="stat-card">
          <div class="stat-icon">📄</div>
          <div class="stat-info">
            <div class="stat-value">{{ invoiceStats.total }}</div>
            <div class="stat-label">Total Invoices</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">💰</div>
          <div class="stat-info">
            <div class="stat-value">${{ invoiceStats.totalRevenue.toLocaleString() }}</div>
            <div class="stat-label">Total Revenue</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">⏰</div>
          <div class="stat-info">
            <div class="stat-value">{{ invoiceStats.unpaid }}</div>
            <div class="stat-label">Unpaid Invoices</div>
          </div>
        </div>
        
        <div class="stat-card overdue">
          <div class="stat-icon">⚠️</div>
          <div class="stat-info">
            <div class="stat-value">{{ invoiceStats.overdue }}</div>
            <div class="stat-label">Overdue</div>
          </div>
        </div>
      </div>

      <!-- Invoice View Switcher -->
      <div class="view-switcher">
        <button 
          :class="['view-btn', { active: currentView === 'list' }]"
          @click="currentView = 'list'"
        >
          📋 Invoice List
        </button>
        <button 
          :class="['view-btn', { active: currentView === 'create' }]"
          @click="currentView = 'create'"
        >
          ➕ Generate Invoice
        </button>
        <button 
          v-if="selectedInvoice"
          :class="['view-btn', { active: currentView === 'view' }]"
          @click="currentView = 'view'"
        >
          👁️ View Invoice
        </button>
        <button 
          :class="['view-btn', { active: currentView === 'payments' }]"
          @click="currentView = 'payments'"
        >
          💳 Payment Methods
        </button>
      </div>

      <!-- Invoice List View -->
      <div v-if="currentView === 'list'" class="invoice-list-view">
        <DashboardWidget
          title="Invoice Management"
          subtitle="Manage and track all invoices"
          icon="📄"
          icon-color="#f59e0b"
        >
          <!-- Filters & Search -->
          <div class="invoice-filters">
            <div class="filter-row">
              <select v-model="filters.status" class="filter-select">
                <option value="all">All Statuses</option>
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
              </select>
              
              <select v-model="filters.client" class="filter-select">
                <option value="all">All Clients</option>
                <option v-for="client in clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
              
              <input 
                type="date" 
                v-model="filters.dateFrom" 
                class="filter-input"
                placeholder="From Date"
              >
              <input 
                type="date" 
                v-model="filters.dateTo" 
                class="filter-input"
                placeholder="To Date"
              >
              
              <input 
                v-model="searchQuery" 
                class="search-input"
                placeholder="Search invoices..."
                type="text"
              >
              
              <BaseButton @click="refreshInvoices" variant="secondary" size="sm">
                🔄 Refresh
              </BaseButton>
            </div>
          </div>

          <!-- Invoice Table -->
          <div v-if="invoicesLoading" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Loading invoices...</p>
          </div>
          
          <div v-else-if="filteredInvoices.length === 0" class="empty-state">
            <div class="empty-icon">📄</div>
            <p>No invoices found</p>
            <BaseButton @click="currentView = 'create'" variant="primary">
              Generate First Invoice
            </BaseButton>
          </div>
          
          <div v-else class="invoice-table">
            <div class="table-header">
              <div class="header-cell">Invoice #</div>
              <div class="header-cell">Client</div>
              <div class="header-cell">Amount</div>
              <div class="header-cell">Due Date</div>
              <div class="header-cell">Status</div>
              <div class="header-cell">Actions</div>
            </div>
            
            <div 
              v-for="invoice in paginatedInvoices" 
              :key="invoice.id"
              class="invoice-row"
              @click="selectInvoice(invoice)"
            >
              <div class="table-cell invoice-number">
                <span class="invoice-id">#{{ invoice.id }}</span>
              </div>
              <div class="table-cell client-info">
                <div class="client-name">{{ invoice.clientName || 'Unknown Client' }}</div>
                <div class="client-email">{{ invoice.clientEmail || '' }}</div>
              </div>
              <div class="table-cell amount">
                <span class="amount-value">${{ parseFloat(invoice.total || 0).toFixed(2) }}</span>
              </div>
              <div class="table-cell due-date">
                <span :class="['date-value', { overdue: isOverdue(invoice.duedate) }]">
                  {{ formatDate(invoice.duedate) }}
                </span>
              </div>
              <div class="table-cell status">
                <span :class="['status-badge', getStatusClass(invoice.status)]">
                  {{ invoice.status || 'Unknown' }}
                </span>
              </div>
              <div class="table-cell actions">
                <BaseButton @click.stop="viewInvoice(invoice)" size="sm" variant="secondary">
                  View
                </BaseButton>
                <BaseButton @click.stop="sendToAI(invoice)" size="sm" variant="primary">
                  Send to AI
                </BaseButton>
                <BaseButton 
                  v-if="invoice.status === 'unpaid'" 
                  @click.stop="capturePayment(invoice)" 
                  size="sm" 
                  variant="success"
                >
                  Capture Payment
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination">
            <button 
              @click="currentPage--" 
              :disabled="currentPage === 1"
              class="pagination-btn"
            >
              Previous
            </button>
            <span class="pagination-info">
              Page {{ currentPage }} of {{ totalPages }}
            </span>
            <button 
              @click="currentPage++" 
              :disabled="currentPage === totalPages"
              class="pagination-btn"
            >
              Next
            </button>
          </div>
        </DashboardWidget>
      </div>

      <!-- Generate Invoice View -->
      <div v-else-if="currentView === 'create'" class="create-invoice">
        <DashboardWidget
          title="Generate New Invoice"
          subtitle="Create a new invoice for a client"
          icon="➕"
          icon-color="#10b981"
        >
          <form @submit.prevent="generateInvoice" class="invoice-form">
            <div class="form-section">
              <h3>Client Information</h3>
              <div class="form-row">
                <div class="form-group">
                  <label>Select Client *</label>
                  <select v-model="invoiceForm.clientId" required class="form-select">
                    <option value="">Choose a client...</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                      {{ client.name }} ({{ client.email }})
                    </option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h3>Invoice Details</h3>
              <div class="form-row">
                <div class="form-group">
                  <label>Description</label>
                  <input 
                    v-model="invoiceForm.description" 
                    class="form-input"
                    placeholder="Invoice description"
                  >
                </div>
                <div class="form-group">
                  <label>Due Date</label>
                  <input 
                    v-model="invoiceForm.dueDate" 
                    type="date" 
                    class="form-input"
                    required
                  >
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h3>Line Items</h3>
              <div class="line-items">
                <div class="line-items-header">
                  <div>Description</div>
                  <div>Quantity</div>
                  <div>Rate</div>
                  <div>Total</div>
                  <div>Action</div>
                </div>
                
                <div 
                  v-for="(item, index) in invoiceForm.items" 
                  :key="index" 
                  class="line-item"
                >
                  <input 
                    v-model="item.description" 
                    placeholder="Description" 
                    class="item-input"
                    required
                  >
                  <input 
                    v-model.number="item.quantity" 
                    type="number" 
                    placeholder="1" 
                    class="item-input quantity"
                    min="1"
                    required
                  >
                  <input 
                    v-model.number="item.rate" 
                    type="number" 
                    step="0.01" 
                    placeholder="0.00" 
                    class="item-input rate"
                    min="0"
                    required
                  >
                  <span class="item-total">
                    ${{ ((item.quantity || 0) * (item.rate || 0)).toFixed(2) }}
                  </span>
                  <BaseButton 
                    @click="removeItem(index)" 
                    variant="danger" 
                    size="sm"
                    type="button"
                  >
                    Remove
                  </BaseButton>
                </div>
                
                <div class="line-item-actions">
                  <BaseButton @click="addItem" type="button" variant="secondary">
                    Add Line Item
                  </BaseButton>
                </div>
              </div>
              
              <div class="invoice-total">
                <div class="total-row">
                  <span class="total-label">Subtotal:</span>
                  <span class="total-value">${{ calculateSubtotal().toFixed(2) }}</span>
                </div>
                <div class="total-row">
                  <span class="total-label">Tax ({{ taxRate }}%):</span>
                  <span class="total-value">${{ calculateTax().toFixed(2) }}</span>
                </div>
                <div class="total-row final">
                  <span class="total-label">Total:</span>
                  <span class="total-value">${{ calculateTotal().toFixed(2) }}</span>
                </div>
              </div>
            </div>
            
            <div class="form-actions">
              <BaseButton @click="currentView = 'list'" variant="secondary" type="button">
                Cancel
              </BaseButton>
              <BaseButton 
                type="submit" 
                variant="primary" 
                :loading="submitting"
              >
                {{ submitting ? 'Generating...' : 'Generate Invoice' }}
              </BaseButton>
            </div>
          </form>
        </DashboardWidget>
      </div>

      <!-- View Invoice Details -->
      <div v-else-if="currentView === 'view' && selectedInvoice" class="view-invoice">
        <DashboardWidget
          :title="`Invoice #${selectedInvoice.id}`"
          :subtitle="`${selectedInvoice.clientName || 'Unknown Client'}`"
          icon="👁️"
          icon-color="#3b82f6"
        >
          <div class="invoice-details">
            <div class="invoice-header">
              <div class="invoice-meta">
                <h2>Invoice #{{ selectedInvoice.id }}</h2>
                <span :class="['status-badge', 'large', getStatusClass(selectedInvoice.status)]">
                  {{ selectedInvoice.status || 'Unknown' }}
                </span>
              </div>
            </div>
            
            <div class="invoice-info-grid">
              <div class="info-section">
                <h4>Client Information</h4>
                <div class="info-content">
                  <p><strong>Name:</strong> {{ selectedInvoice.clientName || 'Unknown' }}</p>
                  <p><strong>Email:</strong> {{ selectedInvoice.clientEmail || 'N/A' }}</p>
                  <p><strong>Company:</strong> {{ selectedInvoice.clientCompany || 'N/A' }}</p>
                </div>
              </div>
              
              <div class="info-section">
                <h4>Invoice Details</h4>
                <div class="info-content">
                  <p><strong>Issue Date:</strong> {{ formatDate(selectedInvoice.date) }}</p>
                  <p><strong>Due Date:</strong> 
                    <span :class="{ overdue: isOverdue(selectedInvoice.duedate) }">
                      {{ formatDate(selectedInvoice.duedate) }}
                    </span>
                  </p>
                  <p><strong>Date Paid:</strong> {{ selectedInvoice.datepaid || 'Not Paid' }}</p>
                  <p><strong>Payment Method:</strong> {{ selectedInvoice.paymentmethod || 'N/A' }}</p>
                </div>
              </div>
              
              <div class="info-section">
                <h4>Amount Details</h4>
                <div class="info-content">
                  <p><strong>Subtotal:</strong> ${{ selectedInvoice.subtotal || '0.00' }}</p>
                  <p><strong>Tax:</strong> ${{ selectedInvoice.tax || '0.00' }}</p>
                  <p class="total-amount"><strong>Total:</strong> ${{ selectedInvoice.total || '0.00' }}</p>
                </div>
              </div>
            </div>
            
            <div class="invoice-actions">
              <BaseButton 
                v-if="selectedInvoice.status === 'unpaid'" 
                @click="capturePayment(selectedInvoice)" 
                variant="success"
              >
                💳 Process Payment
              </BaseButton>
              <BaseButton @click="downloadInvoice(selectedInvoice)" variant="secondary">
                📥 Download PDF
              </BaseButton>
              <BaseButton @click="emailInvoice(selectedInvoice)" variant="secondary">
                📧 Email to Client
              </BaseButton>
              <BaseButton @click="sendToAI(selectedInvoice)" variant="primary">
                🤖 Send to AI Assistant
              </BaseButton>
              <BaseButton @click="editInvoice(selectedInvoice)" variant="secondary">
                ✏️ Edit Invoice
              </BaseButton>
            </div>
          </div>
        </DashboardWidget>
      </div>

      <!-- Payment Methods View -->
      <div v-else-if="currentView === 'payments'" class="payment-methods-view">
        <DashboardWidget
          title="Payment Methods"
          subtitle="Available payment processing options"
          icon="💳"
          icon-color="#8b5cf6"
        >
          <div v-if="loadingPaymentMethods" class="loading-state">
            <div class="loading-spinner"></div>
            <p>Loading payment methods...</p>
          </div>
          
          <div v-else class="payment-methods">
            <div 
              v-for="method in paymentMethods" 
              :key="method.id" 
              class="payment-method-card"
            >
              <div class="method-icon">
                {{ getPaymentIcon(method.name) }}
              </div>
              <div class="method-info">
                <h4>{{ method.name }}</h4>
                <p>{{ method.description || 'Payment processing method' }}</p>
              </div>
              <div class="method-status">
                <span :class="['status', method.enabled ? 'active' : 'inactive']">
                  {{ method.enabled ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
            
            <div v-if="paymentMethods.length === 0" class="empty-state">
              <div class="empty-icon">💳</div>
              <p>No payment methods configured</p>
            </div>
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
  name: 'InvoicesView',
  components: {
    BaseButton,
    DashboardWidget
  },
  props: {
    invoices: {
      type: Array,
      default: () => []
    },
    clients: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    invoicesLoading: {
      type: Boolean,
      default: false
    }
  },
  emits: [
    'generate-invoice', 
    'capture-payment', 
    'load-invoices', 
    'load-payment-methods',
    'send-to-ai',
    'download-invoice',
    'email-invoice'
  ],
  data() {
    return {
      currentView: 'list',
      selectedInvoice: null,
      submitting: false,
      loadingPaymentMethods: false,
      searchQuery: '',
      currentPage: 1,
      itemsPerPage: 10,
      taxRate: 8.5, // Default tax rate
      
      filters: {
        status: 'all',
        client: 'all',
        dateFrom: '',
        dateTo: ''
      },
      
      invoiceForm: {
        clientId: '',
        description: '',
        dueDate: '',
        items: [
          { description: '', quantity: 1, rate: 0 }
        ]
      },
      
      paymentMethods: []
    }
  },
  computed: {
    filteredInvoices() {
      let filtered = this.invoices.filter(invoice => {
        const statusMatch = this.filters.status === 'all' || 
          invoice.status === this.filters.status
        
        const clientMatch = this.filters.client === 'all' || 
          invoice.userid === this.filters.client
        
        const searchMatch = !this.searchQuery || 
          invoice.id.toString().includes(this.searchQuery.toLowerCase()) ||
          (invoice.clientName && invoice.clientName.toLowerCase().includes(this.searchQuery.toLowerCase()))
        
        let dateMatch = true
        if (this.filters.dateFrom) {
          dateMatch = dateMatch && new Date(invoice.duedate) >= new Date(this.filters.dateFrom)
        }
        if (this.filters.dateTo) {
          dateMatch = dateMatch && new Date(invoice.duedate) <= new Date(this.filters.dateTo)
        }
        
        return statusMatch && clientMatch && searchMatch && dateMatch
      })
      
      return filtered.sort((a, b) => new Date(b.duedate) - new Date(a.duedate))
    },
    
    paginatedInvoices() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredInvoices.slice(start, end)
    },
    
    totalPages() {
      return Math.ceil(this.filteredInvoices.length / this.itemsPerPage)
    },
    
    invoiceStats() {
      const total = this.invoices.length
      const unpaid = this.invoices.filter(inv => inv.status === 'unpaid').length
      const overdue = this.invoices.filter(inv => 
        inv.status === 'unpaid' && this.isOverdue(inv.duedate)
      ).length
      const totalRevenue = this.invoices
        .filter(inv => inv.status === 'paid')
        .reduce((sum, inv) => sum + parseFloat(inv.total || 0), 0)
      
      return { total, unpaid, overdue, totalRevenue }
    }
  },
  methods: {
    selectInvoice(invoice) {
      this.selectedInvoice = invoice
      this.currentView = 'view'
    },
    
    viewInvoice(invoice) {
      this.selectInvoice(invoice)
    },
    
    async generateInvoice() {
      this.submitting = true
      try {
        const invoiceData = {
          ...this.invoiceForm,
          subtotal: this.calculateSubtotal(),
          tax: this.calculateTax(),
          total: this.calculateTotal()
        }
        
        await this.$emit('generate-invoice', invoiceData)
        this.resetForm()
        this.currentView = 'list'
      } catch (error) {
        console.error('Error generating invoice:', error)
        alert('Failed to generate invoice. Please try again.')
      } finally {
        this.submitting = false
      }
    },
    
    async capturePayment(invoice) {
      if (confirm(`Capture payment for Invoice #${invoice.id}?`)) {
        try {
          await this.$emit('capture-payment', invoice.id)
          // Refresh invoices after payment capture
          this.refreshInvoices()
        } catch (error) {
          console.error('Error capturing payment:', error)
          alert('Failed to capture payment. Please try again.')
        }
      }
    },
    
    sendToAI(invoice) {
      this.$emit('send-to-ai', invoice)
      this.$router.push('/ai')
    },
    
    downloadInvoice(invoice) {
      this.$emit('download-invoice', invoice)
    },
    
    emailInvoice(invoice) {
      this.$emit('email-invoice', invoice)
    },
    
    editInvoice(invoice) {
      // Switch to create view with pre-filled data
      this.invoiceForm = {
        clientId: invoice.userid,
        description: invoice.description || '',
        dueDate: invoice.duedate,
        items: [{ description: 'Service', quantity: 1, rate: parseFloat(invoice.total || 0) }]
      }
      this.currentView = 'create'
    },
    
    addItem() {
      this.invoiceForm.items.push({
        description: '',
        quantity: 1,
        rate: 0
      })
    },
    
    removeItem(index) {
      if (this.invoiceForm.items.length > 1) {
        this.invoiceForm.items.splice(index, 1)
      }
    },
    
    calculateSubtotal() {
      return this.invoiceForm.items.reduce((sum, item) => {
        return sum + ((item.quantity || 0) * (item.rate || 0))
      }, 0)
    },
    
    calculateTax() {
      return this.calculateSubtotal() * (this.taxRate / 100)
    },
    
    calculateTotal() {
      return this.calculateSubtotal() + this.calculateTax()
    },
    
    resetForm() {
      this.invoiceForm = {
        clientId: '',
        description: '',
        dueDate: '',
        items: [{ description: '', quantity: 1, rate: 0 }]
      }
    },
    
    refreshInvoices() {
      this.$emit('load-invoices')
    },
    
    async loadPaymentMethods() {
      this.loadingPaymentMethods = true
      try {
        await this.$emit('load-payment-methods')
      } catch (error) {
        console.error('Error loading payment methods:', error)
      } finally {
        this.loadingPaymentMethods = false
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString()
    },
    
    isOverdue(dueDate) {
      if (!dueDate) return false
      return new Date(dueDate) < new Date()
    },
    
    getStatusClass(status) {
      const statusMap = {
        'paid': 'paid',
        'unpaid': 'unpaid',
        'overdue': 'overdue',
        'cancelled': 'cancelled',
        'refunded': 'refunded'
      }
      return statusMap[status?.toLowerCase()] || 'unknown'
    },
    
    getPaymentIcon(methodName) {
      const iconMap = {
        'paypal': '💙',
        'stripe': '💳',
        'bank': '🏦',
        'credit': '💳',
        'cash': '💵',
        'check': '📋'
      }
      
      const key = Object.keys(iconMap).find(k => 
        methodName?.toLowerCase().includes(k)
      )
      return iconMap[key] || '💰'
    }
  },
  
  mounted() {
    this.$emit('load-invoices')
    this.loadPaymentMethods()
  }
}
</script>

<style scoped>
.invoices-view {
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
  border-color: #f59e0b;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.back-btn {
  background: #f59e0b;
  color: white;
  border-color: #f59e0b;
}

.invoices-content {
  padding: 24px;
  width: 100%;
  max-width: none;
  margin: 0;
}

/* Invoice Stats */
.invoice-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  border: 1px solid rgba(226,232,240,0.5);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-card.overdue {
  border-color: rgba(239, 68, 68, 0.3);
  background: rgba(254, 242, 242, 0.95);
}

.stat-icon {
  font-size: 32px;
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(245, 158, 11, 0.1);
  border-radius: 12px;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

/* View Switcher */
.view-switcher {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  background: rgba(255, 255, 255, 0.8);
  padding: 8px;
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.view-btn {
  flex: 1;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.view-btn:hover {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.view-btn.active {
  background: #f59e0b;
  color: white;
  box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
}

/* Invoice Filters */
.invoice-filters {
  margin-bottom: 24px;
}

.filter-row {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

.filter-select,
.filter-input,
.search-input {
  padding: 10px 14px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  transition: all 0.2s ease;
}

.filter-select {
  min-width: 140px;
}

.filter-input {
  min-width: 120px;
}

.search-input {
  min-width: 200px;
  flex: 1;
}

.filter-select:focus,
.filter-input:focus,
.search-input:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

/* Invoice Table */
.invoice-table {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  width: 100%;
}

.table-header {
  display: grid;
  grid-template-columns: 140px 1fr 140px 140px 140px 200px;
  gap: 16px;
  padding: 20px 24px;
  background: rgba(248, 250, 252, 0.8);
  border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}

.header-cell {
  font-weight: 600;
  color: #374151;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.invoice-row {
  display: grid;
  grid-template-columns: 140px 1fr 140px 140px 140px 200px;
  gap: 16px;
  padding: 20px 24px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 80px;
  align-items: center;
}

.invoice-row:hover {
  background: rgba(245, 158, 11, 0.05);
  transform: translateX(4px);
}

.table-cell {
  display: flex;
  align-items: center;
  font-size: 14px;
}

.invoice-id {
  font-family: 'Monaco', monospace;
  font-weight: 600;
  color: #f59e0b;
}

.client-name {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 2px;
}

.client-email {
  font-size: 12px;
  color: #64748b;
}

.amount-value {
  font-family: 'Monaco', monospace;
  font-weight: 600;
  color: #059669;
}

.date-value {
  font-size: 13px;
  color: #64748b;
}

.date-value.overdue {
  color: #dc2626;
  font-weight: 600;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.large {
  padding: 8px 16px;
  font-size: 12px;
}

.status-badge.paid {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-badge.unpaid {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-badge.overdue {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.status-badge.cancelled {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

.actions {
  display: flex;
  gap: 8px;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 24px;
  padding: 16px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.9);
  color: #64748b;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #f59e0b;
  color: white;
  border-color: #f59e0b;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

/* Invoice Form */
.invoice-form {
  max-width: 1000px;
  width: 100%;
}

.form-section {
  margin-bottom: 32px;
}

.form-section h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
  border-bottom: 2px solid rgba(245, 158, 11, 0.2);
  padding-bottom: 8px;
}

.form-row {
  display: flex;
  gap: 20px;
}

.form-group {
  flex: 1;
  min-width: 200px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.form-input,
.form-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  transition: all 0.2s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

/* Line Items */
.line-items {
  background: rgba(248, 250, 252, 0.5);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.line-items-header {
  display: grid;
  grid-template-columns: 2fr 100px 100px 100px 100px;
  gap: 12px;
  margin-bottom: 16px;
  font-weight: 600;
  color: #374151;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.line-item {
  display: grid;
  grid-template-columns: 2fr 100px 100px 100px 100px;
  gap: 12px;
  align-items: center;
  margin-bottom: 12px;
}

.item-input {
  padding: 8px 12px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 6px;
  background: white;
  font-size: 14px;
}

.item-input.quantity,
.item-input.rate {
  text-align: right;
  font-family: 'Monaco', monospace;
}

.item-total {
  font-family: 'Monaco', monospace;
  font-weight: 600;
  color: #059669;
  text-align: right;
}

.line-item-actions {
  margin-top: 16px;
}

.invoice-total {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 20px;
  margin-top: 20px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgba(226, 232, 240, 0.3);
}

.total-row.final {
  border-bottom: none;
  border-top: 2px solid rgba(245, 158, 11, 0.3);
  padding-top: 16px;
  margin-top: 8px;
}

.total-label {
  font-weight: 500;
  color: #374151;
}

.total-row.final .total-label {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.total-value {
  font-family: 'Monaco', monospace;
  font-weight: 600;
  color: #059669;
}

.total-row.final .total-value {
  font-size: 20px;
  color: #f59e0b;
}

.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  padding-top: 24px;
  border-top: 1px solid rgba(226, 232, 240, 0.3);
}

/* Invoice Details */
.invoice-details {
  max-width: 1200px;
  width: 100%;
}

.invoice-header {
  margin-bottom: 40px;
}

.invoice-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
  padding: 24px;
  background: rgba(248, 250, 252, 0.6);
  border-radius: 16px;
}

.invoice-meta h2 {
  margin: 0;
  font-size: 36px;
  font-weight: 700;
  color: #0f172a;
}

.invoice-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 32px;
  margin-bottom: 40px;
}

.info-section {
  background: rgba(248, 250, 252, 0.6);
  border-radius: 16px;
  padding: 28px;
  min-height: 200px;
}

.info-section h4 {
  margin: 0 0 20px 0;
  font-size: 18px;
  font-weight: 600;
  color: #0f172a;
  border-bottom: 2px solid rgba(245, 158, 11, 0.2);
  padding-bottom: 12px;
}

.info-content p {
  margin: 12px 0;
  color: #374151;
  line-height: 1.6;
  font-size: 15px;
}

.info-content strong {
  color: #0f172a;
  font-weight: 600;
  display: inline-block;
  min-width: 120px;
}

.total-amount {
  font-size: 20px;
  color: #f59e0b !important;
  font-weight: 700;
}

.overdue {
  color: #dc2626 !important;
  font-weight: 600;
}

.invoice-actions {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  padding: 32px;
  background: rgba(248, 250, 252, 0.4);
  border-radius: 16px;
  justify-content: center;
}

/* Payment Methods */
.payment-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.payment-method-card {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  border: 1px solid rgba(226,232,240,0.5);
  transition: all 0.3s ease;
}

.payment-method-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.method-icon {
  font-size: 24px;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(139, 92, 246, 0.1);
  border-radius: 12px;
}

.method-info {
  flex: 1;
}

.method-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
}

.method-info p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.method-status .status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.method-status .status.active {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.method-status .status.inactive {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

/* Loading & Empty States */
.loading-state {
  text-align: center;
  padding: 40px;
  color: #64748b;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #f59e0b;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #64748b;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .table-header,
  .invoice-row {
    grid-template-columns: 100px 1fr 100px 100px 140px;
  }
  
  .table-header .header-cell:nth-child(3),
  .invoice-row .table-cell:nth-child(3) {
    display: none;
  }
}

@media (max-width: 768px) {
  .invoices-content {
    padding: 16px;
  }
  
  .filter-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-select,
  .filter-input,
  .search-input {
    width: 100%;
  }
  
  .table-header,
  .invoice-row {
    grid-template-columns: 1fr 100px 120px;
  }
  
  .table-header .header-cell:nth-child(2),
  .table-header .header-cell:nth-child(4),
  .invoice-row .table-cell:nth-child(2),
  .invoice-row .table-cell:nth-child(4) {
    display: none;
  }
  
  .form-row {
    flex-direction: column;
  }
  
  .line-items-header,
  .line-item {
    grid-template-columns: 1fr 80px 120px;
  }
  
  .line-items-header > div:nth-child(2),
  .line-item > *:nth-child(2) {
    display: none;
  }
  
  .invoice-actions {
    flex-direction: column;
  }
}
</style> 