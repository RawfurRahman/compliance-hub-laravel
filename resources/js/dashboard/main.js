import { createApp, h } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import App from './App.vue'

const app = createApp(App)
app.use(VueApexCharts)
app.mount('#dashboard-app')
