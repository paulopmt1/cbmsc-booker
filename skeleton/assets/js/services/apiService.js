// API Service for communicating with Symfony backend
export class ApiService {
    constructor() {
        this.baseUrl = '/api/schedule';
    }

    async makeRequest(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    // Get all schedule data for the month
    async getAllScheduleData() {
        const response = await this.makeRequest('/all');
        return response.data;
    }

    // Get schedule data for a specific day
    async getScheduleForDay(day) {
        const response = await this.makeRequest(`/day/${day}`);
        return response.data;
    }

    // Get unresolved conflicts
    async getUnresolvedConflicts() {
        const response = await this.makeRequest('/unresolved');
        return response.data;
    }

    // Get resolution for a specific day
    async getResolution(day) {
        const response = await this.makeRequest(`/resolution/${day}`);
        return response.data;
    }

    // Save resolution for a specific day
    async saveResolution(day, selectedPeople, resolvedBy = 'User') {
        const response = await this.makeRequest(`/resolution/${day}`, {
            method: 'POST',
            body: JSON.stringify({
                selectedPeople,
                resolvedBy
            })
        });
        return response.data;
    }

    // Get all resolutions
    async getAllResolutions() {
        const response = await this.makeRequest('/resolutions');
        return response.data;
    }

    // Clear all resolutions
    async clearAllResolutions() {
        const response = await this.makeRequest('/resolutions/clear', {
            method: 'DELETE'
        });
        return response.message;
    }

    // Regenerate schedule data
    async regenerateData() {
        const response = await this.makeRequest('/regenerate', {
            method: 'POST'
        });
        return response.data;
    }
}

// Make it available globally
window.ApiService = ApiService; 