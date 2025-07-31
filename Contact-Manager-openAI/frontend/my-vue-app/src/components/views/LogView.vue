<template>
  <div class="simple-view">
    <div class="simple-container">
      <h1>History Log</h1>
      <pre class="log-content">{{ historyLog }}</pre>
      <BaseButton @click="$router.push('/ai')" variant="secondary">Back to AI</BaseButton>
    </div>
  </div>
</template>

<script>
import BaseButton from '../ui/BaseButton.vue'

export default {
  name: 'LogView',
  components: {
    BaseButton
  },
  data() {
    return {
      historyLog: 'Loading history log...\n\nNo entries found.'
    }
  },
  mounted() {
    // Load history log from backend
    this.loadHistoryLog()
  },
  methods: {
    loadHistoryLog() {
      // Implement history log loading logic here
      console.log('Loading history log...')
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
  max-width: 600px;
  width: 100%;
}

.log-content {
  background: #f8fafc;
  padding: 16px;
  border-radius: 8px;
  font-family: monospace;
  font-size: 12px;
  max-height: 400px;
  overflow-y: auto;
  margin: 16px 0;
  border: 1px solid #e2e8f0;
}
</style> 