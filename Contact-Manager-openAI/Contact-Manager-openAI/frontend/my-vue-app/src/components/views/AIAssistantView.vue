<script>
export default {
  props: {
    selectedTicket: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      contacts: [],
      view: 'contacts',
      loading: false,
      intervalId: null,
      ticketIdInput: '',
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

    // Auto-submit if a ticket was passed in from TicketsView
    this.tryAutoSubmitSelectedTicket();
  },

  watch: {
    view(newView) {
      if (newView === 'contacts') {
        this.reset();
      }
    },
    // React to ticket selection from TicketsView
    selectedTicket: {
      handler() {
        this.tryAutoSubmitSelectedTicket();
      },
      deep: false,
      immediate: false
    }
  },

  methods: {
    tryAutoSubmitSelectedTicket() {
      if (this.selectedTicket && this.selectedTicket.id) {
        // Ensure we're on the AI view
        this.view = 'AI';
        // Reuse the same flow as the quick ticket submit
        this.ticketIdInput = String(this.selectedTicket.id);
        this.submitTicketId();
      }
    },

    startLoadingDots() {
  if (this.intervalId) clearInterval(this.intervalId);
  this.loading = true;
  this.intervalId = setInterval(() => {
    this.loading = false;
  }, 1000);
}
  }
}
</script> 