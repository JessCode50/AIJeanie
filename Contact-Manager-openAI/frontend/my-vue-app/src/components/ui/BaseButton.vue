<template>
  <button 
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
    @mouseover="onHover"
    @mouseout="onHoverOut"
  >
    <span v-if="loading" class="loading-spinner">⟳</span>
    <slot v-else></slot>
  </button>
</template>

<script>
export default {
  name: 'BaseButton',
  props: {
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'success', 'warning', 'danger', 'info'].includes(value)
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    outline: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click'],
  computed: {
    buttonClasses() {
      return [
        'base-button',
        `variant-${this.variant}`,
        `size-${this.size}`,
        {
          'outline': this.outline,
          'disabled': this.disabled || this.loading,
          'loading': this.loading
        }
      ]
    }
  },
  methods: {
    onHover(event) {
      if (!this.disabled && !this.loading) {
        event.target.style.transform = 'translateY(-1px)'
        event.target.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)'
      }
    },
    onHoverOut(event) {
      event.target.style.transform = 'translateY(0)'
      event.target.style.boxShadow = ''
    }
  }
}
</script>

<style scoped>
.base-button {
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
}

.base-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.base-button:disabled:hover {
  transform: none !important;
  box-shadow: none !important;
}

/* Sizes */
.size-sm {
  padding: 8px 12px;
  font-size: 12px;
  border-radius: 6px;
}

.size-md {
  padding: 10px 16px;
  font-size: 14px;
}

.size-lg {
  padding: 12px 24px;
  font-size: 16px;
  border-radius: 10px;
}

/* Variants */
.variant-primary {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  color: white;
}

.variant-secondary {
  background: rgba(107, 114, 128, 0.1);
  color: #6b7280;
  border: 1px solid rgba(107, 114, 128, 0.2);
}

.variant-success {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.variant-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.variant-danger {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.variant-info {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

/* Outline variants */
.outline.variant-primary {
  background: transparent;
  color: #3b82f6;
  border: 1px solid #3b82f6;
}

.outline.variant-success {
  background: transparent;
  color: #059669;
  border: 1px solid #059669;
}

.outline.variant-warning {
  background: transparent;
  color: #d97706;
  border: 1px solid #d97706;
}

.outline.variant-danger {
  background: transparent;
  color: #dc2626;
  border: 1px solid #dc2626;
}

/* Loading animation */
.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 