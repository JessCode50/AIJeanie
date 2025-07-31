<template>
  <div class="simple-view">
    <div class="simple-container">
      <h1>Client Info Input</h1>
      <div class="client-form">
        <p>Client ID: <input type="text" v-model="clientId" class="simple-input"></p>
        <p>Product: <input type="text" v-model="product" class="simple-input"></p>
        <p>Server: <input type="text" v-model="server" class="simple-input"></p>
        <div class="form-actions">
          <BaseButton @click="enterClientInfo">Submit</BaseButton>
          <BaseButton @click="$router.push('/ai')" variant="secondary">Back to AI</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'

export default {
  name: 'ClientInputView',
  components: {
    BaseButton
  },
  data() {
    return {
      clientId: '',
      product: '',
      server: ''
    }
  },
  methods: {
    enterClientInfo() {
      console.log('Client info entered:', {
        clientId: this.clientId,
        product: this.product,
        server: this.server
      })
      // Implement client info submission logic here
    }
  }
}
</script>

<style scoped>
.simple-view {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
  padding: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.simple-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  max-width: 500px;
  width: 100%;
}

.client-form p {
  margin: 12px 0;
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #374151;
}

.simple-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  margin-left: 8px;
  flex: 1;
}

.form-actions {
  margin-top: 24px;
  display: flex;
  gap: 12px;
  justify-content: center;
}
</style> 