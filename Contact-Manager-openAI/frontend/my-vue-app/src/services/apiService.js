// API Service for OpenAI integration
export const apiService = {
  // Configuration
  config: {
    apiKey: getenv('app.openai_key'), // Use environment variable instead of hardcoded key
    model: 'gpt-4',
    maxTokens: 2000,
    baseURL: 'https://api.openai.com/v1'
  },

  // Set API Key
  setApiKey(key) {
    this.config.apiKey = key
    localStorage.setItem('openai_api_key', key)
  },

  // Send message to OpenAI
  async sendMessage(message, conversationHistory = []) {
    if (!this.config.apiKey) {
      throw new Error('OpenAI API key not configured')
    }

    // Prepare messages for API
    const messages = [
      {
        role: 'system',
        content: 'You are a helpful AI assistant for customer support. You can help with server management, troubleshooting, and various technical tasks.'
      },
      ...conversationHistory.map(msg => ({
        role: msg.role,
        content: msg.content
      })),
      {
        role: 'user',
        content: message
      }
    ]

    // Available functions for the AI
    const functions = [
      {
        name: 'systemloadavg',
        description: 'Get system load average and performance metrics',
        parameters: {
          type: 'object',
          properties: {},
          required: []
        }
      },
      {
        name: 'get_disk_usage',
        description: 'Check disk usage and storage capacity',
        parameters: {
          type: 'object',
          properties: {
            path: {
              type: 'string',
              description: 'Path to check disk usage for',
              default: '/'
            }
          },
          required: []
        }
      },
      {
        name: 'get_domain_info',
        description: 'Retrieve domain configuration and DNS information',
        parameters: {
          type: 'object',
          properties: {
            domain: {
              type: 'string',
              description: 'Domain name to check'
            }
          },
          required: ['domain']
        }
      },
      {
        name: 'check_service_status',
        description: 'Check the status of system services',
        parameters: {
          type: 'object',
          properties: {
            service: {
              type: 'string',
              description: 'Service name to check'
            }
          },
          required: ['service']
        }
      }
    ]

    const requestBody = {
      model: this.config.model,
      messages: messages,
      max_tokens: this.config.maxTokens,
      temperature: 0.7,
      functions: functions,
      function_call: 'auto'
    }

    try {
      const response = await fetch(`${this.config.baseURL}/chat/completions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.config.apiKey}`
        },
        body: JSON.stringify(requestBody)
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.error?.message || `HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      console.error('API Service Error:', error)
      throw error
    }
  },

  // Execute system functions (simulated)
  async executeFunction(functionName, parameters = {}) {
    // Simulate function execution with realistic responses
    const responses = {
      systemloadavg: {
        loadavg: [0.25, 0.35, 0.42],
        uptime: '15 days, 3:45',
        processes: {
          total: 247,
          running: 3,
          sleeping: 244
        },
        memory: {
          total: '16GB',
          used: '8.2GB',
          free: '7.8GB',
          cached: '2.1GB'
        }
      },
      get_disk_usage: {
        filesystem: '/dev/sda1',
        size: '100GB',
        used: '45GB',
        available: '55GB',
        usage_percent: '45%',
        mount_point: parameters.path || '/'
      },
      get_domain_info: {
        domain: parameters.domain || 'example.com',
        ip: '192.168.1.100',
        nameservers: ['ns1.example.com', 'ns2.example.com'],
        mx_records: ['mail.example.com'],
        status: 'active',
        expiry: '2025-12-31'
      },
      check_service_status: {
        service: parameters.service || 'apache2',
        status: 'running',
        pid: 1234,
        uptime: '5 days, 2:30',
        memory_usage: '45MB'
      }
    }

    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 2000))

    return responses[functionName] || { error: 'Function not found' }
  },

  // Format responses for display
  formatResponse(functionName, data) {
    switch (functionName) {
      case 'systemloadavg':
        return `System Load Average: ${data.loadavg.join(', ')}\nUptime: ${data.uptime}\nMemory: ${data.memory.used}/${data.memory.total} used`
      
      case 'get_disk_usage':
        return `Disk Usage: ${data.used}/${data.size} (${data.usage_percent})\nAvailable: ${data.available}\nMount: ${data.mount_point}`
      
      case 'get_domain_info':
        return `Domain: ${data.domain}\nIP: ${data.ip}\nStatus: ${data.status}\nExpiry: ${data.expiry}`
      
      case 'check_service_status':
        return `Service: ${data.service}\nStatus: ${data.status}\nPID: ${data.pid}\nUptime: ${data.uptime}`
      
      default:
        return JSON.stringify(data, null, 2)
    }
  }
} 