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

  <div v-else-if="view === 'AI'" style="display: flex; flex-direction: column; padding: 20px; background: #f9f9f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh;">

<!-- Header -->
<div style="display: flex; gap: 20px; margin-bottom: 20px;">
  <div style="flex: 1; text-align: center;">
    <h2 style="font-size: 26px; margin: 0; border-bottom: 2px solid #ccc; padding-bottom: 10px;">Agent Chat</h2>
  </div>
  <div style="flex: 1; text-align: center;">
    <h2 style="font-size: 26px; margin: 0; border-bottom: 2px solid #ccc; padding-bottom: 10px;">Action Queue</h2>
  </div>
</div>

<!-- Main Columns -->
<div style="display: flex; gap: 20px; flex: 1;">

  <!-- Agent Chat Left Column -->
  <div style="flex: 1; background: white; border: 1px solid #ccc; border-radius: 10px; padding: 20px; display: flex; flex-direction: column;">
    <div style="flex: 1; overflow-y: auto; margin-bottom: 16px;">
  <p
    v-for="(resp, index) in aiResponse"
    :key="index"
    :style="getStyle(resp)"
  >
    {{ resp }}
  </p>
</div>
    <textarea
      id="input-message"
      v-model="userMessage"
      placeholder="Type your message"
      style="width: 96%; height: 60px; padding: 10px; font-size: 14px; border-radius: 6px; border: 1px solid #ccc; resize: none; margin-bottom: 12px;"
    ></textarea>
    <div style="display: flex; gap: 10px;">
      <button @click="aiClick" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 6px; font-weight: 500;">Submit</button>
      <button @click="aiClear" style="padding: 8px 16px; background: #e0e0e0; color: #333; border: none; border-radius: 6px; font-weight: 500;">Clear History</button>
    </div>
  </div>

  <!-- Action Queue Right Column -->
  <div style="flex: 1; background: white; border: 1px solid #ccc; border-radius: 10px; padding: 20px;">
    <div
      v-for="(func, index) in pending_functions"
      :key="index"
      style="background: #f0f0f0; padding: 16px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #bbb;"
    >
      <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
        {{ func.functionName }}
      </div>
      <p style="font-size: 14px; margin-bottom: 12px;">
        {{ func.description }}
      </p>
      <div style="display: flex; gap: 10px;">
        <button @click="aiReject(index)" style="padding: 8px 16px; background-color: #c0392b; color: white; border: none; border-radius: 6px; font-weight: 500;">Reject</button>
        <button @click="aiProceed(index)" style="padding: 8px 16px; background-color: #27ae60; color: white; border: none; border-radius: 6px; font-weight: 500;">Proceed</button>
      </div>
    </div>
  </div>
</div>

<!-- Footer Buttons -->
<div style="margin-top: 40px; display: flex; gap: 20px;">
  <button @click="view = 'contacts'" style="padding: 8px 16px; background: #ddd; border: none; border-radius: 6px;">Back</button>
  <button @click="view = 'session'" style="padding: 8px 16px; background: #ddd; border: none; border-radius: 6px;">View a Session</button>
  <button @click="aiLog()" style="padding: 8px 16px; background: #ddd; border: none; border-radius: 6px;">View History Log</button>
</div>

<!-- Tokens + API Response -->
<div style="margin-top: 20px;">
  <p style="font-size: 18px; font-weight: 600;">Tokens Used: {{ tokens }}</p>
  <p style="font-size: 22px; font-weight: bold; margin-top: 20px;">API Response/calls:</p>
  <p style="font-size: 16px;">{{ apiResponse }}</p>
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
  } else if (upperText.startsWith('TICKET:')) {
    style.color = '#525050';
    style.textAlign = 'left';
    style.marginLeft = 'auto';
    style.backgroundColor = '#f2f2f2';
  } else if (upperText.startsWith('AI:')) {
    style.color = '#1533ad';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = '#e6f0ff';
  } else {
    style.color = 'white';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = 'white';
  }

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
      console.log('Sending API_response:', JSON.parse(JSON.stringify(this.apiResponse)));
      const trimmedMessage = this.userMessage.trim();
      if (trimmedMessage){
        this.aiResponse.push(trimmedMessage);
      }


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

</style>
