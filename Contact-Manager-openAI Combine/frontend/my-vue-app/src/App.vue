<!-- src/App.vue -->
<template>
  <div v-if="view === 'contacts'" >
    <h1 >{{ title }} </h1>
    <h1 :style="{ fontSize: '40px'}">{{ subtitle }} </h1>
    <div v-for="(contact, index) in contacts" :key="index" :style="{ marginBottom: '10px', fontSize: '20px' }">
      <button @click="contactClick(index) ">
        {{ contact.name }}
      </button>
    </div>
    <div>
      <button @click="view = 'new'" :style="{ marginTop: '30px' }">
        Add New Contact
      </button>
      <button @click="view = 'AI'">
        Open AI
      </button>
    </div>
  </div>

  <div v-else-if="view === 'read'">
    <h1>{{selectedContact.name}}</h1>
    <p>
      Email: {{ selectedContact.email }}
    </p>
    <p>
      Phone: {{ selectedContact.phone }}
    </p>
    <button @click="view ='contacts'">
      Back
    </button>
    <button @click="view ='edit'">
      Edit
    </button>
    <button @click="deleteContact">
      Delete
    </button>
  </div>

  <div v-else-if="view === 'edit'">
    <h1>Edit Contact</h1>
    <form @submit.prevent="editedContact">
      <p>Contact Name: <input type="text" v-model="selectedContact.name"></p>
      <p>Email: <input type="email" v-model="selectedContact.email"></p>
      <p>Phone Number: <input type="tel" v-model="selectedContact.phone"></p>
      <button type= "submit"> Save Changes</button>
    </form>
    <button @click="view ='read'">
      Cancel
    </button>
  </div>

  <div v-else-if="view === 'new'">
    <h1>New Contact</h1>
    <form @submit.prevent="newContact">
      <p>Contact Name: <input type="text" v-model="selectedContact.name"></p>
      <p>Email: <input type="email" v-model="selectedContact.email"></p>
      <p>Phone Number: <input type="tel" v-model="selectedContact.phone"></p>
      <button type="submit">Save New Contact</button>
    </form>

    <button @click="view ='contacts'">
      Cancel
    </button>
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
            <!-- <div v-if="isApiResponseMessage(resp, index)" class="api-response-container"> -->
              <!-- Toggle Button -->
              <!-- <div class="api-response-header"> -->
                <!-- <h4 style="margin: 0; color: #374151; font-size: 0.9rem; font-weight: 600;">📊 API Response Data</h4>
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
                </button> -->
              <!-- </div> -->
              <!-- Content Display -->
              <div v-if="getApiResponseViewMode(index) === 'formatted'" class="formatted-view">
                <div class="message-text" v-html="getFormattedApiData(index)"></div>
              </div>
              <div v-else class="raw-view">
                <pre class="raw-data-display">{{ getRawApiData(index) }}</pre>
              </div>
            <!-- </div> -->
            <!-- Regular message display -->
         
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
          @click="aiClick()"
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
    <p>ID Number: <input type="text" v-model="id"></p>
    <button @click="aiView()" :style="{marginTop: '20px'}">
        Submit
      </button>
    <p :style="{fontSize: '20px'}"> 
       Message History {{ messageHistory }}
    </p>
    <button @click="view ='AI'" :style="{marginTop: '40px'}">
        Back
    </button>
  </div>

  <div v-else-if="view === 'log'">
    <p :style="{fontSize: '40px'}"> 
       History Log:
    </p>
    <p style="white-space: pre-wrap">
      {{ historyLog }}
    </p>
    <button @click="view ='AI'" :style="{marginTop: '40px'}">
        Back
    </button>
  </div>

  <div v-else-if="view === 'clientInput'" style="display: flex; flex-direction: column; align-items: center; padding: 20px;">
  <p style="font-size: 40px; margin-bottom: 20px;"> 
    Client Info Input:
  </p>

  <form @submit.prevent="handleClientSubmit" style="display: flex; flex-direction: column; width: 100%; max-width: 400px; gap: 15px;">
    
    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Client ID:
      <input
        v-model="clientId"
        type="text"
        placeholder="Enter Client ID"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Product/Service:
      <input
        v-model="product"
        type="text"
        placeholder="Enter Product or Service"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Server:
      <input
        v-model="server"
        type="text"
        placeholder="Enter Server"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <button
      type="submit"
      style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"
    >
      Submit
    </button>
  </form>

  <button @click="view ='AI'" :style="{marginTop: '40px'}">
        Back
  </button>
</div>

</template>

<script>
export default {
  data() {
    return {
      title: 'Contact Manager',
      subtitle: 'Contacts List',
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
      pending_functions: [],
      acknowledged_functions: [],
      ticketIdInput: '',
      ticketMessage: '',

      loading: false,
      dots: '',
      intervalId: null,

      clientId: '',
      product: '',
      server: '',

      satisfaction: '',
      priority: '',

    }
  }, 

  mounted() {
    fetch('http://localhost:8080/contacts',{
        method: 'GET'
      }).then(response => response.json())  
        .then(data => {
          console.log('Fetched contacts:', data);
          this.contacts = data;
    })
  },

  watch: {
    view(newView) {
      if (newView === 'contacts') {
        this.reset();
      }
    }
  },

  methods: {

    startLoadingDots() {
  if (this.intervalId) clearInterval(this.intervalId);
  this.loading = true;
  let i = 0;
  this.intervalId = setInterval(() => {
    this.dots = '.'.repeat(i % 4);
    this.$forceUpdate(); // ← force re-render
    i++;
  }, 400);
},

stopLoadingDots() {
  clearInterval(this.intervalId);
  this.dots = '';
  this.loading = false;
},

shouldShow(resp) {
    return (
      resp.startsWith('AI:') ||
      resp.startsWith('CATEGORY:') ||
      resp.startsWith('AGENT:') ||
      resp.startsWith('TICKET')
    );
  },
  
    getStyle(text) {
  const upperText = text.toUpperCase();

  const style = {
    fontSize: '14px',
    marginBottom: '8px',
    lineHeight: 1.4,
    width: '50%',
    padding: '8px',
    borderRadius: '6px',
  };

  if (upperText.startsWith('AGENT:')) {
    style.color = '#c71104';
    style.textAlign = 'left';
    style.marginLeft = 'auto';
    style.backgroundColor = '#fce3e1';
  } else if (upperText.startsWith('TICKET')) {
    style.color = '#525050';
    style.textAlign = 'left';
    style.marginLeft = 'auto';
    style.backgroundColor = '#f2f2f2';
  } else if (upperText.startsWith('AI:')) {
    style.color = '#1533ad';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = '#e6f0ff';
  } else if (upperText.startsWith('CATEGORY:')) {
    style.color = '#a29da6';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = '#f6f2fa';
  } 
  //else {
  //   style.color = 'white';
  //   style.textAlign = 'left';
  //   style.marginRight = 'auto';
  //   style.backgroundColor = 'white';
  // }

  return style;
},


    reset(){
      fetch('http://localhost:8080/contacts',{
        method: 'GET'
      }).then(response => response.json())  
        .then(data => {
          this.contacts = data;
    })
      this.selectedContact = { id: '', name: '', email: '', phone: '' }
      this.selectedInd = null
    },

    contactClick(contactInd){
      this.view = 'read'
      this.selectedContact = this.contacts[contactInd]
      this.selectedInd = contactInd
    },

    editedContact(){
      fetch('http://localhost:8080/contacts/edit',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      this.view = 'read'
    },

    newContact(){
      fetch('http://localhost:8080/contacts/new',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      this.view = 'contacts'
    },

    deleteContact(){
      fetch('http://localhost:8080/contacts/delete',{
        method: 'POST',
        body:JSON.stringify (this.selectedContact)
      })
      alert("Contact was Deleted")
      this.view ='contacts'
    },

    aiClick() {
      // console.log('Sending API_response:', JSON.parse(JSON.stringify(this.apiResponse)));
      const trimmedMessage = this.userMessage.trim();
      if (trimmedMessage){
        this.aiResponse.push(trimmedMessage);
      }
      console.log(this.aiResponse);
      this.startLoadingDots();

      fetch('http://localhost:8080/contacts/ai',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify ({'message': this.userMessage, 'pendingFunctions': this.pending_functions, 'tokens': this.tokens,
        'API_response': [...this.apiResponse]
        })
      }).then(response => response.json())  
        .then(data => {
          if (data === 'alert'){
            alert("I couldn't handle the current request made");
            this.aiResponse = [];
            this.tokens = {};
            this.apiResponse = [];
          }
          else{
            if (data.response != 'No Response was Generated'){
              this.aiResponse.push(data.response);
            }
            if (data.category != ''){
                   this.aiResponse.push(data.category);
            }
            this.satisfaction = data.satisfaction;
            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
            // this.pending_functions.push(...data.pending_functions);
            this.pending_functions = data.pending_functions;
       
          
          }
          this.stopLoadingDots();
          // console.log('tokens as string:', this.tokens);
    })
    this.userMessage = '';
    },

    aiClear() {
      console.log("Clicked AI");
      fetch('http://localhost:8080/contacts/ai/clear',{
        method: 'POST',
        credentials: 'include'
      })
      this.aiResponse = [];
      this.tokens = {};
      this.apiResponse = [];
      this.pending_functions = [];
      this.acknowledged_functions = [];
      this.ticketMessage = '';
      this.priority = '';
    },

    aiView() {
      console.log("Clicked AI");
      fetch('http://localhost:8080/contacts/ai/view',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify (this.id)
      }).then(response => response.json())  
        .then(data => {
          this.messageHistory = data;
      })
    },

    aiLog() {
      fetch('http://localhost:8080/contacts/ai/log',{
        method: 'POST',
        credentials: 'include'
      }).then(response => response.json())  
        .then(data => {
          this.historyLog = data;
      })
      this.view = 'log';
    },

    aiProceed(index) {
      let func = this.pending_functions[index];
      func.confirmation = 'proceed';
      this.acknowledged_functions.push(func);
      this.pending_functions.splice(index, 1); 

      fetch('http://localhost:8080/contacts/ai/proceed',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify (this.acknowledged_functions)
      }).then(response => response.json())  
        .then(data => {
          if (data === 'alert'){
            alert("I couldn't handle the current request made");
            this.aiResponse = [];
            this.tokens = {};
            this.apiResponse = [];
          }
          else{
            if (data.response != 'No Response was Generated'){
              this.aiResponse.push(data.response);
            }
            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
            this.userMessage = data.user_message;
          }
          this.acknowledged_functions = [];
          this.aiClick();
      })
    },

    aiReject(index) {
      fetch('http://localhost:8080/contacts/ai/reject',{
        method: 'POST',
        credentials: 'include'
      }).then(response => response.json())  
      .then(data => {
        this.userMessage = data.user_message;
        this.pending_functions.splice(index, 1);
        this.aiClick();
      })
    },

    submitTicketId(){
      // this.userMessage = `TICKETID: ${this.ticketIdInput}`;
      fetch('http://localhost:8080/contacts/ai/ticketID',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify ({'message': this.ticketIdInput})
      }).then(response => response.json())  
        .then(data => {
          // this.aiResponse.push(data.summary);
          this.userMessage = data.summary;
          this.aiResponse.push(data.category);
          this.priority = data.priority;
          this.aiClick();
      })
      this.ticketIdInput = '';
    },

    handleClientSubmit(){
      fetch('http://localhost:8080/contacts/ai/client',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify ({'clientID': this.clientId, 'product': this.product, 'server': this.server})
      }) 
      this.view = "AI";
    },

    formatMessage(message) {
      
      if (typeof message === 'object' && message !== null) {
        return '<pre style="background: #f1f5f9; padding: 12px; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; line-height: 1.4;">' + 
               JSON.stringify(message, null, 2) + 
               '</pre>';
             
      }
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
</style>
