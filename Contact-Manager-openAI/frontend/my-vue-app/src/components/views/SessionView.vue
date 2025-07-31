<template>
  <div class="simple-view">
    <div class="simple-container">
      <h1>Session Viewer</h1>
      <p>ID Number: <input type="text" v-model="id" class="simple-input"></p>
      <BaseButton @click="aiView">Submit</BaseButton>
      <p class="simple-text">Message History: {{ messageHistory }}</p>
      <BaseButton @click="$router.push('/ai')" variant="secondary">Back to AI</BaseButton>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'

export default {
  name: 'SessionView',
  components: {
    BaseButton
  },
  data() {
    return {
      id: '',
      messageHistory: ''
    }
  },
  methods: {
    aiView() {
      console.log('AI View for ID:', this.id)
      // Implement session viewing logic here
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

.simple-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  margin-left: 8px;
}

.simple-text {
  margin: 16px 0;
  color: #64748b;
}
</style> 