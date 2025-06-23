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
            <!-- Check if this is an API response data message -->
            <div v-if="isApiResponseMessage(resp, index)" class="api-response-container">
              <!-- Toggle Button -->
              <div class="api-response-header">
                <h4 style="margin: 0; color: #374151; font-size: 0.9rem; font-weight: 600;">📊 API Response Data</h4>
                <button 
                  @click="toggleApiResponseView(index)" 
                  class="view-toggle-btn"
                  :title="getApiResponseViewMode(index) === 'formatted' ? 'Switch to Raw Data' : 'Switch to Formatted View'"
                >
                  <svg v-if="getApiResponseViewMode(index) === 'formatted'" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 4V20M20 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  {{ getApiResponseViewMode(index) === 'formatted' ? 'Raw' : 'Card' }}
                </button>
              </div>
              <!-- Content Display -->
              <div v-if="getApiResponseViewMode(index) === 'formatted'" class="formatted-view">
                <div class="message-text" v-html="getFormattedApiData(index)"></div>
              </div>
              <div v-else class="raw-view">
                <pre class="raw-data-display">{{ getRawApiData(index) }}</pre>
              </div>
            </div>
            <!-- Regular message display -->
            <div v-else>
              <div class="message-text" v-html="formatMessage(resp)"></div>
            </div>
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
      apiResponseViewModes: {}, // Track view mode (formatted/raw) for each API response
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
            
      // Handle API response data - Store both formatted and raw data
      if (data.API_response && data.API_response.length > 0) {
        // Store the raw API response data
        this.apiResponse = data.API_response;
        
        // Add the API response to the chat with proper indexing
        const apiResponseIndex = this.aiResponse.length;
        
        if (typeof data.API_response[0] === 'string') {
          this.aiResponse.push(data.API_response[0]);
        } else {
          // Create a formatted display for the API response
          const formattedDisplay = "📊 API Response:\n" + JSON.stringify(data.API_response[0], null, 2);
          
          // Add the API response data as an object for special handling
          this.aiResponse.push({
            type: 'api_response',
            data: data.API_response[0],
            formattedMessage: this.formatMessage(formattedDisplay)
          });
          
          // Set default view mode to formatted for this API response
          this.apiResponseViewModes[apiResponseIndex] = 'formatted';
        }
      }
            
            this.tokens = data.tokens_used;
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
      this.apiResponseViewModes = {}; // Clear view modes
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
      
      // Check if this is a server monitoring response (contains server-related patterns)
      if (content.includes('🖥️') || content.includes('📊') || content.includes('💾') || content.includes('🔧')) {
        return this.createServerMonitoringLayout(content);
      }
      
      // Check if this is a formatted API response with the old card system
      if (content.includes('**') && content.includes('═══')) {
        return this.createDashboardCard(content);
      }
      
      // For simple messages, apply basic formatting
      content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      content = content.replace(/\n/g, '<br>');
      
      return `<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #374151;">${content}</div>`;
    },

    createServerMonitoringLayout(content) {
      // Extract title from the content
      let title = 'Server Information';
      if (content.includes('🖥️')) title = '🖥️ Server Status';
      else if (content.includes('📊')) title = '📊 Server Load';
      else if (content.includes('💾')) title = '💾 Disk Usage';
      else if (content.includes('🔧')) title = '🔧 Server Services';

      // Split content into sections
      const sections = content.split('\n\n').filter(section => section.trim());
      
      let html = `
        <div class="server-monitor-container" style="
          background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
          border-radius: 12px;
          border: 1px solid #e5e7eb;
          margin: 16px 0;
          overflow: hidden;
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        ">
          <div class="server-header" style="
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 1.1rem;
          ">
            ${title}
          </div>
          <div class="server-content" style="
            padding: 20px;
            background: white;
          ">
      `;

      sections.forEach((section, index) => {
        if (section.trim() && !section.includes('═══')) {
          // Clean up the section content
          let cleanSection = section
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/• /g, '&nbsp;&nbsp;• ')
            .replace(/\n/g, '<br>');

          // Add section styling
          html += `
            <div class="server-section" style="
              background: #f9fafb;
              border-radius: 8px;
              padding: 16px;
              margin-bottom: 12px;
              border-left: 4px solid #4338ca;
            ">
              <div style="
                line-height: 1.6;
                color: #374151;
                font-size: 0.95rem;
              ">
                ${cleanSection}
              </div>
            </div>
          `;
        }
      });

      html += `
          </div>
        </div>
      `;

      return html;
    },

    createDashboardCard(content) {
      // Extract the main title (e.g., "📧 **Email Forwarders**")
      const titleMatch = content.match(/^([📧🖥️👤🧾📦🌐].+?\*\*(.+?)\*\*)/m);
      const title = titleMatch ? titleMatch[1].replace(/\*\*/g, '') : 'Dashboard';
      
      let html = `
        <div class="dashboard-card animate__animated animate__fadeInUp" style="
          background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); 
          border-radius: 16px; 
          padding: 20px; 
          margin: 16px 0; 
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); 
          border: 1px solid rgba(226, 232, 240, 0.8); 
          transition: all 0.3s ease; 
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        ">
          <div class="dashboard-inner" style="
            background: white; 
            border-radius: 12px; 
            padding: 0; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06); 
            overflow: hidden; 
            transition: all 0.3s ease;
          ">
            <!-- Header -->
            <div class="dashboard-header" style="
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
              padding: 16px 20px; 
              border-bottom: 1px solid #e5e7eb; 
              position: relative; 
              overflow: hidden;
            ">
              <div class="header-pattern" style="
                position: absolute; 
                top: 0; 
                left: 0; 
                right: 0; 
                bottom: 0; 
                background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 2px, transparent 2px), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 1px, transparent 1px); 
                background-size: 30px 30px, 20px 20px; 
                opacity: 0.3;
              "></div>
              <h3 style="
                margin: 0; 
                font-size: 1.25rem; 
                font-weight: 600; 
                color: white; 
                display: flex; 
                align-items: center; 
                gap: 8px; 
                position: relative; 
                z-index: 1;
              ">
                ${title}
              </h3>
            </div>
            <!-- Content -->
            <div style="padding: 20px;">
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
        </div>
      `;
      
      return html;
    },

    createInfoCards(content) {
      let cardsHtml = '<div class="info-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 20px;">';
      
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
        { regex: /🖥️\s+\*\*Server:\*\*\s+(.+)/m, icon: '🖥️', label: 'Server', color: '#6b7280' },
        // Fixed server monitoring patterns to match the actual response format
        { regex: /•\s+\*\*1 minute:\*\*\s+([0-9.]+\s+🟢[^•]*)/m, icon: '📈', label: '1 Min Load', color: '#22c55e' },
        { regex: /•\s+\*\*5 minutes:\*\*\s+([0-9.]+\s+🟢[^•]*)/m, icon: '📊', label: '5 Min Load', color: '#3b82f6' },
        { regex: /•\s+\*\*15 minutes:\*\*\s+([0-9.]+\s+🟢[^•]*)/m, icon: '📉', label: '15 Min Load', color: '#8b5cf6' },
        { regex: /💡\s+\*\*Performance Status:\*\*\s+(.+)/m, icon: '💡', label: 'Performance', color: '#10b981' },
        { regex: /⏰\s+\*\*Timestamp:\*\*\s+(.+)/m, icon: '⏰', label: 'Timestamp', color: '#6b7280' }
      ];
      
      let cardIndex = 0;
      patterns.forEach(pattern => {
        const match = content.match(pattern.regex);
        if (match) {
          cardIndex++;
          let valueContent = match[1];
          
          // Special handling for status to show color-coded badges
          if (pattern.label === 'Status') {
            const isActive = valueContent.includes('Active');
            valueContent = `
              <span style="
                display: inline-flex; 
                align-items: center; 
                gap: 4px; 
                background: ${isActive ? '#dcfce7' : '#fef2f2'}; 
                color: ${isActive ? '#15803d' : '#dc2626'}; 
                padding: 4px 8px; 
                border-radius: 12px; 
                font-size: 0.8rem; 
                font-weight: 600;
              ">
                ${isActive ? '✓' : '✗'} ${valueContent.replace(/[✅❌]/g, '').trim()}
              </span>
            `;
          }
          
          cardsHtml += `
            <div class="info-card animate__animated animate__fadeInUp" style="
              background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
              border: 1px solid #e5e7eb; 
              border-radius: 10px; 
              padding: 16px; 
              text-align: left; 
              transition: all 0.3s ease; 
              border-left: 4px solid ${pattern.color}; 
              animation-delay: ${cardIndex * 0.1}s; 
              position: relative; 
              overflow: hidden;
              min-height: 80px;
            ">
              <div class="card-decoration" style="
                position: absolute; 
                top: -10px; 
                right: -10px; 
                width: 40px; 
                height: 40px; 
                background: radial-gradient(circle, ${pattern.color}20 0%, transparent 70%); 
                border-radius: 50%;
              "></div>
              <div style="
                display: flex; 
                align-items: center; 
                gap: 8px; 
                margin-bottom: 8px; 
                position: relative; 
                z-index: 1;
              ">
                <span class="card-icon" style="
                  font-size: 1.25rem; 
                  color: ${pattern.color}; 
                  transition: all 0.3s ease;
                ">${pattern.icon}</span>
                <span style="
                  font-size: 0.8rem; 
                  color: #6b7280; 
                  font-weight: 600; 
                  text-transform: uppercase; 
                  letter-spacing: 0.5px;
                ">${pattern.label}</span>
              </div>
              <div style="
                font-size: 1rem; 
                font-weight: 600; 
                color: #1f2937; 
                line-height: 1.3; 
                word-break: break-word; 
                position: relative; 
                z-index: 1;
              ">${valueContent}</div>
            </div>
          `;
        }
      });
      
      cardsHtml += '</div>';
      return cardsHtml;
    },

    createDetailsSection(content) {
      let detailsHtml = `
        <div class="details-section animate__animated animate__fadeInUp" style="margin-top: 24px; animation-delay: 0.5s;">
          <div style="
            display: flex; 
            align-items: center; 
            gap: 8px; 
            margin-bottom: 16px; 
            padding: 12px 16px; 
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
            border-radius: 8px; 
            border-left: 4px solid #667eea; 
            position: relative; 
            overflow: hidden;
          ">
            <div class="details-pattern" style="
              position: absolute; 
              top: 0; 
              left: 0; 
              right: 0; 
              bottom: 0; 
              background-image: radial-gradient(circle at 10% 20%, rgba(102, 126, 234, 0.1) 1px, transparent 1px), radial-gradient(circle at 90% 80%, rgba(102, 126, 234, 0.1) 1px, transparent 1px); 
              background-size: 20px 20px, 15px 15px; 
              opacity: 0.5;
            "></div>
            <span style="font-size: 1.1rem; color: #667eea; position: relative; z-index: 1;">📋</span>
            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #374151; position: relative; z-index: 1;">Additional Details</h4>
          </div>
      `;
      
      // Extract each item (🔹 **Item #1**) - updated to handle client sections
      const itemMatches = content.match(/🔹\s+\*\*(.+?)\*\*([\s\S]*?)(?=🔹|$)/g);
      
      if (itemMatches) {
        detailsHtml += '<div style="display: grid; gap: 16px;">';
        
        itemMatches.forEach((item, index) => {
          const itemTitleMatch = item.match(/🔹\s+\*\*(.+?)\*\*/);
          const itemTitle = itemTitleMatch ? itemTitleMatch[1] : `Section ${index + 1}`;
          
          let itemHtml = `
            <div class="detail-item animate__animated animate__fadeInLeft" style="
              background: #ffffff; 
              border: 1px solid #e5e7eb; 
              border-radius: 8px; 
              overflow: hidden; 
              transition: all 0.3s ease; 
              animation-delay: ${(index + 1) * 0.2}s;
            ">
              <div style="
                background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); 
                padding: 12px 16px; 
                border-bottom: 1px solid #e5e7eb; 
                position: relative;
              ">
                <div class="item-decoration" style="
                  position: absolute; 
                  top: 0; 
                  right: 0; 
                  width: 60px; 
                  height: 100%; 
                  background: linear-gradient(90deg, transparent 0%, rgba(102, 126, 234, 0.05) 100%);
                "></div>
                <h5 style="
                  margin: 0; 
                  font-size: 0.9rem; 
                  font-weight: 600; 
                  color: #374151; 
                  display: flex; 
                  align-items: center; 
                  gap: 6px; 
                  position: relative; 
                  z-index: 1;
                ">
                  <span style="color: #667eea;">🔹</span>
                  ${itemTitle}
                </h5>
              </div>
              <div style="padding: 16px;">
          `;
          
          // Extract bullet points for this item
          const bulletPoints = item.match(/\s+•\s+\*\*(.+?)\*\*\s+(.+?)(?=\n|$)/g);
          
          if (bulletPoints) {
            itemHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">';
            
            bulletPoints.forEach((bullet, bulletIndex) => {
              const bulletMatch = bullet.match(/\s+•\s+\*\*(.+?)\*\*\s+(.+?)(?=\n|$)/);
              if (bulletMatch) {
                const label = bulletMatch[1];
                let value = bulletMatch[2].trim();
                
                // Clean up value - remove extra whitespace and line breaks
                value = value.replace(/\s+/g, ' ').trim();
                
                // Special formatting for certain fields
                let valueStyle = 'font-size: 0.9rem; font-weight: 600; color: #1f2937; word-break: break-word; line-height: 1.3; transition: all 0.3s ease;';
                let bgColor = '#f8fafc';
                let decorationColor = '#667eea';
                
                if (label.includes('Email') || label.includes('@')) {
                  valueStyle += ' color: #3b82f6;';
                  bgColor = '#eff6ff';
                  decorationColor = '#3b82f6';
                } else if (label.includes('Currency') || label.includes('Balance') || label.includes('$')) {
                  valueStyle += ' color: #059669; font-family: monospace;';
                  bgColor = '#ecfdf5';
                  decorationColor = '#059669';
                }
                
                itemHtml += `
                  <div class="bullet-item" style="
                    padding: 12px; 
                    background: ${bgColor}; 
                    border-radius: 6px; 
                    border: 1px solid #e5e7eb; 
                    transition: all 0.3s ease; 
                    position: relative; 
                    overflow: hidden;
                  ">
                    <div style="
                      position: absolute; 
                      top: 0; 
                      right: 0; 
                      width: 30px; 
                      height: 30px; 
                      background: radial-gradient(circle, ${decorationColor}10 0%, transparent 70%); 
                      border-radius: 50%;
                    "></div>
                    <div style="
                      font-size: 0.75rem; 
                      color: #6b7280; 
                      margin-bottom: 4px; 
                      font-weight: 600; 
                      text-transform: uppercase; 
                      letter-spacing: 0.3px; 
                      position: relative; 
                      z-index: 1;
                    ">${label}</div>
                    <div style="${valueStyle} position: relative; z-index: 1;">${value}</div>
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
            
            itemHtml += '<div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb;">';
            loginLines.forEach((line, lineIndex) => {
              const cleanLine = line.replace(/•/g, '').trim();
              if (cleanLine) {
                itemHtml += `<div style="margin: 8px 0; color: #4b5563; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: ${lineIndex < loginLines.length - 1 ? '1px solid #e5e7eb' : 'none'};"><span style="color: #667eea; font-weight: bold;">•</span><span style="flex: 1;">${cleanLine}</span></div>`;
              }
            });
            itemHtml += '</div>';
          }
          
          itemHtml += '</div></div>';
          detailsHtml += itemHtml;
        });
        
        detailsHtml += '</div>';
      }
      
      detailsHtml += '</div>';
      return detailsHtml;
    },

    createEmptyState(content) {
      const emptyMatch = content.match(/📭\s+\*\*(.+?)\*\*/);
      const emptyMessage = emptyMatch ? emptyMatch[1] : 'No items found';
      
      return `
        <div style="
          text-align: center; 
          padding: 40px 20px; 
          background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
          border-radius: 12px; 
          border: 2px dashed #d1d5db; 
          margin: 20px 0;
        ">
          <div style="
            font-size: 3rem; 
            margin-bottom: 16px; 
            opacity: 0.6;
          ">📭</div>
          <div style="
            font-size: 1.125rem; 
            font-weight: 600; 
            color: #6b7280; 
            margin-bottom: 8px;
          ">${emptyMessage}</div>
          <div style="
            font-size: 0.875rem; 
            color: #9ca3af;
          ">No data available to display</div>
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
    },

    isApiResponseMessage(resp, index) {
      return typeof resp === 'object' && resp !== null && resp.type === 'api_response';
    },

    getApiResponseViewMode(index) {
      return this.apiResponseViewModes[index] || 'formatted';
    },

    toggleApiResponseView(index) {
      this.apiResponseViewModes[index] = this.getApiResponseViewMode(index) === 'formatted' ? 'raw' : 'formatted';
    },

    getRawApiData(index) {
      // Get the corresponding raw API response data
      const apiResponseMessage = this.aiResponse[index];
      if (apiResponseMessage && apiResponseMessage.type === 'api_response') {
        return JSON.stringify(apiResponseMessage.data, null, 2);
      }
      return 'No raw data available';
    },

    getFormattedApiData(index) {
      const apiResponseMessage = this.aiResponse[index];
      if (apiResponseMessage && apiResponseMessage.type === 'api_response') {
        return this.createBeautifulApiCard(apiResponseMessage.data);
      }
      return '';
    },

    createBeautifulApiCard(data) {
      // Enhanced card creation for API response data with stunning visuals
      const cardHtml = `
        <div class="api-data-card-enhanced animate__animated animate__fadeInUp">
          <div class="card-header-enhanced">
            <div class="header-background-pattern"></div>
            <div class="header-content-enhanced">
              <div class="status-indicator-enhanced ${data.status === 'success' ? 'success' : 'error'}">
                <div class="status-pulse"></div>
                ${data.status === 'success' ? '✅' : '❌'}
              </div>
              <div class="header-text-enhanced">
                <h3 class="card-title-enhanced">${this.formatCardTitle(data)}</h3>
                <p class="card-subtitle-enhanced">${data.source || 'API Response'}</p>
              </div>
              <div class="header-decoration"></div>
            </div>
            <div class="timestamp-badge-enhanced">
              <div class="timestamp-icon">⏰</div>
              <div class="timestamp-text">${data.timestamp || new Date().toLocaleString()}</div>
            </div>
          </div>
          
          <div class="card-body-enhanced">
            ${this.createEnhancedDataVisualization(data)}
          </div>
          
          ${data.ai_context ? `
          <div class="card-footer-enhanced">
            <div class="ai-context-enhanced">
              <div class="context-icon-enhanced">
                <div class="context-pulse"></div>
                🤖
              </div>
              <div class="context-content-enhanced">
                <div class="context-title">AI Analysis</div>
                <div class="context-analysis">${data.ai_context.analysis || 'Data processed successfully'}</div>
                ${data.ai_context.scope ? `<div class="context-scope">Scope: ${data.ai_context.scope}</div>` : ''}
              </div>
            </div>
          </div>
          ` : ''}
        </div>
      `;
      
      return cardHtml;
    },

    createEnhancedDataVisualization(data) {
      // Handle different types of data structures with enhanced visuals
      if (data.data && data.data.metadata && data.data.metadata.command === 'systemloadavg') {
        return this.createEnhancedLoadAverageVisualization(data.data);
      }
      
      // Enhanced default card layout for other data types
      return this.createEnhancedDefaultDataCards(data);
    },

    createEnhancedLoadAverageVisualization(systemData) {
      const loadData = systemData.data;
      const metadata = systemData.metadata;
      
      return `
        <div class="enhanced-load-average-section">
          <div class="section-header-enhanced">
            <div class="section-icon-container">
              <div class="section-icon">🖥️</div>
              <div class="section-icon-glow"></div>
            </div>
            <div class="section-title-enhanced">Server Load Metrics</div>
            <div class="section-divider"></div>
          </div>
          
          <div class="enhanced-load-grid">
            <div class="enhanced-metric-card one-minute-card">
              <div class="metric-card-glow"></div>
              <div class="metric-icon-container">
                <div class="metric-icon-enhanced">📈</div>
                <div class="metric-icon-ring"></div>
              </div>
              <div class="metric-header-enhanced">
                <div class="metric-label-enhanced">1 Minute</div>
                <div class="metric-trend up"></div>
              </div>
              <div class="metric-value-container">
                <div class="metric-value-enhanced">${loadData.one}</div>
                <div class="metric-unit">avg</div>
              </div>
              <div class="metric-status-enhanced ${this.getLoadStatus(parseFloat(loadData.one))}">${this.getLoadStatusText(parseFloat(loadData.one))}</div>
              <div class="metric-progress-bar">
                <div class="progress-fill" style="width: ${Math.min(parseFloat(loadData.one) * 50, 100)}%; background: ${this.getLoadStatusColor(parseFloat(loadData.one))}"></div>
              </div>
            </div>
            
            <div class="enhanced-metric-card five-minute-card">
              <div class="metric-card-glow"></div>
              <div class="metric-icon-container">
                <div class="metric-icon-enhanced">📊</div>
                <div class="metric-icon-ring"></div>
              </div>
              <div class="metric-header-enhanced">
                <div class="metric-label-enhanced">5 Minutes</div>
                <div class="metric-trend stable"></div>
              </div>
              <div class="metric-value-container">
                <div class="metric-value-enhanced">${loadData.five}</div>
                <div class="metric-unit">avg</div>
              </div>
              <div class="metric-status-enhanced ${this.getLoadStatus(parseFloat(loadData.five))}">${this.getLoadStatusText(parseFloat(loadData.five))}</div>
              <div class="metric-progress-bar">
                <div class="progress-fill" style="width: ${Math.min(parseFloat(loadData.five) * 50, 100)}%; background: ${this.getLoadStatusColor(parseFloat(loadData.five))}"></div>
              </div>
            </div>
            
            <div class="enhanced-metric-card fifteen-minute-card">
              <div class="metric-card-glow"></div>
              <div class="metric-icon-container">
                <div class="metric-icon-enhanced">📉</div>
                <div class="metric-icon-ring"></div>
              </div>
              <div class="metric-header-enhanced">
                <div class="metric-label-enhanced">15 Minutes</div>
                <div class="metric-trend down"></div>
              </div>
              <div class="metric-value-container">
                <div class="metric-value-enhanced">${loadData.fifteen}</div>
                <div class="metric-unit">avg</div>
              </div>
              <div class="metric-status-enhanced ${this.getLoadStatus(parseFloat(loadData.fifteen))}">${this.getLoadStatusText(parseFloat(loadData.fifteen))}</div>
              <div class="metric-progress-bar">
                <div class="progress-fill" style="width: ${Math.min(parseFloat(loadData.fifteen) * 50, 100)}%; background: ${this.getLoadStatusColor(parseFloat(loadData.fifteen))}"></div>
              </div>
            </div>
          </div>
          
          <div class="enhanced-load-summary">
            <div class="summary-header-enhanced">
              <div class="summary-icon-container">
                <div class="summary-icon">💡</div>
                <div class="summary-sparkle"></div>
              </div>
              <div class="summary-title-enhanced">Performance Overview</div>
            </div>
            <div class="summary-content-enhanced">
              <div class="performance-assessment">
                <div class="assessment-text">Your server is operating with <span class="performance-level ${this.getOverallLoadStatus(loadData)}">${this.getOverallLoadStatusText(loadData)}</span> performance levels.</div>
                <div class="assessment-description">All load averages are within optimal ranges, indicating excellent system performance.</div>
              </div>
              
              <div class="performance-scale-enhanced">
                <div class="scale-title">Performance Scale</div>
                <div class="scale-items-enhanced">
                  <div class="scale-item-enhanced excellent">
                    <div class="scale-indicator"></div>
                    <span>0.0-0.7 Excellent</span>
                  </div>
                  <div class="scale-item-enhanced good">
                    <div class="scale-indicator"></div>
                    <span>0.7-1.0 Good</span>
                  </div>
                  <div class="scale-item-enhanced moderate">
                    <div class="scale-indicator"></div>
                    <span>1.0-1.5 Moderate</span>
                  </div>
                  <div class="scale-item-enhanced high">
                    <div class="scale-indicator"></div>
                    <span>1.5-2.0 High</span>
                  </div>
                  <div class="scale-item-enhanced critical">
                    <div class="scale-indicator"></div>
                    <span>2.0+ Critical</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    },

    createEnhancedDefaultDataCards(data) {
      // Enhanced cards for other types of data
      let html = '<div class="enhanced-data-grid">';
      
      if (data.data && typeof data.data === 'object') {
        // Create beautiful cards for each data section
        Object.entries(data.data).forEach(([key, value], index) => {
          const delay = index * 0.1;
          
          if (typeof value === 'object' && value !== null) {
            html += `
              <div class="enhanced-data-section animate__animated animate__fadeInLeft" style="animation-delay: ${delay}s;">
                <div class="data-section-header">
                  <div class="section-icon-circle">
                    ${this.getDataIcon(key)}
                  </div>
                  <h4 class="section-title-enhanced">${this.formatKey(key)}</h4>
                  <div class="section-decoration"></div>
                </div>
                <div class="section-content-enhanced">
                  ${this.createEnhancedKeyValuePairs(value)}
                </div>
              </div>
            `;
          } else {
            html += `
              <div class="enhanced-data-item animate__animated animate__fadeInUp" style="animation-delay: ${delay}s;">
                <div class="data-item-icon">${this.getDataIcon(key)}</div>
                <div class="data-item-content">
                  <div class="data-label-enhanced">${this.formatKey(key)}</div>
                  <div class="data-value-enhanced">${this.formatValue(value)}</div>
                </div>
                <div class="data-item-arrow">→</div>
              </div>
            `;
          }
        });
      }
      
      html += '</div>';
      return html;
    },

    createEnhancedKeyValuePairs(obj) {
      return Object.entries(obj)
        .map(([key, value], index) => `
          <div class="enhanced-key-value-pair animate__animated animate__fadeInRight" style="animation-delay: ${index * 0.05}s;">
            <div class="key-container">
              <div class="key-icon">${this.getDataIcon(key)}</div>
              <div class="key-enhanced">${this.formatKey(key)}</div>
            </div>
            <div class="value-container">
              <div class="value-enhanced">${this.formatValue(value)}</div>
            </div>
          </div>
        `).join('');
    },

    getDataIcon(key) {
      const iconMap = {
        'metadata': '📋',
        'command': '⚡',
        'reason': '✅',
        'version': '🔢',
        'result': '📊',
        'data': '💾',
        'one': '1️⃣',
        'five': '5️⃣',
        'fifteen': '🔟',
        'status': '🎯',
        'timestamp': '⏰',
        'source': '🌐',
        'ai_context': '🤖',
        'analysis': '🔍',
        'scope': '📐'
      };
      return iconMap[key.toLowerCase()] || '📄';
    },

    formatValue(value) {
      if (typeof value === 'string' && value.includes('@')) {
        return `<span class="email-value">${value}</span>`;
      }
      if (typeof value === 'number' || (typeof value === 'string' && !isNaN(parseFloat(value)))) {
        return `<span class="numeric-value">${value}</span>`;
      }
      if (typeof value === 'boolean') {
        return `<span class="boolean-value ${value}">${value ? '✅ True' : '❌ False'}</span>`;
      }
      return `<span class="text-value">${value}</span>`;
    },

    getLoadStatusColor(loadValue) {
      if (loadValue <= 0.7) return 'linear-gradient(135deg, #10b981, #34d399)';
      if (loadValue <= 1.0) return 'linear-gradient(135deg, #3b82f6, #60a5fa)';
      if (loadValue <= 1.5) return 'linear-gradient(135deg, #f59e0b, #fbbf24)';
      if (loadValue <= 2.0) return 'linear-gradient(135deg, #f97316, #fb923c)';
      return 'linear-gradient(135deg, #ef4444, #f87171)';
    },

    getOverallLoadStatusText(loadData) {
      const loads = [parseFloat(loadData.one), parseFloat(loadData.five), parseFloat(loadData.fifteen)];
      const maxLoad = Math.max(...loads);
      
      if (maxLoad <= 0.7) return 'Excellent';
      if (maxLoad <= 1.0) return 'Good';
      if (maxLoad <= 1.5) return 'Moderate';
      if (maxLoad <= 2.0) return 'High';
      return 'Critical';
    },

    formatCardTitle(data) {
      if (data.source && data.source.includes('systemloadavg')) {
        return '🖥️ Server Load Averages';
      } else if (data.source && data.source.includes('cpanel')) {
        return '🛠️ cPanel Data';
      } else if (data.data && data.data.metadata) {
        return `📊 ${data.data.metadata.command || 'System Data'}`;
      }
      return '📋 API Response Data';
    },

    createDataVisualization(data) {
      // Handle different types of data structures
      if (data.data && data.data.metadata && data.data.metadata.command === 'systemloadavg') {
        return this.createLoadAverageVisualization(data.data);
      }
      
      // Default card layout for other data types
      return this.createDefaultDataCards(data);
    },

    createLoadAverageVisualization(systemData) {
      const loadData = systemData.data;
      const metadata = systemData.metadata;
      
      return `
        <div class="load-average-grid">
          <div class="metric-card one-min">
            <div class="metric-header">
              <span class="metric-icon">📈</span>
              <span class="metric-label">1 Minute</span>
            </div>
            <div class="metric-value">${loadData.one}</div>
            <div class="metric-status ${this.getLoadStatus(parseFloat(loadData.one))}">${this.getLoadStatusText(parseFloat(loadData.one))}</div>
          </div>
          
          <div class="metric-card five-min">
            <div class="metric-header">
              <span class="metric-icon">📊</span>
              <span class="metric-label">5 Minutes</span>
            </div>
            <div class="metric-value">${loadData.five}</div>
            <div class="metric-status ${this.getLoadStatus(parseFloat(loadData.five))}">${this.getLoadStatusText(parseFloat(loadData.five))}</div>
          </div>
          
          <div class="metric-card fifteen-min">
            <div class="metric-header">
              <span class="metric-icon">📉</span>
              <span class="metric-label">15 Minutes</span>
            </div>
            <div class="metric-value">${loadData.fifteen}</div>
            <div class="metric-status ${this.getLoadStatus(parseFloat(loadData.fifteen))}">${this.getLoadStatusText(parseFloat(loadData.fifteen))}</div>
          </div>
        </div>
        
        <div class="load-summary">
          <div class="summary-header">
            <span class="summary-icon">💡</span>
            <span class="summary-title">Performance Summary</span>
          </div>
          <div class="summary-content">
            <p>Your server is currently operating with <strong>${this.getOverallLoadStatus(loadData)}</strong> performance levels.</p>
            <div class="load-scale">
              <div class="scale-item excellent">0.0-0.7 Excellent</div>
              <div class="scale-item good">0.7-1.0 Good</div>
              <div class="scale-item moderate">1.0-1.5 Moderate</div>
              <div class="scale-item high">1.5-2.0 High</div>
              <div class="scale-item critical">2.0+ Critical</div>
            </div>
          </div>
        </div>
      `;
    },

    createDefaultDataCards(data) {
      // Create cards for other types of data
      let html = '<div class="data-grid">';
      
      if (data.data && typeof data.data === 'object') {
        Object.entries(data.data).forEach(([key, value]) => {
          if (typeof value === 'object' && value !== null) {
            html += `
              <div class="data-section">
                <h4 class="section-title">${this.formatKey(key)}</h4>
                <div class="section-content">
                  ${this.createKeyValuePairs(value)}
                </div>
              </div>
            `;
          } else {
            html += `
              <div class="data-item">
                <span class="data-label">${this.formatKey(key)}:</span>
                <span class="data-value">${value}</span>
              </div>
            `;
          }
        });
      }
      
      html += '</div>';
      return html;
    },

    createKeyValuePairs(obj) {
      return Object.entries(obj)
        .map(([key, value]) => `
          <div class="key-value-pair">
            <span class="key">${this.formatKey(key)}</span>
            <span class="value">${value}</span>
          </div>
        `).join('');
    },

    formatKey(key) {
      return key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1');
    },

    getLoadStatus(loadValue) {
      if (loadValue <= 0.7) return 'excellent';
      if (loadValue <= 1.0) return 'good';
      if (loadValue <= 1.5) return 'moderate';
      if (loadValue <= 2.0) return 'high';
      return 'critical';
    },

    getLoadStatusText(loadValue) {
      if (loadValue <= 0.7) return 'Excellent';
      if (loadValue <= 1.0) return 'Good';
      if (loadValue <= 1.5) return 'Moderate';
      if (loadValue <= 2.0) return 'High';
      return 'Critical';
    },

    getOverallLoadStatus(loadData) {
      const loads = [parseFloat(loadData.one), parseFloat(loadData.five), parseFloat(loadData.fifteen)];
      const maxLoad = Math.max(...loads);
      
      if (maxLoad <= 0.7) return 'excellent';
      if (maxLoad <= 1.0) return 'good';
      if (maxLoad <= 1.5) return 'moderate';
      if (maxLoad <= 2.0) return 'high';
      return 'critical';
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

/* API Response View Styles */
.api-response-container {
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 16px;
}

.api-response-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.view-toggle-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 20px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.view-toggle-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.view-toggle-btn:active {
  transform: translateY(0);
}

.formatted-view {
  margin-bottom: 0;
}

.raw-view {
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 16px;
  margin-top: 8px;
}

.raw-data-display {
  white-space: pre-wrap;
  word-wrap: break-word;
  font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #2d3748;
  margin: 0;
  background: #f7fafc;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  max-height: 400px;
  overflow-y: auto;
}

/* Beautiful API Data Card Styles */
.api-data-card {
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  margin: 16px 0;
  border: 1px solid rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  animation: cardSlideIn 0.6s ease-out;
}

@keyframes cardSlideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.card-header::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  opacity: 0.5;
  animation: headerShimmer 3s ease-in-out infinite;
}

@keyframes headerShimmer {
  0%, 100% { transform: rotate(0deg); }
  50% { transform: rotate(180deg); }
}

.header-content {
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1;
  position: relative;
}

.status-indicator {
  font-size: 1.5rem;
  padding: 8px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  animation: statusPulse 2s ease-in-out infinite;
}

@keyframes statusPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.status-indicator.success {
  background: rgba(34, 197, 94, 0.2);
}

.status-indicator.error {
  background: rgba(239, 68, 68, 0.2);
}

.header-text {
  flex: 1;
}

.card-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.card-subtitle {
  margin: 4px 0 0 0;
  font-size: 0.875rem;
  opacity: 0.9;
  font-weight: 400;
}

.timestamp-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.card-body {
  padding: 24px;
}

/* Load Average Visualization */
.load-average-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.metric-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  border: 1px solid #e5e7eb;
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.metric-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 12px;
}

.metric-icon {
  font-size: 1.25rem;
}

.metric-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-value {
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
  margin: 8px 0;
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
}

.metric-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-status.excellent {
  background: #dcfce7;
  color: #15803d;
}

.metric-status.good {
  background: #dbeafe;
  color: #1d4ed8;
}

.metric-status.moderate {
  background: #fef3c7;
  color: #92400e;
}

.metric-status.high {
  background: #fed7aa;
  color: #ea580c;
}

.metric-status.critical {
  background: #fee2e2;
  color: #dc2626;
}

/* Load Summary */
.load-summary {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #d1d5db;
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.summary-icon {
  font-size: 1.5rem;
}

.summary-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #374151;
}

.summary-content p {
  margin: 0 0 16px 0;
  color: #4b5563;
  line-height: 1.6;
}

.load-scale {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.scale-item {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid;
}

.scale-item.excellent {
  background: #dcfce7;
  color: #15803d;
  border-color: #22c55e;
}

.scale-item.good {
  background: #dbeafe;
  color: #1d4ed8;
  border-color: #3b82f6;
}

.scale-item.moderate {
  background: #fef3c7;
  color: #92400e;
  border-color: #f59e0b;
}

.scale-item.high {
  background: #fed7aa;
  color: #ea580c;
  border-color: #f97316;
}

.scale-item.critical {
  background: #fee2e2;
  color: #dc2626;
  border-color: #ef4444;
}

/* Default Data Cards */
.data-grid {
  display: grid;
  gap: 16px;
}

.data-section {
  background: #f8fafc;
  border-radius: 8px;
  padding: 16px;
  border: 1px solid #e5e7eb;
}

.section-title {
  margin: 0 0 12px 0;
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.section-content {
  display: grid;
  gap: 8px;
}

.data-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f3f4f6;
}

.data-item:last-child {
  border-bottom: none;
}

.data-label {
  font-weight: 600;
  color: #6b7280;
}

.data-value {
  font-weight: 700;
  color: #1f2937;
}

.key-value-pair {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
}

.key {
  font-weight: 500;
  color: #6b7280;
  font-size: 0.875rem;
}

.value {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

/* Card Footer */
.card-footer {
  background: #f8fafc;
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
}

.ai-context {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.context-icon {
  font-size: 1.25rem;
  margin-top: 2px;
}

.context-content {
  flex: 1;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #4b5563;
}

.context-content strong {
  color: #1f2937;
}

.context-content em {
  color: #6b7280;
  font-style: italic;
}

/* Responsive Design */
@media (max-width: 768px) {
  .load-average-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .load-scale {
    flex-direction: column;
  }
  
  .metric-card {
    padding: 16px;
  }
  
  .card-body {
    padding: 16px;
  }
}

/* Enhanced API Data Card Styles */
.api-data-card-enhanced {
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow: 
    0 20px 40px rgba(0, 0, 0, 0.1), 
    0 8px 16px rgba(0, 0, 0, 0.05),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  overflow: hidden;
  margin: 20px 0;
  border: 1px solid rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
  position: relative;
  animation: cardSlideInEnhanced 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes cardSlideInEnhanced {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.card-header-enhanced {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px;
  position: relative;
  overflow: hidden;
}

.header-background-pattern {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: 
    radial-gradient(circle at 20% 50%, rgba(255,255,255,0.15) 2px, transparent 2px),
    radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 1px, transparent 1px),
    radial-gradient(circle at 40% 80%, rgba(255,255,255,0.08) 1.5px, transparent 1.5px);
  background-size: 40px 40px, 30px 30px, 50px 50px;
  animation: patternMove 20s linear infinite;
}

@keyframes patternMove {
  0% { transform: translate(0, 0); }
  100% { transform: translate(40px, 40px); }
}

.header-content-enhanced {
  display: flex;
  align-items: center;
  gap: 16px;
  position: relative;
  z-index: 2;
}

.status-indicator-enhanced {
  font-size: 1.75rem;
  padding: 12px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.status-pulse {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  animation: statusPulseEnhanced 2s ease-in-out infinite;
}

@keyframes statusPulseEnhanced {
  0%, 100% { 
    transform: scale(1); 
    opacity: 1; 
  }
  50% { 
    transform: scale(1.2); 
    opacity: 0.5; 
  }
}

.status-indicator-enhanced.success {
  background: rgba(34, 197, 94, 0.3);
  box-shadow: 0 0 20px rgba(34, 197, 94, 0.4);
}

.status-indicator-enhanced.error {
  background: rgba(239, 68, 68, 0.3);
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.4);
}

.header-text-enhanced {
  flex: 1;
}

.card-title-enhanced {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  letter-spacing: -0.025em;
}

.card-subtitle-enhanced {
  margin: 6px 0 0 0;
  font-size: 0.95rem;
  opacity: 0.9;
  font-weight: 400;
}

.header-decoration {
  width: 60px;
  height: 60px;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
  border-radius: 50%;
  position: absolute;
  right: -30px;
  top: -30px;
  animation: decorationFloat 6s ease-in-out infinite;
}

@keyframes decorationFloat {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-10px) rotate(180deg); }
}

.timestamp-badge-enhanced {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.25);
  padding: 8px 16px;
  border-radius: 25px;
  font-size: 0.8rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  position: relative;
  z-index: 2;
}

.timestamp-icon {
  animation: tickTock 2s ease-in-out infinite;
}

@keyframes tickTock {
  0%, 50%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(15deg); }
  75% { transform: rotate(-15deg); }
}

.card-body-enhanced {
  padding: 32px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

/* Enhanced Load Average Section */
.enhanced-load-average-section {
  position: relative;
}

.section-header-enhanced {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px 0;
}

.section-icon-container {
  position: relative;
}

.section-icon {
  font-size: 2rem;
  position: relative;
  z-index: 2;
}

.section-icon-glow {
  position: absolute;
  inset: -8px;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  animation: iconGlow 3s ease-in-out infinite;
}

@keyframes iconGlow {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.2); opacity: 0.8; }
}

.section-title-enhanced {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  flex: 1;
}

.section-divider {
  height: 2px;
  flex: 1;
  background: linear-gradient(90deg, #667eea, transparent);
  border-radius: 1px;
}

.enhanced-load-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.enhanced-metric-card {
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 16px;
  padding: 24px;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.enhanced-metric-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.metric-card-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
  animation: shimmerGlow 2s ease-in-out infinite;
}

@keyframes shimmerGlow {
  0%, 100% { opacity: 0.7; }
  50% { opacity: 1; }
}

.metric-icon-container {
  position: relative;
  margin-bottom: 16px;
}

.metric-icon-enhanced {
  font-size: 2.5rem;
  position: relative;
  z-index: 2;
}

.metric-icon-ring {
  position: absolute;
  inset: -12px;
  border: 2px solid rgba(102, 126, 234, 0.2);
  border-radius: 50%;
  animation: iconRing 4s linear infinite;
}

@keyframes iconRing {
  0% { transform: rotate(0deg) scale(1); }
  50% { transform: rotate(180deg) scale(1.1); }
  100% { transform: rotate(360deg) scale(1); }
}

.metric-header-enhanced {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.metric-label-enhanced {
  font-size: 0.9rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metric-trend {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
}

.metric-trend.up {
  background: linear-gradient(135deg, #10b981, #34d399);
  color: white;
}

.metric-trend.stable {
  background: linear-gradient(135deg, #3b82f6, #60a5fa);
  color: white;
}

.metric-trend.down {
  background: linear-gradient(135deg, #8b5cf6, #a78bfa);
  color: white;
}

.metric-trend.up::before { content: '↗'; }
.metric-trend.stable::before { content: '→'; }
.metric-trend.down::before { content: '↘'; }

.metric-value-container {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 12px;
}

.metric-value-enhanced {
  font-size: 3rem;
  font-weight: 900;
  color: #1f2937;
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  line-height: 1;
}

.metric-unit {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 600;
}

.metric-status-enhanced {
  padding: 6px 16px;
  border-radius: 25px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
  text-align: center;
}

.metric-progress-bar {
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.progress-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 1s ease-out;
  position: relative;
}

.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
  animation: progressShine 2s ease-in-out infinite;
}

@keyframes progressShine {
  0% { left: -100%; }
  100% { left: 100%; }
}

/* Enhanced Load Summary */
.enhanced-load-summary {
  background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #d1d5db;
  position: relative;
  overflow: hidden;
}

.enhanced-load-summary::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
  background-size: 200% 100%;
  animation: summaryGradient 3s ease-in-out infinite;
}

@keyframes summaryGradient {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.summary-header-enhanced {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.summary-icon-container {
  position: relative;
}

.summary-icon {
  font-size: 1.75rem;
  position: relative;
  z-index: 2;
}

.summary-sparkle {
  position: absolute;
  inset: -6px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
  0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
  50% { transform: scale(1.3) rotate(180deg); opacity: 1; }
}

.summary-title-enhanced {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.summary-content-enhanced {
  display: grid;
  gap: 20px;
}

.performance-assessment {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.5);
}

.assessment-text {
  font-size: 1.1rem;
  line-height: 1.6;
  color: #374151;
  margin-bottom: 8px;
}

.performance-level {
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 6px;
}

.performance-level.excellent {
  background: #dcfce7;
  color: #15803d;
}

.performance-level.good {
  background: #dbeafe;
  color: #1d4ed8;
}

.performance-level.moderate {
  background: #fef3c7;
  color: #92400e;
}

.performance-level.high {
  background: #fed7aa;
  color: #ea580c;
}

.performance-level.critical {
  background: #fee2e2;
  color: #dc2626;
}

.assessment-description {
  font-size: 0.9rem;
  color: #6b7280;
  line-height: 1.5;
}

.performance-scale-enhanced {
  background: rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 16px;
}

.scale-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.scale-items-enhanced {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 8px;
}

.scale-item-enhanced {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid;
  transition: all 0.2s ease;
}

.scale-item-enhanced:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.scale-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.scale-item-enhanced.excellent {
  background: #dcfce7;
  color: #15803d;
  border-color: #22c55e;
}

.scale-item-enhanced.excellent .scale-indicator {
  background: #22c55e;
}

.scale-item-enhanced.good {
  background: #dbeafe;
  color: #1d4ed8;
  border-color: #3b82f6;
}

.scale-item-enhanced.good .scale-indicator {
  background: #3b82f6;
}

.scale-item-enhanced.moderate {
  background: #fef3c7;
  color: #92400e;
  border-color: #f59e0b;
}

.scale-item-enhanced.moderate .scale-indicator {
  background: #f59e0b;
}

.scale-item-enhanced.high {
  background: #fed7aa;
  color: #ea580c;
  border-color: #f97316;
}

.scale-item-enhanced.high .scale-indicator {
  background: #f97316;
}

.scale-item-enhanced.critical {
  background: #fee2e2;
  color: #dc2626;
  border-color: #ef4444;
}

.scale-item-enhanced.critical .scale-indicator {
  background: #ef4444;
}

/* Enhanced Default Data Cards */
.enhanced-data-grid {
  display: grid;
  gap: 20px;
}

.enhanced-data-section {
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.enhanced-data-section:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.data-section-header {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
  overflow: hidden;
}

.section-icon-circle {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.section-decoration {
  position: absolute;
  right: -20px;
  top: -20px;
  width: 60px;
  height: 60px;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.section-content-enhanced {
  padding: 20px;
}

.enhanced-data-item {
  background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid #e5e7eb;
  transition: all 0.3s ease;
  margin-bottom: 12px;
}

.enhanced-data-item:hover {
  transform: translateX(8px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.data-item-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
}

.data-item-content {
  flex: 1;
}

.data-label-enhanced {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.data-value-enhanced {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.data-item-arrow {
  font-size: 1.25rem;
  color: #667eea;
  transition: all 0.3s ease;
}

.enhanced-data-item:hover .data-item-arrow {
  transform: translateX(4px);
}

.enhanced-key-value-pair {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  margin-bottom: 8px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.5);
  transition: all 0.2s ease;
}

.enhanced-key-value-pair:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateX(4px);
}

.key-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.key-icon {
  font-size: 1rem;
  opacity: 0.8;
}

.key-enhanced {
  font-weight: 600;
  color: #4b5563;
  font-size: 0.875rem;
}

.value-container {
  text-align: right;
}

.value-enhanced {
  font-weight: 700;
  color: #1f2937;
  font-size: 0.875rem;
}

/* Value Type Styling */
.email-value {
  color: #3b82f6;
  font-family: monospace;
}

.numeric-value {
  color: #059669;
  font-family: monospace;
  font-weight: 700;
}

.boolean-value {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.boolean-value.true {
  background: #dcfce7;
  color: #15803d;
}

.boolean-value.false {
  background: #fee2e2;
  color: #dc2626;
}

.text-value {
  color: #374151;
}

/* Enhanced Footer */
.card-footer-enhanced {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
}

.ai-context-enhanced {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.context-icon-enhanced {
  position: relative;
  font-size: 1.5rem;
  margin-top: 2px;
}

.context-pulse {
  position: absolute;
  inset: -6px;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  animation: contextPulse 3s ease-in-out infinite;
}

@keyframes contextPulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.4); opacity: 0.8; }
}

.context-content-enhanced {
  flex: 1;
}

.context-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.context-analysis {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #4b5563;
  margin-bottom: 4px;
}

.context-scope {
  font-size: 0.8rem;
  color: #6b7280;
  font-style: italic;
}

/* Mobile Responsiveness for Enhanced Cards */
@media (max-width: 768px) {
  .enhanced-load-grid {
    grid-template-columns: 1fr;
  }
  
  .card-header-enhanced {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
  }
  
  .scale-items-enhanced {
    grid-template-columns: 1fr;
  }
  
  .enhanced-metric-card {
    padding: 20px;
  }
  
  .card-body-enhanced {
    padding: 20px;
  }
  
  .metric-value-enhanced {
    font-size: 2.5rem;
  }
  
  .enhanced-data-item {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
  
  .data-item-arrow {
    transform: rotate(90deg);
  }
}
</style>
