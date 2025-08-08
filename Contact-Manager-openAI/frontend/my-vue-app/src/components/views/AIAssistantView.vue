<!-- src/App.vue -->
<template>
  <!-- <div v-if="view === 'contacts'" >
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
  </div> -->

  <!-- <div v-else-if="view === 'read'">
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
  </div> -->

  <!-- <div v-else-if="view === 'edit'">
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
  </div> -->

  <!-- <div v-else-if="view === 'new'">
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
  </div> -->

  <div v-if="view === 'AI'" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-height: 100vh; height: 100vh; width: 100vw; margin: 0; padding: 0; position: fixed; top: 0; left: 0; overflow-y: auto;">
    <!-- Header -->
    <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);">
      <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);">
            <span style="font-size: 18px; color: white;">🤖</span>
          </div>
          <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; letter-spacing: -0.025em;">Agent Dashboard</h1>
            <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">AI-Powered Customer Support</p>
          </div>
        </div>
        <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 6px 16px; border-radius: 24px; font-size: 11px; font-weight: 600; letter-spacing: 0.025em; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">ACTIVE SESSION</div>
      </div>
      <div style="display: flex; align-items: center; gap: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; background: rgba(16, 185, 129, 0.1); padding: 10px 18px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2);">
          <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
          <span style="color: #047857; font-weight: 600; font-size: 13px;">System Online</span>
        </div>
        <button style="background: rgba(71, 85, 105, 0.1); border: 1px solid rgba(71, 85, 105, 0.2); color: #475569; font-size: 16px; cursor: pointer; padding: 10px; border-radius: 10px; transition: all 0.2s ease;" @mouseover="this.style.background='rgba(71, 85, 105, 0.15)'" @mouseout="this.style.background='rgba(71, 85, 105, 0.1)'">⚙️</button>
        <button @click="$router.push('/dashboard')" style="background: rgba(71, 85, 105, 0.1); border: 1px solid rgba(71, 85, 105, 0.2); color: #475569; font-size: 16px; cursor: pointer; padding: 10px; border-radius: 10px; transition: all 0.2s ease;">← Back</button>
      </div>
    </div>

    <!-- Main Content Container -->
    <div style="padding: 32px; display: flex; gap: 32px; height: calc(100vh - 340px); overflow: hidden;">
      <!-- Left Column - Agent Chat -->
      <div style="flex: 1; display: flex; flex-direction: column; min-width: 0;">
        <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; flex: 1; border: 1px solid rgba(226,232,240,0.5); overflow: hidden;">
          <!-- Agent Chat Header -->
          <div style="display: flex; align-items: center; gap: 16px; padding: 24px 28px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); flex-shrink: 0;">
            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6, #1e40af); border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(59, 130, 246, 0.3);">
              <span style="color: white; font-size: 16px;">💬</span>
            </div>
            <div style="flex: 1;">
              <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">AI Assistant</h2>
              <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">Ready to help with support requests</p>
            </div>
            <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid rgba(59, 130, 246, 0.2);">READY</div>
          </div>

          <!-- Priority and Category Display -->
          <div v-if="priority" style="padding: 20px 28px; background: linear-gradient(90deg, rgba(245, 158, 11, 0.08), rgba(245, 158, 11, 0.03)); border-bottom: 1px solid rgba(245, 158, 11, 0.15); flex-shrink: 0;">
            <div style="display: flex; gap: 12px; align-items: center;">
              <span style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; box-shadow: 0 3px 8px rgba(245,158,11,0.3);">
                ⚠️ Priority: {{ priority }}
              </span>
              <span style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; box-shadow: 0 3px 8px rgba(139,92,246,0.3);">
                📁 Category : {{category}}
              </span>
            </div>
          </div>

          <!-- Chat Messages Area -->
          <div style="flex: 1; padding: 28px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.4)); min-height: 0;">
            <!-- Welcome message when no chat -->
            <div v-if="aiResponse.length === 0 && !loading" style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; color: #64748b;">
              <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <span style="font-size: 32px; color: #64748b;">🤖</span>
              </div>
              <div style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #1e293b;">AI Assistant Ready</div>
              <div style="font-size: 15px; color: #64748b; text-align: center; max-width: 360px; line-height: 1.6; margin-bottom: 24px;">Start a conversation or submit a ticket ID to begin your support session</div>
              <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 600; border: 1px solid rgba(59, 130, 246, 0.2);">Server Support</div>
                <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 600; border: 1px solid rgba(16, 185, 129, 0.2);">Ticket Lookup</div>
                <div style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 600; border: 1px solid rgba(139, 92, 246, 0.2);">Issue Resolution</div>
              </div>
            </div>

            <!-- Dynamic AI Responses with enhanced styling -->
            <template v-for="(resp, index) in aiResponse" :key="index">
              <!-- <div v-if="shouldShow(resp)" > -->
              <div :style="getProfessionalChatStyle(resp)" class="message-bubble">
                <div class="message-content">
                  <!-- <div class="message-text"> {{resp}}</div> -->
                  <div class="message-text" v-html="formatApiResponse(resp)"></div>
                  <div class="message-time">{{ getMessageTime() }}</div>
                </div>
              </div>
            </template>

            <!-- Enhanced Loading indicator with typing animation -->
            <div v-if="loading" style="display: flex; align-items: center; justify-content: flex-start; padding: 20px 0;">
              <div style="display: flex; align-items: center; gap: 16px; background: rgba(59, 130, 246, 0.1); padding: 20px 24px; border-radius: 16px; max-width: 80%; border: 1px solid rgba(59, 130, 246, 0.2); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);">
                <div class="typing-indicator">
                  <span></span>
                  <span></span>
                  <span></span>
                </div>
                <span style="color: #3b82f6; font-size: 14px; font-weight: 600;">Processing{{ dots }}</span>
              </div>
            </div>

            <!-- Auto-scroll helper -->
            <div ref="chatBottom"></div>
          </div>

          <!-- Enhanced Chat Input Area -->
          <div style="padding: 20px 24px; border-top: 1px solid rgba(229, 231, 235, 0.8); background: rgba(255,255,255,0.95); flex-shrink: 0;">
            <!-- Input suggestions when empty -->
            <div v-if="!userMessage.trim() && aiResponse.length === 0" style="margin-bottom: 12px;">
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button @click="userMessage = 'Can you help me troubleshoot server connectivity issues?'" style="background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 6px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Server Issues</button>
                <button @click="userMessage = 'What are the current system performance metrics?'" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Performance</button>
                <button @click="userMessage = 'I need assistance with user account management'" style="background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 6px; padding: 6px 12px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Account Help</button>
              </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px; position: relative;">
              <div style="flex: 1; position: relative;">
                <textarea
                  v-model="userMessage"
                  placeholder="Type your message... (Shift+Enter for new line)"
                  style="flex: 1; min-height: 44px; max-height: 120px; padding: 14px 16px; border: 2px solid rgba(209, 213, 219, 0.8); border-radius: 8px; resize: vertical; font-family: inherit; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.98); width: 100%; box-sizing: border-box;"
                  @focus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
                  @blur="this.style.borderColor='rgba(209, 213, 219, 0.8)'; this.style.boxShadow='none'"
                  @keydown="handleKeydown"
                ></textarea>
                <div v-if="userMessage.length > 0" style="position: absolute; bottom: 8px; right: 12px; font-size: 10px; color: #9ca3af; background: rgba(255,255,255,0.9); padding: 2px 6px; border-radius: 4px;">
                  {{ userMessage.length }}/500
                </div>
              </div>
              <button 
                @click="aiClick"
                :disabled="!userMessage.trim() || loading"
                style="background: linear-gradient(45deg, #2563eb, #1d4ed8); color: white; border: none; border-radius: 8px; padding: 14px 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(37,99,235,0.3); font-weight: 600; min-width: 80px;"
                :style="{ opacity: (!userMessage.trim() || loading) ? '0.6' : '1', cursor: (!userMessage.trim() || loading) ? 'not-allowed' : 'pointer' }"
                @mouseover="if (userMessage.trim() && !loading) { this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)' }"
                @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'"
              >
                <span v-if="loading">⏳</span>
                <span v-else>Send →</span>
              </button>
            </div>

            <!-- Enhanced action buttons with icons -->
            <div style="display: flex; gap: 12px; align-items: center; justify-content: space-between;">
              <div style="display: flex; gap: 8px;">
                <button @click="aiClear" style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; padding: 8px 16px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Clear</button>
                <button @click="view = 'clientInput'" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 6px; padding: 8px 16px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Client Info</button>
                <button @click="exportChat" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; padding: 8px 16px; font-size: 12px; cursor: pointer; transition: all 0.2s ease; font-weight: 500;">Export</button>
              </div>
              
              <!-- Message count and status -->
              <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #6b7280;">
                <span>{{ aiResponse.filter(shouldShow).length }} messages</span>
                <div style="width: 6px; height: 6px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></div>
                <span>Connected</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Action Queue -->
      <div style="flex: 1; display: flex; flex-direction: column;">
        <div style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; flex: 1; border: 1px solid rgba(226,232,240,0.5); overflow: hidden;">
          <!-- Action Queue Header -->
          <div style="display: flex; align-items: center; gap: 16px; padding: 24px 28px; border-bottom: 1px solid rgba(226, 232, 240, 0.6); flex-shrink: 0;">
            <div v-if="pending_functions.length > 0" style="width: 32px; height: 32px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; font-weight: 700; box-shadow: 0 3px 8px rgba(239,68,68,0.4); animation: pulse 2s infinite;">{{ pending_functions.length }}</div>
            <div v-else style="width: 32px; height: 32px; background: linear-gradient(135deg, #71717a, #52525b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; font-weight: 700; box-shadow: 0 3px 8px rgba(113,113,122,0.3);">0</div>
            <div style="flex: 1;">
              <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;">Action Queue</h2>
              <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">Pending system requests</p>
            </div>
            <div v-if="pending_functions.length > 0" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; box-shadow: 0 2px 8px rgba(239,68,68,0.3);">{{ pending_functions.length }} PENDING</div>
            <div v-else style="background: rgba(113, 113, 122, 0.1); color: #71717a; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid rgba(113, 113, 122, 0.2);">CLEAR</div>
          </div>

          <!-- Action Queue Items -->
          <div style="flex: 1; padding: 28px; overflow-y: auto; background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.4)); min-height: 0;">
            <!-- Show message when no pending functions -->
            <div v-if="pending_functions.length === 0" style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; color: #64748b;">
              <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #e2e8f0, #cbd5e1); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <span style="font-size: 24px; color: #64748b;">✅</span>
              </div>
              <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: #1e293b;">Queue Clear</div>
              <div style="font-size: 14px; color: #64748b;">No pending actions</div>
            </div>

            <!-- Dynamic pending functions -->
            <div v-for="(func, index) in pending_functions" :key="index" style="background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04); transition: all 0.2s ease;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.06)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04)'">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 12px;">
                  <span style="font-size: 16px;">⚡</span>
                  {{ func.functionName }}
                </h3>
                <div :style="func.tag === 'Information Request' ? 'background: linear-gradient(135deg, #3b82f6, #1e40af); color: white; padding: 6px 12px; border-radius: 12px; font-size: 10px; font-weight: 700; box-shadow: 0 2px 6px rgba(59,130,246,0.3);' : 'background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 6px 12px; border-radius: 12px; font-size: 10px; font-weight: 700; box-shadow: 0 2px 6px rgba(245,158,11,0.3);'">
                  {{ func.tag || 'Information Request' }}
                </div>
              </div>
              <p style="margin: 0 0 20px 0; font-size: 14px; color: #475569; line-height: 1.6;">
                {{ func.description}}
              </p>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                  <span style="font-size: 12px;">🕐</span>
                  {{ new Date().toLocaleTimeString() }}
                </span>
                <div style="display: flex; gap: 12px;">
                  <button @click="aiReject(index)" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; border-radius: 10px; padding: 10px 20px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 3px 8px rgba(239,68,68,0.3);" @mouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(239,68,68,0.4)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 8px rgba(239,68,68,0.3)'">Reject</button>
                  <button @click="aiProceed(index)" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 10px; padding: 10px 20px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 3px 8px rgba(16,185,129,0.3);" @mouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.4)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 8px rgba(16,185,129,0.3)'">Proceed</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Stats Section -->
    <div style="padding: 0 32px 24px; display: flex; gap: 32px;">
      <!-- Quick Ticket -->
      <div style="flex: 1; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05); padding: 28px; border: 1px solid rgba(226,232,240,0.5);">
        <h3 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3);">
              <span style="font-size: 16px; color: white;">🎫</span>
            </div>
            <div>
              <div style="font-size: 18px; font-weight: 700; color: #0f172a;">Quick Ticket</div>
              <div style="font-size: 13px; color: #64748b; font-weight: 500;">Submit or lookup tickets</div>
            </div>
          </div>
          <!-- Mode toggle buttons -->
          <div style="display: flex; gap: 4px; background: rgba(248, 250, 252, 0.8); padding: 4px; border-radius: 12px; border: 1px solid rgba(226, 232, 240, 0.8);">
            <button
              @click="ticketMode = 'submit'" 
              :style="{ 
                background: ticketMode === 'submit' ? 'linear-gradient(135deg, #10b981, #059669)' : 'transparent', 
                color: ticketMode === 'submit' ? 'white' : '#64748b', 
                border: 'none', 
                padding: '8px 16px', 
                borderRadius: '8px', 
                fontSize: '12px', 
                fontWeight: '700', 
                cursor: 'pointer', 
                transition: 'all 0.2s ease',
                boxShadow: ticketMode === 'submit' ? '0 2px 6px rgba(16,185,129,0.3)' : 'none'
              }"
            >
              Submit
            </button>
            <button 
              @click="ticketMode = 'lookup'" 
              :style="{ 
                background: ticketMode === 'lookup' ? 'linear-gradient(135deg, #3b82f6, #1e40af)' : 'transparent', 
                color: ticketMode === 'lookup' ? 'white' : '#64748b', 
                border: 'none', 
                padding: '8px 16px', 
                borderRadius: '8px', 
                fontSize: '12px', 
                fontWeight: '700', 
                cursor: 'pointer', 
                transition: 'all 0.2s ease',
                boxShadow: ticketMode === 'lookup' ? '0 2px 6px rgba(59,130,246,0.3)' : 'none'
              }"
            >
              Lookup
            </button>
          </div>
        </h3>

        <!-- Submit Mode -->
        <div v-if="ticketMode === 'submit'">
          <div style="display: flex; gap: 12px;">
            <input
              v-model="ticketIdInput"
              placeholder="Enter Ticket ID"
              style="flex: 1; padding: 14px 16px; border: 2px solid rgba(209, 213, 219, 0.8); border-radius: 8px; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.95);"
              @focus="this.style.borderColor='#059669'; this.style.boxShadow='0 0 0 3px rgba(5,150,105,0.1)'"
              @blur="this.style.borderColor='rgba(209, 213, 219, 0.8)'; this.style.boxShadow='none'"
            />
            <button
              @click="submitTicketId"
              style="background: linear-gradient(45deg, #059669, #047857); color: white; border: none; border-radius: 8px; padding: 14px 24px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(5,150,105,0.3);"
              @mouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(5,150,105,0.4)'"
              @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(5,150,105,0.3)'"
            >
              Submit
            </button>
          </div>
          
          <!-- Placeholder for submit mode -->
          <div style="background: rgba(240, 253, 244, 0.8); border: 2px dashed rgba(34, 197, 94, 0.4); border-radius: 8px; padding: 24px; margin-top: 12px; text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
              <div style="width: 48px; height: 48px; background: linear-gradient(45deg, rgba(5, 150, 105, 0.1), rgba(5, 150, 105, 0.05)); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(5, 150, 105, 0.2);">
                <span style="font-size: 20px; opacity: 0.7;">🎫</span>
              </div>
              <div style="color: #047857; font-size: 14px; font-weight: 500; margin-bottom: 4px;">Quick Submit</div>
              <div style="color: #6b7280; font-size: 12px; line-height: 1.4; max-width: 250px;">Enter a ticket ID above to quickly load it into the AI chat for immediate assistance</div>
            </div>
          </div>
        </div>

        <!-- Lookup Mode -->
        <div v-if="ticketMode === 'lookup'">
          <div style="display: flex; gap: 12px; margin-bottom: 16px;">
            <input
              v-model="ticketIdInput"
              placeholder="Enter Ticket ID to lookup"
              style="flex: 1; padding: 14px 16px; border: 2px solid rgba(209, 213, 219, 0.8); border-radius: 8px; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.95);"
              @focus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
              @blur="this.style.borderColor='rgba(209, 213, 219, 0.8)'; this.style.boxShadow='none'"
              @keyup.enter="lookupTicket"
            />
            <button
              @click="lookupTicket"
              :disabled="!ticketIdInput.trim() || lookupLoading"
              style="background: linear-gradient(45deg, #2563eb, #1d4ed8); color: white; border: none; border-radius: 8px; padding: 14px 24px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(37,99,235,0.3);"
              :style="{ opacity: (!ticketIdInput.trim() || lookupLoading) ? '0.6' : '1', cursor: (!ticketIdInput.trim() || lookupLoading) ? 'not-allowed' : 'pointer' }"
              @mouseover="if (ticketIdInput.trim() && !lookupLoading) { this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)' }"
              @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'"
            >
              <span v-if="lookupLoading">🔍</span>
              <span v-else>Lookup</span>
            </button>
          </div>

          <!-- Placeholder when no ticket info is shown -->
          <div v-if="!ticketInfo && !lookupError" style="background: rgba(249, 250, 251, 0.8); border: 2px dashed rgba(209, 213, 219, 0.6); border-radius: 8px; padding: 24px; text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
              <div style="width: 48px; height: 48px; background: linear-gradient(45deg, rgba(37, 99, 235, 0.1), rgba(37, 99, 235, 0.05)); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(37, 99, 235, 0.2);">
                <span style="font-size: 20px; opacity: 0.7;">🔍</span>
              </div>
              <div style="color: #6b7280; font-size: 14px; font-weight: 500; margin-bottom: 4px;">Ticket Lookup</div>
              <div style="color: #9ca3af; font-size: 12px; line-height: 1.4; max-width: 250px;">Enter a ticket ID above to search and view detailed ticket information</div>
            </div>
          </div>

          <!-- Ticket Information Display -->
          <div v-if="ticketInfo" style="background: rgba(239, 246, 255, 0.8); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 16px; margin-top: 12px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
              <span style="font-size: 16px;">🎫</span>
              <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1e40af;">Ticket Details</h4>
              <div style="margin-left: auto; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                {{ ticketInfo.status || 'ACTIVE' }}
              </div>
            </div>
            
            <div style="display: grid; gap: 8px;">
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(59, 130, 246, 0.2);">
                <span style="font-weight: 500; color: #374151; font-size: 13px;">Ticket ID:</span>
                <span style="font-family: 'Monaco', monospace; color: #1e40af; font-size: 13px; font-weight: 600;">#{{ ticketInfo.id || ticketIdInput }}</span>
              </div>
              
              <div v-if="ticketInfo.priority" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(59, 130, 246, 0.2);">
                <span style="font-weight: 500; color: #374151; font-size: 13px;">Priority:</span>
                <span :style="{ 
                  color: ticketInfo.priority === 'High' ? '#dc2626' : ticketInfo.priority === 'Medium' ? '#f59e0b' : '#059669', 
                  fontWeight: '600',
                  fontSize: '13px'
                }">
                  {{ ticketInfo.priority }}
                </span>
              </div>
              
              <div v-if="ticketInfo.category" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(59, 130, 246, 0.2);">
                <span style="font-weight: 500; color: #374151; font-size: 13px;">Category:</span>
                <span style="color: #6b7280; font-size: 13px;">{{ ticketInfo.category }}</span>
              </div>
              
              <div v-if="ticketInfo.summary" style="display: flex; flex-direction: column; gap: 4px; padding: 6px 0;">
                <span style="font-weight: 500; color: #374151; font-size: 13px;">Summary:</span>
                <span style="color: #6b7280; font-size: 13px; line-height: 1.4;">{{ ticketInfo.summary }}</span>
              </div>
            </div>
            
            <!-- Action buttons -->
            <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(59, 130, 246, 0.2);">
              <button 
                @click="submitTicketId"
                style="background: linear-gradient(45deg, #2563eb, #1d4ed8); color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; flex: 1;"
                @mouseover="this.style.transform='translateY(-1px)'"
                @mouseout="this.style.transform='translateY(0)'"
              >
                Use for Chat
              </button>
              <button 
                @click="clearTicketInfo"
                style="background: rgba(107, 114, 128, 0.1); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.2); border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                @mouseover="this.style.transform='translateY(-1px)'"
                @mouseout="this.style.transform='translateY(0)'"
              >
                Clear
              </button>
            </div>
          </div>

          <!-- Error display -->
          <div v-if="lookupError" style="background: rgba(254, 242, 242, 0.8); border: 1px solid rgba(252, 165, 165, 0.5); border-radius: 8px; padding: 12px; margin-top: 12px; color: #dc2626; font-size: 13px;">
            {{ lookupError }}
          </div>
        </div>
      </div>

      <!-- System Stats -->
      <div style="flex: 1; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05); padding: 28px; border: 1px solid rgba(226,232,240,0.5);">
        <h3 style="margin: 0 0 28px 0; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 16px;">
          <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #1e40af); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);">
            <span style="font-size: 20px; color: white;">📊</span>
          </div>
          <div>
            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">System Analytics</div>
            <div style="font-size: 13px; color: #64748b; font-weight: 500;">Performance metrics & insights</div>
          </div>
        </h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
          <!-- Tokens Used -->
          <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02)); border: 1px solid rgba(59, 130, 246, 0.12); border-radius: 12px; padding: 20px; transition: all 0.2s ease;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(59, 130, 246, 0.15)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="color: #64748b; font-size: 11px; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.1em;">Total Tokens</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px;">{{ tokens && tokens.total_tokens ? tokens.total_tokens.toLocaleString() : '0' }}</div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 6px; height: 6px; background: #3b82f6; border-radius: 50%;"></div>
              <span style="color: #64748b; font-size: 11px; font-weight: 600;">Active Usage</span>
            </div>
          </div>
          
          <!-- API Calls -->
          <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02)); border: 1px solid rgba(16, 185, 129, 0.12); border-radius: 12px; padding: 20px; transition: all 0.2s ease;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(16, 185, 129, 0.15)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="color: #64748b; font-size: 11px; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.1em;">API Calls</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px;">{{ Array.isArray(apiResponse) ? apiResponse.length : '0' }}</div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 6px; height: 6px; background: #10b981; border-radius: 50%;"></div>
              <span style="color: #64748b; font-size: 11px; font-weight: 600;">Requests Made</span>
            </div>
          </div>
          
          <!-- Prompt Tokens -->
          <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.06), rgba(139, 92, 246, 0.02)); border: 1px solid rgba(139, 92, 246, 0.12); border-radius: 12px; padding: 20px; transition: all 0.2s ease;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(139, 92, 246, 0.15)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="color: #64748b; font-size: 11px; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.1em;">Input Tokens</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px;">{{ tokens && tokens.prompt_tokens ? tokens.prompt_tokens.toLocaleString() : '0' }}</div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 6px; height: 6px; background: #8b5cf6; border-radius: 50%;"></div>
              <span style="color: #64748b; font-size: 11px; font-weight: 600;">Prompts Sent</span>
            </div>
          </div>
          
          <!-- Completion Tokens -->
          <div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.06), rgba(245, 158, 11, 0.02)); border: 1px solid rgba(245, 158, 11, 0.12); border-radius: 12px; padding: 20px; transition: all 0.2s ease;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(245, 158, 11, 0.15)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <div style="color: #64748b; font-size: 11px; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.1em;">Response Tokens</div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px;">{{ tokens && tokens.completion_tokens ? tokens.completion_tokens.toLocaleString() : '0' }}</div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <div style="width: 6px; height: 6px; background: #f59e0b; border-radius: 50%;"></div>
              <span style="color: #64748b; font-size: 11px; font-weight: 600;">AI Generated</span>
            </div>
          </div>
        </div>
        
        <!-- Customer Satisfaction Section -->
        <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.08), rgba(34, 197, 94, 0.03)); border: 1px solid rgba(34, 197, 94, 0.15); border-radius: 12px; padding: 24px;">
          <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
              <div style="color: #64748b; font-size: 11px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.1em;">Customer Satisfaction</div>
              <div style="display: flex; align-items: center; gap: 16px;">
                <span style="font-size: 32px; font-weight: 800; color: #0f172a;">{{ rating}}</span>
                <div style="display: flex; gap: 4px;">
                  <span
                    v-for="i in Number(rating)"
                    :key="'star-' + i"
                    style="color: #fbbf24; font-size: 18px; filter: drop-shadow(0 1px 3px rgba(251, 191, 36, 0.4));"
                  >
                    ⭐
                  </span>
                </div>
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; background: rgba(34, 197, 94, 0.1); padding: 8px 16px; border-radius: 24px; border: 1px solid rgba(34, 197, 94, 0.2);">
              <span style="color: #16a34a; font-size: 14px;">📈</span>
              <span style="color: #0f5132; font-size: 12px; font-weight: 700;">{{ satisfaction}}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Client Info -->
      <div style="flex: 1; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 6px rgba(0,0,0,0.05); padding: 28px; border: 1px solid rgba(226,232,240,0.5);">
        <h3 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 16px;">
          <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(139, 92, 246, 0.3);">
            <span style="font-size: 16px; color: white;">👤</span>
          </div>
          <div>
            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">Client Information</div>
            <div style="font-size: 13px; color: #64748b; font-weight: 500;">Customer & system details</div>
          </div>
        </h3>
        
        <div style="display: flex; flex-direction: column; gap: 20px;">
          <div style="display: flex; flex-direction: column;">
            <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Client ID</label>
            <input
              v-model="clientId"
              type="text"
              placeholder="Enter Client ID"
              style="padding: 14px 16px; border: 2px solid rgba(226, 232, 240, 0.8); border-radius: 12px; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.9); font-weight: 500;"
              @focus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.1)'"
              @blur="this.style.borderColor='rgba(226, 232, 240, 0.8)'; this.style.boxShadow='none'"
            />
          </div>
          
          <div style="display: flex; flex-direction: column;">
            <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Product/Service</label>
            <input
              v-model="product"
              type="text"
              placeholder="Enter Product or Service"
              style="padding: 14px 16px; border: 2px solid rgba(226, 232, 240, 0.8); border-radius: 12px; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.9); font-weight: 500;"
              @focus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.1)'"
              @blur="this.style.borderColor='rgba(226, 232, 240, 0.8)'; this.style.boxShadow='none'"
            />
          </div>
          
          <div style="display: flex; flex-direction: column;">
            <label style="font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Server</label>
            <input
              v-model="server"
              type="text"
              placeholder="Enter Server"
              style="padding: 14px 16px; border: 2px solid rgba(226, 232, 240, 0.8); border-radius: 12px; font-size: 14px; transition: all 0.2s ease; background: rgba(255,255,255,0.9); font-weight: 500;"
              @focus="this.style.borderColor='#8b5cf6'; this.style.boxShadow='0 0 0 3px rgba(139,92,246,0.1)'"
              @blur="this.style.borderColor='rgba(226, 232, 240, 0.8)'; this.style.boxShadow='none'"
            />
          </div>
          
          <button
            @click="handleClientSubmit"
            :disabled="!clientId.trim() && !product.trim() && !server.trim()"
            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; border: none; border-radius: 12px; padding: 14px 24px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(139,92,246,0.4); margin-top: 12px; font-size: 14px;"
            :style="{ opacity: (!clientId.trim() && !product.trim() && !server.trim()) ? '0.6' : '1', cursor: (!clientId.trim() && !product.trim() && !server.trim()) ? 'not-allowed' : 'pointer' }"
            @mouseover="if (clientId.trim() || product.trim() || server.trim()) { this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(139,92,246,0.5)' }"
            @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(139,92,246,0.4)'"
          >
            Save Client Information
          </button>
        </div>
      </div>
    </div>

    <!-- Navigation buttons -->
    <div style="padding: 0 32px 24px; display: flex; gap: 20px;">
      <!-- <button @click="view = 'contacts'" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; border: none; border-radius: 12px; padding: 14px 28px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(107,114,128,0.4); display: flex; align-items: center; gap: 8px;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(107,114,128,0.5)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(107,114,128,0.4)'">
        <span>🏠</span>
        <span>Back to Contacts</span>
      </button> -->
      <button @click="view = 'session'" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; border: none; border-radius: 12px; padding: 14px 28px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(107,114,128,0.4); display: flex; align-items: center; gap: 8px;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(107,114,128,0.5)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(107,114,128,0.4)'">
        <span>👁️</span>
        <span>View Session</span>
      </button>
      <button @click="aiLog()" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white; border: none; border-radius: 12px; padding: 14px 28px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(107,114,128,0.4); display: flex; align-items: center; gap: 8px;" @mouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(107,114,128,0.5)'" @mouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(107,114,128,0.4)'">
        <span>📋</span>
        <span>View History</span>
      </button>
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

  <div v-else-if="view === 'clientInput'" style="display: flex; flex-direction: column; align-items: center; padding: 20px;">
  <p style="font-size: 40px; margin-bottom: 20px;"> 
    Client Info Input:
  </p>

  <form @submit.prevent="handleClientSubmit" style="display: flex; flex-direction: column; width: 100%; max-width: 400px; gap: 15px;">
    
    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Client ID:
      <input
        v-model="clientId"
        type="text"
        placeholder="Enter Client ID"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Product/Service:
      <input
        v-model="product"
        type="text"
        placeholder="Enter Product or Service"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <label style="display: flex; flex-direction: column; font-weight: 500;">
      Server:
      <input
        v-model="server"
        type="text"
        placeholder="Enter Server"
        style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-top: 5px;"
      />
    </label>

    <button
      type="submit"
      style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"
    >
      Submit
    </button>
  </form>

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
      view: 'AI',
      selectedContact: {id: '', name: '', email: '', phone: ''},
      selectedInd: null,
      aiResponse: [],
      userMessage: '',
      tokens: {},
      apiResponse: [],
      messageHistory: '',
      historyLog: '',
      pending_functions: [],
      acknowledged_functions: [],
      ticketIdInput: '',
      ticketMessage: '',

      loading: false,
      dots: '',
      intervalId: null,

      clientId: '',
      product: '',
      server: '',

      satisfaction: '',
      rating: '',
      priority: '',
      categroy: '',

      // Ticket lookup functionality
      ticketMode: 'submit', // 'submit' or 'lookup'
      ticketInfo: null,
      lookupLoading: false,
      lookupError: '',

      id: '',
      summary: ''
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

    startLoadingDots() {
  if (this.intervalId) clearInterval(this.intervalId);
  this.loading = true;
  let i = 0;
  this.intervalId = setInterval(() => {
    this.dots = '.'.repeat(i % 4);
    this.$forceUpdate(); // ← force re-render
    i++;
  }, 400);
},

stopLoadingDots() {
  clearInterval(this.intervalId);
  this.dots = '';
  this.loading = false;
},

shouldShow(resp) {
  if (Array.isArray(resp)){
    return false;
  }
    return (
      resp.startsWith('AI:') ||
      resp.startsWith('CATEGORY:') ||
      resp.startsWith('AGENT:') ||
      resp.startsWith('TICKET')
    );
  },
  
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
  } else if (upperText.startsWith('TICKET')) {
    style.color = '#525050';
    style.textAlign = 'left';
    style.marginLeft = 'auto';
    style.backgroundColor = '#f2f2f2';
  } else if (upperText.startsWith('AI:')) {
    style.color = '#1533ad';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = '#e6f0ff';
  } else if (upperText.startsWith('CATEGORY:')) {
    style.color = '#a29da6';
    style.textAlign = 'left';
    style.marginRight = 'auto';
    style.backgroundColor = '#f6f2fa';
  } 
  //else {
  //   style.color = 'white';
  //   style.textAlign = 'left';
  //   style.marginRight = 'auto';
  //   style.backgroundColor = 'white';
  // }

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
      // console.log('Sending API_response:', JSON.parse(JSON.stringify(this.apiResponse)));
      const trimmedMessage = this.userMessage.trim();
      if (trimmedMessage){
        if (this.shouldShow(trimmedMessage)){
          this.aiResponse.push(trimmedMessage);
        }
      }

      if (this.summary != ""){
        this.userMessage = this.summary;
      }
      
      this.startLoadingDots();

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
            // this.aiResponse = [];
            // this.tokens = {};
            // this.apiResponse = [];
          }
          else{
            if (data.response != 'No Response was Generated'){
              this.aiResponse.push(data.response);
            }
            if (data.category != ''){
                   this.aiResponse.push(data.category);
            }

            if (data.satisfaction != ''){
              this.satisfaction = data.satisfaction;
              this.rating = data.rating.includes('.') ? str.split('.')[1] : data.rating;
            }
  
            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
            this.summary = "";
            // this.pending_functions.push(...data.pending_functions);
            this.pending_functions = data.pending_functions;
       
          
          }
          this.stopLoadingDots();
          // console.log('tokens as string:', this.tokens);
    })
    this.userMessage = '';
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
      this.ticketMessage = '';
      this.priority = '';
      this.category = '';
      this.satisfaction = '';
      this.rating = '';
      this.summary = '';
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

            this.aiResponse.push(data.API_response);

            this.tokens = data.tokens_used;
            this.apiResponse = data.API_response;
            this.userMessage = data.user_message;
            
            // Process the response without triggering scroll
            this.acknowledged_functions = [];
            
            // Only call aiClick if there's a user message to process
            if (this.userMessage && this.userMessage.trim()) {
              this.aiClick();
            }
          }
      })
    },

    aiReject(index) {
      fetch('http://localhost:8080/contacts/ai/reject',{
        method: 'POST',
        credentials: 'include'
      }).then(response => response.json())  
      .then(data => {
        this.userMessage = data.user_message;
        this.pending_functions.splice(index, 1);
        this.aiClick();
      })
    },

    submitTicketId(){
      // this.userMessage = `TICKETID: ${this.ticketIdInput}`;
      fetch('http://localhost:8080/contacts/ai/ticketID',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify ({'message': this.ticketIdInput})
      }).then(response => response.json())  
        .then(data => {
          // this.aiResponse.push(data.summary);

          this.userMessage = data.summary;
          this.priority = data.priority;
          this.category = data.category;
          this.rating = data.rating;

          if (data.response && data.response !== "") {
            this.aiResponse.push(data.response);

            const trimmedMessage = this.userMessage.trim();
            if (trimmedMessage){
              if (this.shouldShow(trimmedMessage)){
                this.aiResponse.push(trimmedMessage);
              }
            }

            this.userMessage = "AGENT: You may proceed";
            this.summary = data.summary;

            // this.historyAdd = "AGENT: Yes, I can confirm this is a valid client";
          }
          
          else {
            this.aiClick();
          }
          

          // this.userMessage = data.summary;
          // this.priority = data.priority;
          // this.aiClick();
      })
      this.ticketIdInput = '';
    },

    handleClientSubmit(){
      fetch('http://localhost:8080/contacts/ai/client',{
        method: 'POST',
        credentials: 'include',
        body:JSON.stringify ({'clientID': this.clientId, 'product': this.product, 'server': this.server})
      }) 
      clientId = '';
      product =  '';
      server = '';
      this.view = "AI";
    },

    getNewChatStyle(text) {
      const upperText = text.toUpperCase();

      const style = {
        fontSize: '14px',
        marginBottom: '12px',
        lineHeight: 1.4,
        padding: '12px 16px',
        borderRadius: '8px',
        maxWidth: '80%',
      };

      if (upperText.startsWith('AGENT:')) {
        style.backgroundColor = '#f8f9fa';
        style.color = '#495057';
        style.marginLeft = 'auto';
        style.borderLeft = '3px solid #007bff';
      } else if (upperText.startsWith('TICKET')) {
        style.backgroundColor = '#007bff';
        style.color = 'white';
        style.fontWeight = '600';
        style.marginBottom = '16px';
      } else if (upperText.startsWith('AI:')) {
        style.backgroundColor = '#e3f2fd';
        style.color = '#1565c0';
        style.marginRight = 'auto';
        style.borderLeft = '3px solid #2196f3';
      } else if (upperText.startsWith('CATEGORY:')) {
        style.backgroundColor = '#f3e5f5';
        style.color = '#7b1fa2';
        style.marginRight = 'auto';
        style.borderLeft = '3px solid #9c27b0';
      } else {
        style.backgroundColor = '#f8f9fa';
        style.color = '#495057';
        // style.marginRight = 'auto';
      }

      return style;
    },

    getProfessionalChatStyle(text) {
      // const upperText = text.toUpperCase();

      const style = {
        fontSize: '14px',
        marginBottom: '16px',
        lineHeight: 1.5,
        padding: '16px 20px',
        borderRadius: '8px',
        maxWidth: '85%',
        border: '1px solid rgba(229, 231, 235, 0.8)',
      };

      if (Array.isArray(text)){
            style.backgroundColor = 'rgba(249, 250, 251, 0.95)';
        style.color = '#374151';
        style.marginLeft = 'auto';
        style.borderLeft = '3px solid #2563eb';
        style.fontWeight = '500'; 
      }
      else if (text.startsWith('AGENT:')) {
        style.backgroundColor = 'rgba(249, 250, 251, 0.95)';
        style.color = '#374151';
        style.marginLeft = 'auto';
        style.borderLeft = '3px solid #2563eb';
        style.fontWeight = '500';
      } else if (text.startsWith('TICKET')) {
        style.backgroundColor = '#2563eb';
        style.color = 'white';
        style.fontWeight = '600';
        style.marginBottom = '20px';
        style.borderColor = '#1d4ed8';
      } else if (text.startsWith('AI:')) {
        style.backgroundColor = 'rgba(239, 246, 255, 0.9)';
        style.color = '#1e40af';
        style.marginRight = 'auto';
        style.borderLeft = '3px solid #3b82f6';
        style.fontWeight = '500';
      } else if (text.startsWith('CATEGORY:')) {
        style.backgroundColor = 'rgba(248, 250, 252, 0.9)';
        style.color = '#7c3aed';
        style.marginRight = 'auto';
        style.borderLeft = '3px solid #8b5cf6';
        style.fontWeight = '500';
      } else {
        style.backgroundColor = 'rgba(249, 250, 251, 0.95)';
        style.color = '#374151';
        // style.marginRight = 'auto';
        style.fontWeight = '500';
      }

      return style;
    },

    getMessageTime() {
      const now = new Date();
      const time = now.toLocaleTimeString();
      return time.split(' ')[0];
    },

    handleKeydown(event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        this.aiClick();
      }
    },

    exportChat() {
      // Implement export functionality
      console.log("Exporting chat");
    },

    scrollToBottom() {
      // Only scroll if user has manually scrolled to near bottom
      const chatContainer = this.$el.querySelector('[style*="overflow-y: auto"]');
      if (chatContainer) {
        const isNearBottom = chatContainer.scrollTop + chatContainer.clientHeight >= chatContainer.scrollHeight - 100;
        if (isNearBottom) {
          this.$nextTick(() => {
            const chatBottom = this.$refs.chatBottom;
            if (chatBottom) {
              chatBottom.scrollIntoView({ behavior: 'smooth' });
            }
          });
        }
      }
    },

    formatApiResponse(resp) {
      // Check if response contains system metrics or structured data
      // if (this.containsSystemMetrics(resp)) {
      //   return this.formatSystemMetrics(resp);
      // } else if (this.containsDiskUsage(resp)) {
      //   return this.formatDiskUsage(resp);
      // } else if (this.containsLoadMetrics(resp)) {
      //   return this.formatLoadMetrics(resp);
      // } else if (this.containsTableData(resp)) {
      //   return this.formatTableData(resp);
      // }
      
      if (Array.isArray(resp)){ // the only time resp will ever be an array is when it's API data
        return this.generateHtml(resp);
        // return ` <div style="font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #374151;">
        //     This is where info card would go (Simply need a function call here)
        //   </div>`
    
    }
      
      // Default formatting for regular responses
      return this.formatRegularResponse(resp);
    },

    generateHtml(data) {
    function renderValue(value) {
        if (typeof value === 'object' && value !== null) {
            if (Array.isArray(value)) {
                return `<ul style="padding-left: 1em;">${value.map(item => `<li>${renderValue(item)}</li>`).join('')}</ul>`;
            } else {
                // Nested object
                return `<div style="margin-left: 1em; border-left: 2px solid #ddd; padding-left: 1em;">${Object.entries(value).map(
                    ([key, val]) => `<p><strong>${key}:</strong> ${renderValue(val)}</p>`
                ).join('')}</div>`;
            }
        } else {
            return value; // primitive
        }
    }

    let html = '';

    data.forEach(item => {
        html += `<div class="professional-card" style="border:1px solid #ccc; border-radius:8px; padding:16px; margin-bottom:12px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">`;

        for (const key in item) {
            if (item.hasOwnProperty(key)) {
                html += `<p><strong>${key}:</strong> ${renderValue(item[key])}</p>`;
            }
        }

        html += `</div>`;
    });

    return html;
},

    containsSystemMetrics(resp) {
      return resp.includes('System Load Average') || resp.includes('Disk Usage') || resp.includes('performance metrics');
    },

    containsDiskUsage(resp) {
      return resp.includes('blocks used') && resp.includes('limit');
    },

    containsLoadMetrics(resp) {
      return resp.includes('Load Time') || resp.includes('Load Average');
    },

    containsTableData(resp) {
      return resp.includes(':') && resp.includes('-') && (resp.match(/:/g) || []).length > 3;
    },

    formatSystemMetrics(resp) {
      let formatted = resp;
      
      // Format the main response with better structure
      if (resp.includes('System Load Average') && resp.includes('Disk Usage')) {
        const parts = resp.split('**Disk Usage:**');
        const loadSection = parts[0];
        const diskSection = parts[1];
        
        let html = `
          <div style="font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #374151;">
            ${this.formatLoadMetrics(loadSection)}
            ${this.formatDiskUsage('**Disk Usage:**' + diskSection)}
          </div>
        `;
        return html;
      }
      
      return this.formatRegularResponse(formatted);
    },

    formatLoadMetrics(resp) {
      if (!resp.includes('Load')) return this.formatRegularResponse(resp);
      
      // Extract load metrics
      const loadPattern = /Previous (\w+) (?:Minutes? )?Load Time: ([\d.]+)/g;
      let matches = [];
      let match;
      while ((match = loadPattern.exec(resp)) !== null) {
        matches.push({ period: match[1], value: match[2] });
      }
      
      if (matches.length === 0) return this.formatRegularResponse(resp);
      
      let html = `
        <div style="margin-bottom: 24px;">
          <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 16px;">📊</span> System Load Average
          </h4>
          <div style="background: rgba(249, 250, 251, 0.8); border: 1px solid rgba(229, 231, 235, 0.8); border-radius: 8px; padding: 16px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
      `;
      
      matches.forEach(item => {
        const value = parseFloat(item.value);
        const status = value < 1 ? 'good' : value < 2 ? 'moderate' : 'high';
        const color = status === 'good' ? '#059669' : status === 'moderate' ? '#f59e0b' : '#dc2626';
        
        html += `
          <div style="background: white; border: 1px solid rgba(229, 231, 235, 0.6); border-radius: 6px; padding: 12px; text-align: center;">
            <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; font-weight: 500;">${item.period.charAt(0).toUpperCase() + item.period.slice(1)} Min${item.period !== 'Minute' ? 's' : ''}</div>
            <div style="font-size: 20px; font-weight: 700; color: ${color};">${item.value}</div>
            <div style="font-size: 10px; color: ${color}; margin-top: 2px; font-weight: 500;">${status.toUpperCase()}</div>
          </div>
        `;
      });
      
      html += `
            </div>
          </div>
        </div>
      `;
      
      return html;
    },

    formatDiskUsage(resp) {
      if (!resp.includes('blocks used')) return this.formatRegularResponse(resp);
      
      // Extract disk usage data
      const lines = resp.split('\n').filter(line => line.includes('blocks used'));
      const accounts = [];
      
      lines.forEach(line => {
        const match = line.match(/- ([^:]+): ([\d,]+) blocks used(?:\s*\/\s*([\d,]+) limit)?/);
        if (match) {
          const name = match[1].trim();
          const used = parseInt(match[2].replace(/,/g, ''));
          const limit = match[3] ? parseInt(match[3].replace(/,/g, '')) : null;
          const percentage = limit ? (used / limit * 100) : null;
          
          accounts.push({ name, used, limit, percentage });
        }
      });
      
      if (accounts.length === 0) return this.formatRegularResponse(resp);
      
      // Sort by usage (highest first)
      accounts.sort((a, b) => b.used - a.used);
      
      let html = `
        <div style="margin-bottom: 24px;">
          <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 16px;">💾</span> Disk Usage Report
          </h4>
          <div style="background: rgba(249, 250, 251, 0.8); border: 1px solid rgba(229, 231, 235, 0.8); border-radius: 8px; overflow: hidden;">
            <div style="background: rgba(243, 244, 246, 0.8); padding: 12px 16px; border-bottom: 1px solid rgba(229, 231, 235, 0.8);">
              <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; font-size: 12px; font-weight: 600; color: #6b7280;">
                <div>ACCOUNT</div>
                <div style="text-align: right;">BLOCKS USED</div>
                <div style="text-align: right;">LIMIT</div>
                <div style="text-align: right;">USAGE %</div>
              </div>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
      `;
      
      accounts.forEach((account, index) => {
        const usageColor = account.percentage ? 
          (account.percentage > 80 ? '#dc2626' : account.percentage > 60 ? '#f59e0b' : '#059669') : '#6b7280';
        
        const bgColor = index % 2 === 0 ? 'rgba(255, 255, 255, 0.8)' : 'rgba(249, 250, 251, 0.8)';
        
        html += `
          <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; padding: 12px 16px; background: ${bgColor}; border-bottom: 1px solid rgba(229, 231, 235, 0.3);">
            <div style="font-weight: 500; color: #1f2937;">${account.name}</div>
            <div style="text-align: right; font-family: 'Monaco', monospace; font-size: 13px; color: #4b5563;">${account.used.toLocaleString()}</div>
            <div style="text-align: right; font-family: 'Monaco', monospace; font-size: 13px; color: #6b7280;">${account.limit ? account.limit.toLocaleString() : 'No limit'}</div>
            <div style="text-align: right; font-weight: 600; color: ${usageColor};">
              ${account.percentage ? account.percentage.toFixed(1) + '%' : 'N/A'}
            </div>
          </div>
        `;
      });
      
      html += `
            </div>
          </div>
        </div>
      `;
      
      // Add summary
      const totalUsed = accounts.reduce((sum, acc) => sum + acc.used, 0);
      const accountsWithLimits = accounts.filter(acc => acc.limit);
      const highUsageAccounts = accountsWithLimits.filter(acc => acc.percentage > 80);
      
      html += `
        <div style="background: rgba(239, 246, 255, 0.8); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 16px; margin-top: 12px;">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; text-align: center;">
            <div>
              <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Total Accounts</div>
              <div style="font-size: 18px; font-weight: 700; color: #2563eb;">${accounts.length}</div>
            </div>
            <div>
              <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Total Usage</div>
              <div style="font-size: 18px; font-weight: 700; color: #2563eb;">${totalUsed.toLocaleString()}</div>
            </div>
            <div>
              <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">High Usage</div>
              <div style="font-size: 18px; font-weight: 700; color: ${highUsageAccounts.length > 0 ? '#dc2626' : '#059669'};">${highUsageAccounts.length}</div>
            </div>
          </div>
        </div>
      `;
      
      return html;
    },

    formatTableData(resp) {
      // Generic table formatting for colon-separated data
      const lines = resp.split('\n').filter(line => line.includes(':') && line.trim());
      if (lines.length < 2) return this.formatRegularResponse(resp);
      
      let html = `
        <div style="background: rgba(249, 250, 251, 0.8); border: 1px solid rgba(229, 231, 235, 0.8); border-radius: 8px; padding: 16px;">
          <div style="display: flex; flex-direction: column; gap: 8px;">
      `;
      
      lines.forEach(line => {
        const parts = line.split(':');
        if (parts.length >= 2) {
          const key = parts[0].replace(/^-\s*/, '').trim();
          const value = parts.slice(1).join(':').trim();
          
          html += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(229, 231, 235, 0.3);">
              <span style="font-weight: 500; color: #374151;">${key}</span>
              <span style="font-family: 'Monaco', monospace; color: #6b7280; font-size: 13px;">${value}</span>
            </div>
          `;
        }
      });
      
      html += `
          </div>
        </div>
      `;
      
      return html;
    },

    formatRegularResponse(resp) {
      // Clean up and format regular text responses
      let formatted = resp;
      
      // Handle bold text
      formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong style="font-weight: 600; color: #1f2937;">$1</strong>');
      
      // Handle line breaks and paragraphs
      formatted = formatted.replace(/\n\s*\n/g, '</p><p style="margin: 12px 0; line-height: 1.5;">');
      formatted = formatted.replace(/\n/g, '<br>');
      
      // Wrap in paragraph if not already wrapped
      if (!formatted.includes('<p>')) {
        formatted = `<p style="margin: 0; line-height: 1.5;">${formatted}</p>`;
      }
      
      return formatted;
    },

    lookupTicket() {
      if (!this.ticketIdInput.trim()) return;
      
      this.lookupLoading = true;
      this.lookupError = '';
      this.ticketInfo = null;

      // Use the same API endpoint as submitTicketId but don't auto-submit to chat
      fetch('http://localhost:8080/contacts/ai/ticketID', {
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify({ 'message': this.ticketIdInput.trim() })
      })
      .then(response => response.json())
      .then(data => {
        this.lookupLoading = false;
        if (data && data.summary) {
          // Parse the ticket information from the response
          this.ticketInfo = {
            id: this.ticketIdInput.trim(),
            summary: data.summary,
            category: data.category,
            priority: data.priority,
            status: 'Active' // Default status
          };
          this.lookupError = '';
        } else {
          this.lookupError = `Ticket #${this.ticketIdInput.trim()} not found`;
          this.ticketInfo = null;
        }
      })
      .catch(error => {
        this.lookupLoading = false;
        this.lookupError = `Error looking up ticket: ${error.message}`;
        this.ticketInfo = null;
        console.error('Ticket lookup error:', error);
      });
    },

    useTicketForChat() {
      if (this.ticketInfo) {
        // Set the ticket information in the chat context
        this.userMessage = this.ticketInfo.summary;
        this.priority = this.ticketInfo.priority;
        
        // Add the category information to AI responses if it exists
        if (this.ticketInfo.category) {
          this.aiResponse.push(this.ticketInfo.category);
        }
        
        // Switch back to submit mode and clear lookup
        this.ticketMode = 'submit';
        this.clearTicketInfo();
        
        // Trigger the AI chat with the ticket information
        this.aiClick();
      }
    },

    clearTicketInfo() {
      this.ticketInfo = null;
      this.lookupError = '';
      this.ticketIdInput = '';
    }
  }

}
</script>

<style scoped>
@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
  100% {
    opacity: 1;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Typing indicator animation */
.typing-indicator {
  display: flex;
  gap: 4px;
}

.typing-indicator span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #2563eb;
  display: inline-block;
  animation: typing 1.2s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) {
  animation-delay: 0s;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.15s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes typing {
  0%, 80%, 100% {
    transform: scale(0.8);
    opacity: 0.4;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Message bubble styles */
.message-bubble {
  position: relative;
  animation: slideIn 0.2s ease-out;
  transition: all 0.2s ease;
}

.message-bubble:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.message-content {
  position: relative;
}

.message-text {
  margin-bottom: 6px;
}

.message-time {
  font-size: 11px;
  opacity: 0.6;
  text-align: right;
  margin-top: 6px;
  color: #6b7280;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Enhanced scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: rgba(0,0,0,0.03);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(45deg, rgba(37,99,235,0.3), rgba(37,99,235,0.6));
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(45deg, rgba(37,99,235,0.5), rgba(37,99,235,0.8));
}

/* Button enhancements */
button {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

/* Input focus enhancements */
input:focus, textarea:focus {
  outline: none !important;
}

/* Suggestion buttons hover effects */
button[style*="rgba(37, 99, 235, 0.1)"]:hover {
  background: rgba(37, 99, 235, 0.15) !important;
  transform: translateY(-1px);
}

button[style*="rgba(16, 185, 129, 0.1)"]:hover {
  background: rgba(16, 185, 129, 0.15) !important;
  transform: translateY(-1px);
}

button[style*="rgba(245, 158, 11, 0.1)"]:hover {
  background: rgba(245, 158, 11, 0.15) !important;
  transform: translateY(-1px);
}

/* Disabled button styles */
button:disabled {
  cursor: not-allowed !important;
  transform: none !important;
  opacity: 0.6 !important;
}

/* Chat area smooth scrolling */
div[style*="overflow-y: auto"] {
  scroll-behavior: smooth;
}

/* Enhanced glass morphism */
.glass-card {
  backdrop-filter: blur(10px) saturate(120%);
  -webkit-backdrop-filter: blur(10px) saturate(120%);
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(229, 231, 235, 0.8);
}

/* Professional focus states */
input:focus, textarea:focus {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
  border-color: #2563eb !important;
}

/* Subtle animations for professional feel */
.professional-hover {
  transition: all 0.2s ease;
}

.professional-hover:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Remove overly flashy animations */
@keyframes subtle-pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* Professional card styling */
.professional-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(229, 231, 235, 0.8);
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.professional-card:hover {
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
</style>