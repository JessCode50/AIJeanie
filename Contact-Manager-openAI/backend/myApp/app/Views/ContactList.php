<div>
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