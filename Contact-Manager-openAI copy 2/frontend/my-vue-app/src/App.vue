<!-- src/App.vue -->
<template>
  <div v-if="view === 'contacts'" class="contacts-view">
    <h1>{{ title }}</h1>
    <h1 class="subtitle">{{ subtitle }}</h1>
    <div class="contacts-list">
      <button 
        v-for="(contact, index) in contacts" 
        :key="index" 
        @click="contactClick(index)"
        class="contact-button"
      >
        {{ contact.name }}
      </button>
    </div>
    <div class="action-buttons">
      <button @click="view = 'new'" class="primary-button">
        Add New Contact
      </button>
      <button @click="view = 'AI'" class="ai-button">
        Open AI
      </button>
    </div>
  </div>

  <div v-else-if="view === 'read'" class="contact-detail-view">
    <h1>{{selectedContact.name}}</h1>
    <div class="contact-info">
      <p><strong>Email:</strong> {{ selectedContact.email }}</p>
      <p><strong>Phone:</strong> {{ selectedContact.phone }}</p>
    </div>
    <div class="action-buttons">
      <button @click="view ='contacts'" class="secondary-button">
      Back
    </button>
      <button @click="view ='edit'" class="primary-button">
      Edit
    </button>
      <button @click="deleteContact" class="danger-button">
      Delete
    </button>
    </div>
  </div>

  <div v-else-if="view === 'edit'" class="form-view">
    <h1>Edit Contact</h1>
    <form @submit.prevent="editedContact" class="contact-form">
      <div class="form-group">
        <label>Contact Name:</label>
        <input type="text" v-model="selectedContact.name" required>
      </div>
      <div class="form-group">
        <label>Email:</label>
        <input type="email" v-model="selectedContact.email" required>
      </div>
      <div class="form-group">
        <label>Phone Number:</label>
        <input type="tel" v-model="selectedContact.phone" required>
      </div>
      <div class="form-actions">
        <button type="submit" class="primary-button">Save Changes</button>
        <button type="button" @click="view ='read'" class="secondary-button">Cancel</button>
      </div>
    </form>
  </div>

  <div v-else-if="view === 'new'" class="form-view">
    <h1>New Contact</h1>
    <form @submit.prevent="newContact" class="contact-form">
      <div class="form-group">
        <label>Contact Name:</label>
        <input type="text" v-model="selectedContact.name" required>
      </div>
      <div class="form-group">
        <label>Email:</label>
        <input type="email" v-model="selectedContact.email" required>
      </div>
      <div class="form-group">
        <label>Phone Number:</label>
        <input type="tel" v-model="selectedContact.phone" required>
      </div>
      <div class="form-actions">
        <button type="submit" class="primary-button">Save New Contact</button>
        <button type="button" @click="view ='contacts'" class="secondary-button">Cancel</button>
      </div>
    </form>
  </div>

  <div v-else-if="view === 'AI'" class="ai-chat-container">
    <!-- Header -->
    <div class="chat-header">
      <div class="header-left">
        <div class="ai-avatar">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
          </svg>
        </div>
        <div class="header-info">
          <h2>AI Assistant</h2>
          <span class="status-indicator">Online</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="icon-btn" @click="aiClear()" title="Clear History">
          <span class="icon-text">🗑️</span>
          <span class="btn-label">Clear</span>
        </button>
        <button class="icon-btn" @click="view = 'session'" title="View Session">
          <span class="icon-text">📋</span>
          <span class="btn-label">Session</span>
        </button>
        <button class="icon-btn" @click="aiLog()" title="View History Log">
          <span class="icon-text">📄</span>
          <span class="btn-label">History</span>
    </button>
  </div>
      </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
      <!-- Chat Messages -->
      <div class="chat-messages" ref="chatMessages">
        <div v-if="aiResponse.length === 0" class="welcome-message">
          <div class="welcome-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
            </svg>
      </div>
          <h3>Welcome to AI Assistant</h3>
          <p>I'm here to help you with hosting, cPanel management, and technical support. How can I assist you today?</p>
    </div>

        <div v-for="(resp, index) in aiResponse" :key="index" class="message-bubble ai-message">
          <div class="message-avatar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" fill="currentColor"/>
            </svg>
          </div>
          <div class="message-content">
            <div class="message-text" v-html="formatMessage(resp)"></div>
            <div class="message-time">{{ formatTime(new Date()) }}</div>
          </div>
        </div>
      </div>

      <!-- Action Queue Sidebar -->
      <div class="action-queue" v-if="pending_functions.length > 0">
        <div class="queue-header">
          <h3>Pending Actions</h3>
          <span class="queue-count">{{ pending_functions.length }}</span>
        </div>
        <div class="queue-items">
        <div
          v-for="(func, index) in pending_functions"
          :key="index"
            class="queue-item"
          >
            <div class="queue-item-header">
              <div class="function-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
          </div>
              <div class="function-info">
                <div class="function-name">{{ func.functionName }}</div>
                <div class="function-description">{{ func.description }}</div>
              </div>
            </div>
            <div class="queue-actions">
              <button class="btn-reject" @click="aiReject(index)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              Reject
            </button>
              <button class="btn-proceed" @click="aiProceed(index)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              Proceed
            </button>
          </div>
        </div>
      </div>
      </div>
    </div>

    <!-- Chat Input -->
    <div class="chat-input-container">
      <div class="input-wrapper">
        <textarea
          v-model="userMessage"
          placeholder="Type your message here..."
          class="chat-input"
          @keydown.enter.prevent="handleEnterKey"
          rows="1"
          ref="chatInput"
        ></textarea>
        <button 
          class="send-button" 
          @click="sendMessage"
          :disabled="!userMessage.trim()"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
      </button>
        </div>
      <div class="chat-footer">
        <div class="token-info" v-if="tokens && Object.keys(tokens).length > 0">
          <span class="token-label">Tokens Used:</span>
          <span class="token-count">{{ formatTokens(tokens) }}</span>
        </div>
        <button class="back-button" @click="view = 'contacts'">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H5M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back to Contacts
          </button>
        </div>
    </div>

    <!-- Debug Info (Hidden by default) -->
    <div class="debug-info" v-if="showDebug">
      <h4>API Response/calls:</h4>
      <pre>{{ JSON.stringify(apiResponse, null, 2) }}</pre>
    </div>
  </div>

  <div v-else-if="view === 'session'">
    <div class="session-view">
      <h2>View Session</h2>
      <div class="input-group">
        <label for="session-id">Session ID:</label>
        <input type="text" id="session-id" v-model="id" placeholder="Enter session ID">
        <button @click="aiView()" class="btn-primary">Load Session</button>
      </div>
      <div v-if="messageHistory" class="message-history">
        <h3>Message History</h3>
        <pre>{{ messageHistory }}</pre>
      </div>
      <button @click="view = 'AI'" class="btn-secondary">Back to Chat</button>
    </div>
  </div>

  <div v-else-if="view === 'log'">
    <div class="log-view">
      <h2>History Log</h2>
      <div class="log-content">
        <pre>{{ historyLog }}</pre>
  </div>
      <button @click="view = 'AI'" class="btn-secondary">Back to Chat</button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      title: 'Contact Manager',
      subtitle: 'Advanced AI-Powered Support Assistant',
      contacts: [],
      view: 'contacts',
      selectedContact: {id: '', name: '', email: '', phone: ''},
      selectedInd: null,
      aiResponse: [],
      userMessage: '',
      tokens: {},
      apiResponse: [],
      messageHistory: '',
      historyLog: '',
      id: '',
      pending_functions: [],
      acknowledged_functions: [],
      showDebug: false,
      isLoading: false,
      connectionStatus: 'connected',
      errorMessage: '',
      suggestions: [],
      typingIndicator: false,
      messageValidation: {
        isValid: true,
        errors: []
      },
      chatStats: {
        totalMessages: 0,
        avgResponseTime: 0,
        lastActivity: null
      },
      retryCount: 0,
      maxRetries: 3
    }
  }, 

  mounted() {
    this.reset();
    this.loadDraftMessage();
    this.$watch('userMessage', this.saveDraftMessage);
    document.addEventListener('keydown', this.handleKeyboardShortcuts);
    this.checkConnectionStatus();
    setInterval(this.checkConnectionStatus, 30000);
  },

  watch: {
    view(newView) {
      if (newView === 'contacts') {
        this.reset();
      } else if (newView === 'AI') {
        this.$nextTick(() => {
          this.focusChatInput();
        });
      }
    },
    connectionStatus(newStatus) {
      if (newStatus === 'error') {
        this.showErrorMessage('Connection to AI service lost. Please check your connection.');
      }
    }
  },

  methods: {
    reset(){
      this.clearErrorState();
      this.showLoadingIndicator();
      
      fetch('http://localhost:8080/index.php/contacts',{
        method: 'GET'
      })
      .then(response => this.handleApiResponse(response))
        .then(data => {
          this.contacts = data;
        this.hideLoadingIndicator();
    })
      .catch(error => {
        this.handleApiError('Failed to load contacts', error);
      });
      
      this.selectedContact = { id: '', name: '', email: '', phone: '' }
      this.selectedInd = null
    },

    contactClick(contactInd){
      this.view = 'read'
      this.selectedContact = this.contacts[contactInd]
      this.selectedInd = contactInd
    },

    editedContact(){
      fetch('http://localhost:8080/index.php/contacts/edit',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      this.view = 'read'
    },

    newContact(){
      fetch('http://localhost:8080/index.php/contacts/new',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      this.view = 'contacts'
    },

    deleteContact(){
      fetch('http://localhost:8080/index.php/contacts/delete',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      alert("Contact was Deleted")
      this.view ='contacts'
    },

    handleEnterKey(event) {
      if (!event.shiftKey && this.isMessageValid()) {
        this.sendMessage();
      }
    },

    sendMessage() {
      if (!this.userMessage.trim() || this.isLoading) return;
      
      // Validate message before sending
      if (!this.validateMessage()) {
        return;
      }
      
      // Clear any existing errors and old API response data
      this.clearErrorState();
      this.apiResponse = [];
      
      // Add user message to chat with timestamp
      const timestamp = this.formatTime(new Date());
      const userMessageWithMeta = {
        type: 'user',
        content: this.userMessage,
        timestamp: timestamp
      };
      
      this.aiResponse.push(`You: ${this.userMessage}`);
      this.chatStats.totalMessages++;
      this.chatStats.lastActivity = new Date();
      
      // Show typing indicator
      this.showTypingIndicator();
      
      // Send to AI
      this.aiClick();
      
      // Clear input and resize
      this.userMessage = '';
      this.clearSuggestions();
      this.$nextTick(() => {
        if (this.$refs.chatInput) {
          this.$refs.chatInput.style.height = 'auto';
          this.$refs.chatInput.focus();
        }
      });
    },

    aiClick() {
      const startTime = Date.now();
      this.isLoading = true;
      this.retryCount = 0;
      
      // Clear any previous API response data to prevent reuse
      this.apiResponse = [];
      
      this.performAiRequest(startTime);
    },

    performAiRequest(startTime, attempt = 1) {
      console.log(`AI Request attempt ${attempt}:`, {
        message: this.userMessage,
        pendingFunctions: this.pending_functions,
        tokens: this.tokens,
        API_response: [...this.apiResponse]
      });

      fetch('http://localhost:8080/index.php/ai/chat', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          'message': this.userMessage, 
          'pendingFunctions': this.pending_functions, 
          'tokens': this.tokens,
        'API_response': [...this.apiResponse]
        })
      })
      .then(response => this.handleApiResponse(response))
        .then(data => {
        this.hideTypingIndicator();
        this.processAiResponse(data, startTime);
      })
      .catch(error => {
        this.hideTypingIndicator();
        if (attempt < this.maxRetries) {
          setTimeout(() => {
            this.performAiRequest(startTime, attempt + 1);
          }, 1000 * attempt); // Exponential backoff
        } else {
          this.handleApiError('Failed to get AI response after multiple attempts', error);
        }
      });
    },

    processAiResponse(data, startTime) {
      const responseTime = Date.now() - startTime;
      this.updateResponseTimeStats(responseTime);
      
      if (data === 'alert') {
        this.showErrorMessage("I couldn't handle the current request. Please try rephrasing or contact support.");
        this.resetChatState();
        return;
      }

      if (data.response && data.response !== 'No Response was Generated') {
              this.aiResponse.push(data.response);
            }
            
      // Handle pending functions
            if (data.pending_functions && data.pending_functions.length > 0) {
              this.pending_functions = data.pending_functions;
        this.generateSmartSuggestions(data.pending_functions);
            }
            
      // Handle API response data - Always display if present
      if (data.API_response && data.API_response.length > 0) {
              if (typeof data.API_response[0] === 'string') {
                this.aiResponse.push(data.API_response[0]);
              } else {
          this.aiResponse.push("📊 API Response:\n" + JSON.stringify(data.API_response[0], null, 2));
              }
            }
            
            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
      this.isLoading = false;
      this.connectionStatus = 'connected';
          
      // Auto-scroll and focus
            this.$nextTick(() => {
              this.scrollToBottom();
        this.focusChatInput();
            });
    },

    aiClear() {
      this.showLoadingIndicator();
      
      fetch('http://localhost:8080/index.php/ai/clear', {
        method: 'POST',
        credentials: 'include'
      })
      .then(response => this.handleApiResponse(response))
      .then(() => {
        this.resetChatState();
        this.hideLoadingIndicator();
        this.showSuccessMessage('Chat history cleared successfully');
      })
      .catch(error => {
        this.handleApiError('Failed to clear chat history', error);
      });
    },

    aiView() {
      if (!this.id.trim()) {
        this.showErrorMessage('Please enter a valid session ID');
        return;
      }

      this.showLoadingIndicator();
      
      fetch('http://localhost:8080/index.php/ai/session_view', {
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify(this.id)
      })
      .then(response => this.handleApiResponse(response))
        .then(data => {
          this.messageHistory = data;
        this.hideLoadingIndicator();
      })
      .catch(error => {
        this.handleApiError('Failed to load session history', error);
      });
    },

    aiLog() {
      this.showLoadingIndicator();
      
      fetch('http://localhost:8080/index.php/ai/history_log', {
        method: 'POST',
        credentials: 'include'
      })
      .then(response => this.handleApiResponse(response))
        .then(data => {
          this.historyLog = data;
      this.view = 'log';
        this.hideLoadingIndicator();
      })
      .catch(error => {
        this.handleApiError('Failed to load history log', error);
      });
    },

    aiProceed(index) {
      if (index < 0 || index >= this.pending_functions.length) {
        this.showErrorMessage('Invalid function selection');
        return;
      }

      this.showLoadingIndicator();
      const startTime = Date.now();
      
      let func = this.pending_functions[index];
      func.confirmation = 'proceed';
      this.acknowledged_functions.push(func);
      this.pending_functions.splice(index, 1); 

      fetch('http://localhost:8080/index.php/ai/proceed', {
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify(this.acknowledged_functions)
      })
      .then(response => this.handleApiResponse(response))
        .then(data => {
        this.processAiResponse(data, startTime);
        this.acknowledged_functions = [];
        this.userMessage = '';
        
        // Don't auto-call aiClick() to give user control
        this.hideLoadingIndicator();
        this.showSuccessMessage('Function executed successfully');
      })
      .catch(error => {
        this.handleApiError('Function execution failed', error);
        this.acknowledged_functions = [];
      });
    },

    aiReject(index) {
      if (index < 0 || index >= this.pending_functions.length) {
        this.showErrorMessage('Invalid function selection');
        return;
      }
      
      this.pending_functions.splice(index, 1);
      this.showSuccessMessage('Function request rejected');
    },

    // Enhanced utility methods
    validateMessage() {
      const message = this.userMessage.trim();
      this.messageValidation.errors = [];
      
      if (!message) {
        this.messageValidation.errors.push('Message cannot be empty');
      }
      
      if (message.length > 5000) {
        this.messageValidation.errors.push('Message is too long (max 5000 characters)');
            }
            
      // Check for potentially harmful content
      if (this.containsSuspiciousContent(message)) {
        this.messageValidation.errors.push('Message contains potentially harmful content');
      }
      
      this.messageValidation.isValid = this.messageValidation.errors.length === 0;
      
      if (!this.messageValidation.isValid) {
        this.showErrorMessage(this.messageValidation.errors.join(', '));
      }
      
      return this.messageValidation.isValid;
    },

    containsSuspiciousContent(message) {
      const suspiciousPatterns = [
        /<script/i,
        /javascript:/i,
        /vbscript:/i,
        /data:text\/html/i,
        /<iframe/i
      ];
      
      return suspiciousPatterns.some(pattern => pattern.test(message));
    },

    isMessageValid() {
      return this.userMessage.trim().length > 0 && 
             this.userMessage.length <= 5000 && 
             !this.containsSuspiciousContent(this.userMessage);
    },

    handleApiResponse(response) {
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    },

    handleApiError(message, error) {
      console.error('API Error:', error);
      this.connectionStatus = 'error';
      this.isLoading = false;
      this.hideTypingIndicator();
      this.hideLoadingIndicator();
      
      const errorMsg = error?.message || error || 'Unknown error occurred';
      this.showErrorMessage(`${message}: ${errorMsg}`);
    },

    showErrorMessage(message) {
      this.errorMessage = message;
      setTimeout(() => {
        this.clearErrorState();
      }, 5000);
    },

    showSuccessMessage(message) {
      // You could implement a success toast here
      console.log('Success:', message);
    },

    clearErrorState() {
      this.errorMessage = '';
      this.connectionStatus = 'connected';
    },

    showLoadingIndicator() {
      this.isLoading = true;
    },

    hideLoadingIndicator() {
      this.isLoading = false;
    },

    showTypingIndicator() {
      this.typingIndicator = true;
    },

    hideTypingIndicator() {
      this.typingIndicator = false;
    },

    resetChatState() {
      this.aiResponse = [];
      this.tokens = {};
      this.apiResponse = [];
      this.pending_functions = [];
          this.acknowledged_functions = [];
      this.clearSuggestions();
    },

    generateSmartSuggestions(pendingFunctions) {
      this.suggestions = [];
      
      if (pendingFunctions.length > 0) {
        this.suggestions.push('Execute pending functions');
        this.suggestions.push('Reject all pending functions');
      }
      
      // Add contextual suggestions based on recent activity
      if (this.apiResponse.length > 0) {
        this.suggestions.push('View detailed results');
        this.suggestions.push('Export current data');
      }
    },

    clearSuggestions() {
      this.suggestions = [];
    },

    updateResponseTimeStats(responseTime) {
      if (this.chatStats.avgResponseTime === 0) {
        this.chatStats.avgResponseTime = responseTime;
      } else {
        this.chatStats.avgResponseTime = 
          (this.chatStats.avgResponseTime + responseTime) / 2;
      }
    },

    formatTime(date) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },

    formatTokens(tokens) {
      if (typeof tokens === 'object' && tokens !== null) {
        return Object.entries(tokens)
          .map(([key, value]) => `${key}: ${value}`)
          .join(', ');
      }
      return tokens || 'N/A';
    },

    scrollToBottom() {
      if (this.$refs.chatMessages) {
        this.$refs.chatMessages.scrollTop = this.$refs.chatMessages.scrollHeight;
      }
    },

    focusChatInput() {
      if (this.$refs.chatInput) {
        this.$refs.chatInput.focus();
      }
    },

    formatMessage(message) {
      if (typeof message === 'object' && message !== null) {
        return '<pre style="background: #f1f5f9; padding: 12px; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; line-height: 1.4;">' + 
               JSON.stringify(message, null, 2) + 
               '</pre>';
      }
      
      let content = String(message);
      
      // Check if this is a formatted API response
      if (content.includes('**') && content.includes('═══')) {
        return this.createDashboardCard(content);
      }
      
      // For simple messages, apply basic formatting
      content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      content = content.replace(/\n/g, '<br>');
      
      return `<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #374151;">${content}</div>`;
    },

    createDashboardCard(content) {
      // Extract the main title (e.g., "📧 **Email Forwarders**")
      const titleMatch = content.match(/^([📧🖥️👤🧾📦🌐].+?\*\*(.+?)\*\*)/m);
      const title = titleMatch ? titleMatch[1].replace(/\*\*/g, '') : 'Dashboard';
      
      let html = `
        <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px; padding: 16px; margin: 12px 0; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border: 1px solid rgba(226, 232, 240, 0.8);">
          <div style="background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
            <h3 style="margin: 0 0 12px 0; font-size: 1.1rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px;">
              ${title} Dashboard
            </h3>
      `;
        
      // Create info cards section
      html += this.createInfoCards(content);
      
      // Create details section if it has items
      if (content.includes('🔹')) {
        html += this.createDetailsSection(content);
      }
      
      // Handle empty states
      if (content.includes('📭')) {
        html += this.createEmptyState(content);
      }
      
      html += `
          </div>
        </div>
      `;
      
      return html;
    },

    createInfoCards(content) {
      let cardsHtml = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 200px)); gap: 8px; margin-bottom: 16px; justify-content: start;">';
      
      // Extract context information - Updated patterns for client info
      const patterns = [
        { regex: /🆔\s+\*\*Client ID:\*\*\s+(.+)/m, icon: '🆔', label: 'Client ID', color: '#8b5cf6' },
        { regex: /👤\s+\*\*Name:\*\*\s+(.+)/m, icon: '👤', label: 'Name', color: '#f59e0b' },
        { regex: /🏢\s+\*\*Company:\*\*\s+(.+)/m, icon: '🏢', label: 'Company', color: '#6b7280' },
        { regex: /📧\s+\*\*Email:\*\*\s+(.+)/m, icon: '📧', label: 'Email', color: '#3b82f6' },
        { regex: /📞\s+\*\*Phone:\*\*\s+(.+)/m, icon: '📞', label: 'Phone', color: '#10b981' },
        { regex: /✅\s+\*\*Status:\*\*\s+(.+)/m, icon: '✅', label: 'Status', color: '#22c55e' },
        // Original patterns for other types
        { regex: /👤\s+\*\*cPanel User:\*\*\s+(.+)/m, icon: '👤', label: 'cPanel User', color: '#f59e0b' },
        { regex: /🌐\s+\*\*Domain:\*\*\s+(.+)/m, icon: '🌐', label: 'Domain', color: '#3b82f6' },
        { regex: /📊\s+\*\*Total.*?:\*\*\s+(.+)/m, icon: '📊', label: 'Total Items', color: '#10b981' },
        { regex: /🖥️\s+\*\*Server:\*\*\s+(.+)/m, icon: '🖥️', label: 'Server', color: '#6b7280' }
      ];
      
      patterns.forEach(pattern => {
        const match = content.match(pattern.regex);
        if (match) {
          cardsHtml += `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
              <div style="font-size: 1.5rem; margin-bottom: 6px; color: ${pattern.color};">${pattern.icon}</div>
              <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 3px; font-weight: 500;">${pattern.label}</div>
              <div style="font-size: 0.95rem; font-weight: 600; color: #1f2937; line-height: 1.2;">${match[1]}</div>
            </div>
          `;
        }
      });
      
      cardsHtml += '</div>';
      return cardsHtml;
    },

    createDetailsSection(content) {
      let detailsHtml = `
        <div style="margin-top: 16px;">
          <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 1px solid #e5e7eb;">
            <span style="font-size: 1rem;">📋</span>
            <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: #374151;">Details</h4>
          </div>
      `;
      
      // Extract each item (🔹 **Item #1**) - updated to handle client sections
      const itemMatches = content.match(/🔹\s+\*\*(.+?)\*\*([\s\S]*?)(?=🔹|$)/g);
      
      if (itemMatches) {
        itemMatches.forEach((item, index) => {
          const itemTitleMatch = item.match(/🔹\s+\*\*(.+?)\*\*/);
          const itemTitle = itemTitleMatch ? itemTitleMatch[1] : `Section ${index + 1}`;
          
          // Extract bullet points for this item - updated regex
          const bulletPoints = item.match(/\s+•\s+\*\*(.+?)\*\*\s+(.+?)(?=\n|$)/g);
          
          let itemHtml = `
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 8px;">
              <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                <span style="color: #3b82f6; font-size: 1rem;">🔹</span>
                <span style="font-weight: 600; color: #1e293b; font-size: 0.95rem;">${itemTitle}</span>
                <span style="background: #22c55e; color: white; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem; font-weight: 500; margin-left: auto;">active</span>
              </div>
          `;
          
          if (bulletPoints) {
            itemHtml += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">';
            
            bulletPoints.forEach(bullet => {
              const bulletMatch = bullet.match(/\s+•\s+\*\*(.+?)\*\*\s+(.+?)(?=\n|$)/);
              if (bulletMatch) {
                const label = bulletMatch[1];
                let value = bulletMatch[2].trim();
                
                // Clean up value - remove extra whitespace and line breaks
                value = value.replace(/\s+/g, ' ').trim();
                
                itemHtml += `
                  <div>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 3px; font-weight: 500;">${label}:</div>
                    <div style="font-size: 0.9rem; font-weight: 600; color: #3b82f6; word-break: break-all; line-height: 1.2;">${value}</div>
                  </div>
                `;
      }
            });
            
            itemHtml += '</div>';
          }
          
          // Handle special sections like Last Login that don't have bullet format
          if (itemTitle === 'Last Login') {
            const loginInfo = item.replace(/🔹\s+\*\*Last Login\*\*/, '').trim();
            const loginLines = loginInfo.split(/\n/).filter(line => line.trim());
            
            itemHtml += '<div style="margin-top: 8px;">';
            loginLines.forEach(line => {
              const cleanLine = line.replace(/•/g, '').trim();
              if (cleanLine) {
                itemHtml += `<div style="margin: 3px 0; color: #4b5563; font-size: 0.85rem;">${cleanLine}</div>`;
              }
            });
            itemHtml += '</div>';
          }
          
          itemHtml += '</div>';
          detailsHtml += itemHtml;
        });
      }
      
      detailsHtml += '</div>';
      return detailsHtml;
    },

    createEmptyState(content) {
      const emptyMatch = content.match(/📭\s+\*\*(.+?)\*\*/);
      const emptyMessage = emptyMatch ? emptyMatch[1] : 'No items found';
      
      return `
        <div style="text-align: center; padding: 40px 20px; background: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db; margin: 20px 0;">
          <div style="font-size: 3rem; margin-bottom: 12px; opacity: 0.6;">📭</div>
          <div style="font-size: 1.1rem; font-weight: 600; color: #6b7280; margin-bottom: 8px;">${emptyMessage}</div>
          <div style="font-size: 0.875rem; color: #9ca3af;">No data available to display</div>
        </div>
      `;
    },

    // Auto-save draft message
    saveDraftMessage() {
      if (this.userMessage.trim()) {
        localStorage.setItem('chatDraft', this.userMessage);
      } else {
        localStorage.removeItem('chatDraft');
      }
    },

    loadDraftMessage() {
      const draft = localStorage.getItem('chatDraft');
      if (draft) {
        this.userMessage = draft;
      }
    },

    // Keyboard shortcuts
    handleKeyboardShortcuts(event) {
      // Ctrl/Cmd + Enter to send message
      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        this.sendMessage();
      }
      
      // Ctrl/Cmd + K to clear chat
      if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        this.aiClear();
      }
      
      // Escape to clear input
      if (event.key === 'Escape') {
        this.userMessage = '';
        this.clearSuggestions();
      }
    },

    checkConnectionStatus() {
      fetch('http://localhost:8080/index.php/ai', {
        method: 'HEAD',
        credentials: 'include'
      })
      .then(response => {
        this.connectionStatus = response.ok ? 'connected' : 'error';
      })
      .catch(() => {
        this.connectionStatus = 'error';
      });
    }
  }
}
</script>

<style scoped>
/* AI Chat Container */
.ai-chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  min-height: 600px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Header */
.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ai-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.header-info h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
}

.status-indicator {
  font-size: 0.875rem;
  color: #10b981;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.icon-btn {
  min-width: 80px;
  height: 40px;
  border: none;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #4a5568;
  padding: 4px 8px;
  gap: 2px;
}

.icon-btn:hover {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.icon-text {
  font-size: 16px;
  line-height: 1;
}

.btn-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #4a5568;
}

/* Main Chat Area */
.chat-main {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.chat-messages {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

/* Welcome Message */
.welcome-message {
  text-align: center;
  padding: 60px 20px;
  color: #4a5568;
}

.welcome-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  color: white;
}

.welcome-message h3 {
  margin: 0 0 12px;
  font-size: 1.5rem;
  font-weight: 600;
}

.welcome-message p {
  margin: 0;
  font-size: 1rem;
  opacity: 0.8;
  max-width: 400px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Message Bubbles */
.message-bubble {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  animation: slideIn 0.3s ease-out;
}

.ai-message {
  align-items: flex-start;
}

.message-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.message-content {
  flex: 1;
  max-width: 70%;
}

.message-text {
  background: #f7fafc;
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #2d3748;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  word-wrap: break-word;
  white-space: pre-wrap;
}

.message-text strong {
  font-weight: 600;
}

.message-text br {
  margin: 4px 0;
}

.message-time {
  font-size: 0.75rem;
  color: #a0aec0;
  margin-top: 4px;
  padding-left: 16px;
}

/* Action Queue */
.action-queue {
  width: 320px;
  background: rgba(255, 255, 255, 0.98);
  border-left: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  flex-direction: column;
}

.queue-header {
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.queue-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
}

.queue-count {
  background: #667eea;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.queue-items {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.queue-item {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 12px;
  transition: all 0.2s ease;
}

.queue-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.queue-item-header {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.function-icon {
  width: 32px;
  height: 32px;
  background: #667eea;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.function-info {
  flex: 1;
}

.function-name {
  font-weight: 600;
  color: #2d3748;
  font-size: 0.9rem;
  margin-bottom: 4px;
}

.function-description {
  font-size: 0.8rem;
  color: #718096;
  line-height: 1.4;
}

.queue-actions {
  display: flex;
  gap: 8px;
}

.btn-reject, .btn-proceed {
  flex: 1;
  padding: 8px 12px;
  border: none;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.btn-reject {
  background: #fed7d7;
  color: #c53030;
}

.btn-reject:hover {
  background: #feb2b2;
}

.btn-proceed {
  background: #c6f6d5;
  color: #2f855a;
}

.btn-proceed:hover {
  background: #9ae6b4;
}

/* Chat Input */
.chat-input-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding: 20px 24px;
}

.input-wrapper {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  margin-bottom: 12px;
}

.chat-input {
  flex: 1;
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  padding: 12px 20px;
  font-size: 1rem;
  resize: none;
  outline: none;
  transition: all 0.2s ease;
  background: white;
  color: #2d3748;
  min-height: 48px;
  max-height: 120px;
}

.chat-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.send-button {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.send-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chat-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.token-info {
  font-size: 0.875rem;
  color: #718096;
}

.token-label {
  margin-right: 8px;
}

.token-count {
  font-weight: 600;
  color: #4a5568;
}

.back-button {
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 8px 16px;
  font-size: 0.875rem;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.back-button:hover {
  background: white;
  transform: translateY(-1px);
}

/* Session and Log Views */
.session-view, .log-view {
  padding: 40px;
  max-width: 800px;
  margin: 0 auto;
}

.session-view h2, .log-view h2 {
  margin-bottom: 24px;
  color: #2d3748;
}

.input-group {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 24px;
}

.input-group label {
  font-weight: 500;
  color: #4a5568;
}

.input-group input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a67d8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.message-history, .log-content {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.message-history pre, .log-content pre {
  white-space: pre-wrap;
  word-wrap: break-word;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #2d3748;
  margin: 0;
}

/* Debug Info */
.debug-info {
  background: #1a202c;
  color: #e2e8f0;
  padding: 20px;
  margin-top: 20px;
  border-radius: 8px;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.875rem;
}

.debug-info h4 {
  margin-top: 0;
  color: #63b3ed;
}

.debug-info pre {
  margin: 0;
  white-space: pre-wrap;
  word-wrap: break-word;
}

/* Animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .ai-chat-container {
    height: 100vh;
    border-radius: 0;
  }
  
  .chat-main {
    flex-direction: column;
  }
  
  .action-queue {
    width: 100%;
    max-height: 200px;
    border-left: none;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
  }
  
  .message-content {
    max-width: 85%;
  }
}

/* Contact Management Styles */
.contacts-view, .contact-detail-view, .form-view {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
}

.contacts-view h1 {
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.subtitle {
  font-size: 2rem !important;
  color: #4a5568;
  margin-bottom: 2rem;
  font-weight: 500;
}

.contacts-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}

.contact-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.contact-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(102, 126, 234, 0.3);
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-top: 2rem;
}

.primary-button {
  background: #48bb78;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.primary-button:hover {
  background: #38a169;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(72, 187, 120, 0.3);
}

.secondary-button {
  background: #e2e8f0;
  color: #4a5568;
  border: 2px solid #cbd5e0;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.secondary-button:hover {
  background: #cbd5e0;
  border-color: #a0aec0;
  transform: translateY(-1px);
}

.danger-button {
  background: #f56565;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.danger-button:hover {
  background: #e53e3e;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(245, 101, 101, 0.3);
}

.ai-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.ai-button:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.contact-detail-view {
  text-align: center;
}

.contact-detail-view h1 {
  color: #2d3748;
  margin-bottom: 1.5rem;
}

.contact-info {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  text-align: left;
}

.contact-info p {
  margin: 0.5rem 0;
  font-size: 1.1rem;
  color: #4a5568;
}

.contact-info strong {
  color: #2d3748;
}

.form-view {
  text-align: left;
}

.form-view h1 {
  text-align: center;
  color: #2d3748;
  margin-bottom: 2rem;
}

.contact-form {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2d3748;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .contacts-view, .contact-detail-view, .form-view {
    padding: 1rem;
  }
  
  .action-buttons, .form-actions {
    flex-direction: column;
  }
  
  .contact-button, .primary-button, .secondary-button, .danger-button, .ai-button {
    width: 100%;
  }
}
</style>
