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

  <div v-else-if="view === 'AI'">
    <div :style="{ display: 'flex', gap: '10px' }">
      <!-- Left Column -->
      <div :style="{ width: '50%', textAlign: 'center' }">
        <p :style="{ fontSize: '30px', margin: 0 }">Agent Chat</p>
      </div>

      <!-- Right Column -->
      <div :style="{ width: '50%', textAlign: 'center' }">
        <p :style="{ fontSize: '30px', margin: 0 }">Action Queue</p>
      </div>
    </div>

    <div :style="{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '20px' }">
      <!-- Left Column -->
      <div :style="{ width: '50%', marginRight: '20px', border: '2px solid #ccc'}">
        <p  v-for="(resp, index) in aiResponse" :key="index" :style="{ fontSize: '15px' }"> 
          {{ resp }}
        </p>
        <textarea
          id="input-message"
          placeholder="Type your message"
          v-model="userMessage"
          :style="{ width: '92%', height: '50px', fontSize: '16px', padding: '20px', marginTop: '20px' }"
        ></textarea>
        <button @click="aiClick()" :style="{marginTop: '20px'}">
          Submit
        </button>
        <button @click="aiClear()" :style="{marginTop: '20px'}">
        Clear History
      </button>
      </div>

      <!-- Right Column (wraps all function blocks) -->
      <div :style="{ width: '50%' }">
        <div
          v-for="(func, index) in pending_functions"
          :key="index"
          :style="{ marginBottom: '10px', backgroundColor: '#e6e6e6', padding: '10px', border: '1px solid #ccc', borderRadius: '6px' }"
        >
          <div :style="{ fontSize: '20px', marginBottom: '20px' }">
            {{ func.functionName }} : {{ func.description }}
          </div>
          <div>
            <button
              @click="aiReject(index)"
              style="background-color: #991506; color: white; margin-right: 16px; margin-bottom: 16px"
            >
              Reject
            </button>
            <button
              @click="aiProceed(index)"
              style="background-color: #099c33; color:white; margin-left: 16px"
            >
              Proceed
            </button>
          </div>
        </div>
      </div>

    </div>


      <button @click="view ='contacts'" :style="{marginTop: '40px'}">
        Back
      </button>
      <button @click="view = 'session'" :style="{marginTop: '40px'}">
        View a Session
      </button>
      <button @click="aiLog()" :style="{marginTop: '40px'}">
        View History Log
      </button>
      <p :style="{fontSize: '20px'}"> 
       Tokens Used: {{ tokens }}
      </p>
      <p :style="{fontSize: '30px'}"> 
      API Response/calls:
      </p>
      <p :style="{fontSize: '20px'}"> 
        {{ apiResponse }}
      </p>

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
      acknowledged_functions: []
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
      console.log('Sending API_response:', JSON.parse(JSON.stringify(this.apiResponse)));


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
            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
            // this.pending_functions.push(...data.pending_functions);
            this.pending_functions = data.pending_functions;
          
          }
          // console.log('tokens as string:', this.tokens);
    })
    // userMessage = '';
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
          }
          this.acknowledged_functions = [];
      this.userMessage = '';
      this.aiClick();
      })
    },

    aiReject(index) {
      this.pending_functions.splice(index, 1);
    }
  }

}
</script>

<style scoped>
/* Optional styling */
</style>
