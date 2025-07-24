// Enhanced Functionality for Contact Manager AI
// This file contains all the new methods and functionality for the enhanced features

export const enhancedMethods = {
  // Quick Actions
  executeQuickAction(actionId) {
    console.log('Executing quick action:', actionId);
    
    switch(actionId) {
      case 'create-ticket':
        this.view = 'tickets';
        this.currentTicketView = 'create';
        this.createTicket();
        break;
      case 'add-client':
        this.view = 'clients';
        this.createClient();
        break;
      case 'create-invoice':
        this.createInvoice();
        break;
      case 'backup-account':
        this.executeServerAction('backup-account');
        break;
      case 'check-ssl':
        this.executeServerAction('ssl-status');
        break;
      case 'system-status':
        this.view = 'servers';
        break;
      default:
        console.log('Unknown action:', actionId);
    }
  },

  // Server Actions
  executeServerAction(action) {
    console.log('Executing server action:', action);
    
    const serverActions = {
      'disk-usage': 'get disk usage for all accounts',
      'system-load': 'show system load average and performance metrics', 
      'account-list': 'list all cPanel accounts with status',
      'email-accounts': 'show email accounts and usage statistics',
      'ssl-status': 'check SSL certificate status for all domains',
      'backup-status': 'show backup status and recent backup information'
    };
    
    const message = serverActions[action] || `execute ${action}`;
    
    // Switch to AI view and send the command
    this.view = 'AI';
    this.userMessage = message;
    setTimeout(() => {
      this.aiClick();
    }, 500);
  },

  // Ticket Management Methods
  createTicket() {
    this.currentTicketView = 'create';
    this.ticketForm = {
      subject: '',
      message: '',
      category: 'general',
      priority: 'medium',
      clientId: '',
      attachments: [],
      tags: []
    };
  },

  editTicket(ticket) {
    this.selectedTicket = { ...ticket };
    this.ticketForm = {
      subject: ticket.subject || '',
      message: ticket.message || '',
      category: ticket.category || 'general',
      priority: ticket.priority || 'medium',
      clientId: ticket.clientId || '',
      attachments: ticket.attachments || [],
      tags: ticket.tags || []
    };
    this.currentTicketView = 'edit';
  },

  viewTicket(ticket) {
    this.selectedTicket = ticket;
    this.currentTicketView = 'view';
  },

  saveTicket() {
    console.log('Saving ticket:', this.ticketForm);
    
    if (this.currentTicketView === 'create') {
      // Create new ticket
      const newTicket = {
        id: Date.now(),
        ...this.ticketForm,
        status: 'open',
        created: new Date().toLocaleString(),
        updated: new Date().toLocaleString()
      };
      
      this.tickets.unshift(newTicket);
      
      // Make API call to create ticket
      fetch('http://localhost:8080/contacts/tickets/create', {
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify(newTicket)
      }).catch(error => {
        console.error('Error creating ticket:', error);
      });
        
    } else if (this.currentTicketView === 'edit') {
      // Update existing ticket
      const ticketIndex = this.tickets.findIndex(t => t.id === this.selectedTicket.id);
      if (ticketIndex !== -1) {
        this.tickets[ticketIndex] = {
          ...this.tickets[ticketIndex],
          ...this.ticketForm,
          updated: new Date().toLocaleString()
        };
      }
    }
    
    this.currentTicketView = 'list';
  },

  deleteTicket(ticket) {
    if (confirm(`Are you sure you want to delete ticket #${ticket.id}?`)) {
      const index = this.tickets.findIndex(t => t.id === ticket.id);
      if (index !== -1) {
        this.tickets.splice(index, 1);
      }
    }
  },

  refreshTickets() {
    console.log('Loading sample tickets...');
    // Add sample tickets for demo
    this.tickets = [
      { id: 1001, subject: 'Website Down', client: 'John Doe', clientEmail: 'john@example.com', message: 'My website is not loading properly and customers cannot access it.', status: 'open', priority: 'high', category: 'technical', created: '2 hours ago', updated: '1 hour ago' },
      { id: 1002, subject: 'Email Setup Help', client: 'Jane Smith', clientEmail: 'jane@example.com', message: 'I need assistance setting up email accounts for my domain.', status: 'pending', priority: 'medium', category: 'general', created: '1 day ago', updated: '4 hours ago' },
      { id: 1003, subject: 'Billing Question', client: 'Bob Wilson', clientEmail: 'bob@example.com', message: 'I have a question about charges on my recent invoice.', status: 'resolved', priority: 'low', category: 'billing', created: '3 days ago', updated: '2 days ago' },
      { id: 1004, subject: 'SSL Certificate Issue', client: 'Alice Brown', clientEmail: 'alice@example.com', message: 'SSL certificate expired and need to renew it urgently.', status: 'open', priority: 'critical', category: 'technical', created: '30 minutes ago', updated: 'Just now' }
    ];
  },

  exportTickets() {
    console.log('Exporting tickets...');
    const exportData = JSON.stringify(this.filteredTickets(), null, 2);
    const blob = new Blob([exportData], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `tickets-${new Date().toISOString().split('T')[0]}.json`;
    a.click();
    URL.revokeObjectURL(url);
  },

  // Ticket Helper Methods
  getStatusColor(status) {
    const colors = {
      'open': '#3b82f6',
      'pending': '#f59e0b', 
      'resolved': '#10b981',
      'closed': '#6b7280'
    };
    return colors[status?.toLowerCase()] || '#6b7280';
  },

  getPriorityColor(priority) {
    const colors = {
      'low': '#10b981',
      'medium': '#f59e0b',
      'high': '#ef4444', 
      'critical': '#dc2626'
    };
    return colors[priority?.toLowerCase()] || '#f59e0b';
  },

  // Tag Management
  addTicketTag() {
    const tag = (this.ticketTagInput || '').trim();
    if (tag && !this.ticketForm.tags.includes(tag)) {
      this.ticketForm.tags.push(tag);
      this.ticketTagInput = '';
    }
  },

  removeTicketTag(index) {
    this.ticketForm.tags.splice(index, 1);
  },

  // Client Management Methods
  createClient() {
    console.log('Creating new client...');
    this.currentClientView = 'create';
  },

  // Server Management Methods
  refreshServerData() {
    console.log('Refreshing server data...');
    this.executeServerAction('system-load');
  },

  createCpanelAccount() {
    console.log('Creating new cPanel account...');
  },

  // Invoice Management
  createInvoice() {
    console.log('Creating new invoice...');
  },

  // Filtering functionality (as method since we can't add computed)
  filteredTickets() {
    let filtered = this.tickets;
    
    // Apply search filter
    if (this.searchQuery) {
      filtered = filtered.filter(ticket => 
        (ticket.subject || '').toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        (ticket.client || '').toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        (ticket.id || '').toString().includes(this.searchQuery)
      );
    }
    
    // Apply status filter
    if (this.ticketFilters.status !== 'all') {
      filtered = filtered.filter(ticket => 
        (ticket.status || 'open').toLowerCase() === this.ticketFilters.status.toLowerCase()
      );
    }
    
    // Apply priority filter
    if (this.ticketFilters.priority !== 'all') {
      filtered = filtered.filter(ticket => 
        (ticket.priority || 'medium').toLowerCase() === this.ticketFilters.priority.toLowerCase()
      );
    }
    
    // Apply category filter
    if (this.ticketFilters.category !== 'all') {
      filtered = filtered.filter(ticket => 
        (ticket.category || 'general').toLowerCase() === this.ticketFilters.category.toLowerCase()
      );
    }
    
    return filtered;
  }
};

export const enhancedData = {
  // Ticketing System
  tickets: [],
  currentTicketView: 'list', // 'list', 'create', 'edit', 'view'
  selectedTicket: null,
  ticketForm: {
    subject: '',
    message: '',
    category: 'general',
    priority: 'medium',
    clientId: '',
    attachments: [],
    tags: []
  },
  ticketTagInput: '',
  ticketFilters: {
    status: 'all',
    priority: 'all',
    category: 'all'
  },
  searchQuery: '',

  // Client Management
  clients: [],
  currentClientView: 'list',
  selectedClient: null,

  // Server Management
  cpanelAccounts: [],
  emailAccounts: [],
  databases: [],
  serverStats: {
    cpuUsage: 23,
    memoryUsage: 67,
    diskUsage: 45,
    networkIO: 12
  },

  // Dashboard Features
  notifications: [
    { id: 1, type: 'warning', message: 'High memory usage detected', time: '5 min ago' },
    { id: 2, type: 'info', message: 'New ticket #1003 created', time: '10 min ago' },
    { id: 3, type: 'success', message: 'Backup completed successfully', time: '1 hour ago' }
  ],
  showNotifications: false,
  
  quickActions: [
    { id: 'create-ticket', label: 'New Ticket', icon: '🎫', color: '#3b82f6' },
    { id: 'add-client', label: 'Add Client', icon: '👤', color: '#10b981' },
    { id: 'create-invoice', label: 'Invoice', icon: '💰', color: '#f59e0b' },
    { id: 'backup-account', label: 'Backup', icon: '💾', color: '#8b5cf6' },
    { id: 'check-ssl', label: 'SSL Check', icon: '🔒', color: '#ef4444' },
    { id: 'system-status', label: 'System', icon: '⚡', color: '#06b6d4' }
  ],

  // Additional Features
  analyticsData: {
    totalTickets: 127,
    openTickets: 23,
    resolvedTickets: 89,
    clientSatisfaction: 4.8
  }
};

// Mixin for Vue component
export const enhancementMixin = {
  data() {
    return enhancedData;
  },
  methods: enhancedMethods,
  mounted() {
    // Load sample ticket data on mount
    this.refreshTickets();
  }
};

export default {
  enhancedMethods,
  enhancedData,
  enhancementMixin
}; 