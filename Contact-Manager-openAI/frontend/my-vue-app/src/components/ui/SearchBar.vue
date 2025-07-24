<template>
  <div class="search-bar">
    <input 
      v-model="localValue"
      :placeholder="placeholder"
      class="search-input"
      @keydown="handleKeydown"
      @focus="onFocus"
      @blur="onBlur"
    />
    <span class="search-icon">🔍</span>
  </div>
</template>

<script>
export default {
  name: 'SearchBar',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'Search...'
    }
  },
  emits: ['update:modelValue', 'search', 'focus', 'blur'],
  computed: {
    localValue: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  },
  methods: {
    handleKeydown(event) {
      if (event.key === 'Enter') {
        this.$emit('search', this.localValue)
      }
    },
    onFocus(event) {
      event.target.style.borderColor = '#3b82f6'
      event.target.style.boxShadow = '0 0 0 3px rgba(59,130,246,0.1)'
      this.$emit('focus', event)
    },
    onBlur(event) {
      event.target.style.borderColor = 'rgba(209, 213, 219, 0.8)'
      event.target.style.boxShadow = 'none'
      this.$emit('blur', event)
    }
  }
}
</script>

<style scoped>
.search-bar {
  position: relative;
}

.search-input {
  width: 280px;
  padding: 10px 40px 10px 16px;
  border: 1px solid rgba(209, 213, 219, 0.8);
  border-radius: 8px;
  font-size: 14px;
  background: rgba(255,255,255,0.9);
  transition: all 0.2s ease;
}

.search-input:focus {
  outline: none;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-size: 16px;
  pointer-events: none;
}
</style> 