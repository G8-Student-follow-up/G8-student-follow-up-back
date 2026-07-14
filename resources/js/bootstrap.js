import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Send cookies for Sanctum SPA authentication by default
window.axios.defaults.withCredentials = true;
