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

  
</template>

<script>
export default {
  data() {
    return {
      title: 'Contact Manager',
      subtitle: 'Contacts List',
      contacts: [],
      view: 'contacts',
      selectedContact: {name: '', email: '', phone: ''},
      selectedInd: null
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
      this.selectedContact = { name: '', email: '', phone: '' }
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
        body:JSON.stringify ({index: this.selectedInd, contact: this.selectedContact})
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
        body:JSON.stringify (this.selectedInd)
      })
      alert("Contact was Deleted")
      this.view ='contacts'
    }
  }

}
</script>

<style scoped>
/* Optional styling */
</style>
