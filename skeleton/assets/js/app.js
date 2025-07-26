// Import API service and make it globally available
import { ApiService } from './services/apiService.js';
window.ApiService = ApiService;

// Import React components
import './components/App';

// This line enables HMR
if (module.hot) {
    module.hot.accept();
} 