import { createApp } from 'vue'

createApp({
  data() {
    return {
      count: 0
    }
  },
  mounted(){
    alert(5) 
  }
}).mount('#app')