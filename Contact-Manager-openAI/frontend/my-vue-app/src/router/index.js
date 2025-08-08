import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../components/views/DashboardView.vue'
import AIAssistantView from '../components/views/AIAssistantView.vue'
import TicketsView from '../components/views/TicketsView.vue'
import ClientsView from '../components/views/ClientsView.vue'
import ServersView from '../components/views/ServersView.vue'
import HostingView from '../components/views/HostingView.vue'
import InvoicesView from '../components/views/InvoicesView.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardView,
    meta: { title: 'Admin Dashboard', icon: '🏠' }
  },
  {
    path: '/ai',
    name: 'AI',
    component: AIAssistantView,
    meta: { title: 'AI Assistant', icon: '🤖', fullscreen: true }
  },
  {
    path: '/tickets',
    name: 'Tickets',
    component: TicketsView,
    meta: { title: 'Support Tickets', icon: '🎫', fullscreen: true }
  },
  {
    path: '/clients',
    name: 'Clients',
    component: ClientsView,
    meta: { title: 'Client Management', icon: '👥', fullscreen: true }
  },
  {
    path: '/invoices',
    name: 'Invoices',
    component: InvoicesView,
    meta: { title: 'Invoice Management', icon: '💰', fullscreen: true }
  },
  {
    path: '/servers',
    name: 'Servers',
    component: ServersView,
    meta: { title: 'Server Management', icon: '🖥️', fullscreen: true }
  },
  {
    path: '/hosting',
    name: 'Hosting',
    component: HostingView,
    meta: { title: 'Hosting Management', icon: '🏠', fullscreen: true }
  },
  {
    path: '/session',
    name: 'Session',
    component: () => import('../components/views/SessionView.vue'),
    meta: { title: 'Session Viewer', icon: '📋' }
  },
  {
    path: '/log',
    name: 'Log',
    component: () => import('../components/views/LogView.vue'),
    meta: { title: 'History Log', icon: '📜' }
  },
  {
    path: '/client-input',
    name: 'ClientInput',
    component: () => import('../components/views/ClientInputView.vue'),
    meta: { title: 'Client Information', icon: '📝' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Set page title based on route
router.beforeEach((to, from, next) => {
  if (to.meta && to.meta.title) {
    document.title = `${to.meta.title} - Admin Dashboard`
  }
  next()
})

export default router 