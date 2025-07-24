/**
 * Alpine.js Timer Store
 * Handles all timer-related state and API interactions
 */

function timerStore() {
    return {
        // State
        timer: {
            id: null,
            status: 'stopped',
            description: '',
            trackable_type: '',
            trackable_id: null,
            trackable: null,
            project: null,
            issue: null,
            started_at: null,
            elapsed_seconds: 0
        },
        elapsedSeconds: 0,
        recentEntries: [],
        projects: [],
        issues: [],
        loading: false,
        error: '',
        success: '',
        showStartModal: false,
        showRecentEntries: false,
        
        // Synchronization state
        syncStatus: 'idle', // 'idle', 'syncing', 'error', 'offline'
        lastSyncTime: null,
        syncInterval: null,
        networkStatus: 'online',
        conflictResolution: 'server-wins', // 'server-wins', 'local-wins', 'merge'
        
        // New timer form data
        newTimer: {
            trackable_type: '',
            trackable_id: '',
            description: ''
        },
        
        // Open start modal and reset form
        openStartModal() {
            // Reset form to ensure clean state
            this.newTimer = {
                trackable_type: '',
                trackable_id: '',
                description: ''
            };
            this.showStartModal = true;
        },

        // Handle trackable type change - reset selected ID
        onTrackableTypeChange() {
            this.newTimer.trackable_id = '';
            console.log('Trackable type changed to:', this.newTimer.trackable_type);
        },

        // Computed properties
        get formattedTime() {
            return this.formatDuration(this.elapsedSeconds);
        },

        // Initialize the store
        async init() {
            console.log('Initializing timer store...');
            
            // Load timer state from localStorage
            this.loadFromStorage();
            
            // Setup network monitoring
            this.setupNetworkMonitoring();
            
            try {
                // Load projects and issues for selection
                await Promise.all([
                    this.fetchProjects(),
                    this.fetchIssues()
                ]);
                
                // Fetch current timer from API
                await this.fetchActiveTimer();
                
                // Fetch recent entries
                await this.fetchRecentEntries();
                
                // Start periodic synchronization
                this.startPeriodicSync();
                
                console.log('Timer store initialized successfully');
            } catch (error) {
                console.warn('Failed to initialize timer store:', error.message);
                
                // Check if it's an authentication error
                if (error.message.includes('Unauthenticated') || error.message.includes('401')) {
                    this.handleError('Please log in to use the timer functionality');
                } else {
                    this.handleError('Failed to load timer data. Some features may not work.');
                }
            }
            
            // Validate and correct timer state after loading
            this.validateAndCorrectTimerState();
            
            // Setup cleanup on page unload
            this.setupCleanup();
            
            // Start real-time updates regardless of API status
            this.startRealtimeUpdates();
            
            // Start periodic timer state validation
            this.startTimerStateValidation();
            
            // Auto-hide messages after 5 seconds
            this.$watch('error', (value) => {
                if (value) setTimeout(() => this.error = '', 5000);
            });
            this.$watch('success', (value) => {
                if (value) setTimeout(() => this.success = '', 5000);
            });
        },

        // ============================================
        // STORAGE AND RECOVERY METHODS
        // ============================================
        
        // Load timer state from localStorage with validation and recovery
        loadFromStorage() {
            try {
                const stored = localStorage.getItem('timer_state');
                if (!stored) {
                    this.initializeDefaultState();
                    return;
                }
                
                const data = JSON.parse(stored);
                
                // Validate stored data structure
                if (!this.validateStoredData(data)) {
                    console.warn('Invalid stored data detected, initializing default state');
                    this.initializeDefaultState();
                    this.clearStorage();
                    return;
                }
                
                // Check if data is stale (older than 24 hours)
                const maxAge = 24 * 60 * 60 * 1000; // 24 hours
                if (data.timestamp && (Date.now() - data.timestamp) > maxAge) {
                    console.warn('Stored data is stale, initializing fresh state');
                    this.initializeDefaultState();
                    this.clearStorage();
                    return;
                }
                
                // Load and validate timer state
                if (data.timer) {
                    this.timer = this.sanitizeTimerData({ ...this.timer, ...data.timer });
                    this.elapsedSeconds = Math.max(0, data.elapsedSeconds || 0);
                }
                
                // Load offline queue if exists
                this.loadOfflineQueue();
                
                console.log('Timer state loaded from storage successfully');
                
            } catch (error) {
                console.error('Error loading timer state from storage:', error);
                this.logError(error, { action: 'loadFromStorage' });
                
                // Attempt recovery
                this.recoverFromStorageError();
            }
        },
        
        // Validate stored data structure
        validateStoredData(data) {
            if (!data || typeof data !== 'object') return false;
            
            // Check required fields
            if (!data.timer || typeof data.timer !== 'object') return false;
            
            // Validate timer structure
            const timer = data.timer;
            const requiredFields = ['id', 'status', 'trackable_type', 'trackable_id'];
            
            for (const field of requiredFields) {
                if (!(field in timer)) return false;
            }
            
            // Validate status values
            const validStatuses = ['running', 'paused', 'stopped'];
            if (!validStatuses.includes(timer.status)) return false;
            
            return true;
        },
        
        // Sanitize timer data to prevent corruption
        sanitizeTimerData(timer) {
            const sanitized = {
                id: timer.id || null,
                status: ['running', 'paused', 'stopped'].includes(timer.status) ? timer.status : 'stopped',
                description: typeof timer.description === 'string' ? timer.description : '',
                trackable_type: typeof timer.trackable_type === 'string' ? timer.trackable_type : '',
                trackable_id: timer.trackable_id || null,
                trackable: timer.trackable || null,
                project: timer.project || null,
                issue: timer.issue || null,
                started_at: timer.started_at || null,
                elapsed_seconds: Math.max(0, timer.elapsed_seconds || 0)
            };
            
            // Validate started_at date
            if (sanitized.started_at) {
                const startDate = new Date(sanitized.started_at);
                if (isNaN(startDate.getTime())) {
                    sanitized.started_at = null;
                    sanitized.status = 'stopped';
                }
            }
            
            return sanitized;
        },
        
        // Initialize default state
        initializeDefaultState() {
            this.timer = {
                id: null,
                status: 'stopped',
                description: '',
                trackable_type: '',
                trackable_id: null,
                trackable: null,
                project: null,
                issue: null,
                started_at: null,
                elapsed_seconds: 0
            };
            this.elapsedSeconds = 0;
        },
        
        // Recover from storage errors
        recoverFromStorageError() {
            console.log('Attempting storage recovery...');
            
            try {
                // Clear corrupted storage
                this.clearStorage();
                
                // Initialize default state
                this.initializeDefaultState();
                
                // Try to fetch current state from server
                this.fetchActiveTimer().catch(error => {
                    console.warn('Could not fetch server state during recovery:', error.message);
                });
                
                this.showMessage('Storage recovered. Some data may have been lost.', 'warning');
                
            } catch (error) {
                console.error('Storage recovery failed:', error);
                this.showMessage('Storage recovery failed. Please refresh the page.', 'error');
            }
        },

        // Enhanced save to localStorage with error handling
        saveToStorage() {
            try {
                const data = {
                    timer: this.timer,
                    elapsedSeconds: this.elapsedSeconds,
                    timestamp: Date.now(),
                    version: '1.0' // For future migrations
                };
                
                const jsonString = JSON.stringify(data);
                
                // Check if storage quota is exceeded
                if (jsonString.length > 5 * 1024 * 1024) { // 5MB limit
                    console.warn('Storage data too large, cleaning up...');
                    this.cleanupStorage();
                    return;
                }
                
                localStorage.setItem('timer_state', jsonString);
                
            } catch (error) {
                console.error('Error saving timer state to storage:', error);
                this.logError(error, { action: 'saveToStorage' });
                
                if (error.name === 'QuotaExceededError') {
                    this.handleStorageQuotaError();
                } else {
                    this.showMessage('Failed to save timer state locally', 'warning');
                }
            }
        },
        
        // Handle storage quota exceeded
        handleStorageQuotaError() {
            console.log('Storage quota exceeded, cleaning up...');
            
            try {
                // Clear error logs first
                this.errorLog = [];
                
                // Clear offline queue if too large
                if (this.offlineQueue.length > 10) {
                    this.offlineQueue = this.offlineQueue.slice(-5);
                }
                
                // Try saving again
                this.saveToStorage();
                
                this.showMessage('Storage cleaned up to make space', 'info');
                
            } catch (error) {
                console.error('Storage cleanup failed:', error);
                this.showMessage('Storage full. Some data may not be saved locally.', 'warning');
            }
        },
        
        // Clean up storage by removing old data
        cleanupStorage() {
            try {
                // Clear error logs
                this.errorLog = [];
                
                // Limit offline queue
                this.offlineQueue = this.offlineQueue.slice(-10);
                
                // Clear old localStorage items
                const keysToCheck = ['timer_state_backup', 'old_timer_data'];
                keysToCheck.forEach(key => {
                    localStorage.removeItem(key);
                });
                
                console.log('Storage cleanup completed');
                
            } catch (error) {
                console.error('Storage cleanup failed:', error);
            }
        },
        
        // Clear all storage
        clearStorage() {
            try {
                localStorage.removeItem('timer_state');
                localStorage.removeItem('offline_queue');
                console.log('Storage cleared');
            } catch (error) {
                console.error('Error clearing storage:', error);
            }
        },
        
        // Save offline queue to storage
        saveOfflineQueue() {
            try {
                localStorage.setItem('offline_queue', JSON.stringify(this.offlineQueue));
            } catch (error) {
                console.error('Error saving offline queue:', error);
            }
        },
        
        // Load offline queue from storage
        loadOfflineQueue() {
            try {
                const stored = localStorage.getItem('offline_queue');
                if (stored) {
                    const queue = JSON.parse(stored);
                    if (Array.isArray(queue)) {
                        this.offlineQueue = queue;
                    }
                }
            } catch (error) {
                console.error('Error loading offline queue:', error);
                this.offlineQueue = [];
            }
        },

        // Start real-time updates
        startRealtimeUpdates() {
            setInterval(() => {
                if (this.timer.status === 'running') {
                    this.updateElapsedTime();
                    
                    // Check for timer drift every 30 seconds
                    if (Math.floor(Date.now() / 1000) % 30 === 0) {
                        this.checkForTimerDrift();
                    }
                }
            }, 1000);
        },

        // Update elapsed time for running timer
        updateElapsedTime() {
            if (this.timer.status === 'running' && this.timer.started_at) {
                const startTime = new Date(this.timer.started_at);
                const now = new Date();
                const additionalSeconds = Math.floor((now - startTime) / 1000);
                this.elapsedSeconds = (this.timer.elapsed_seconds || 0) + additionalSeconds;
                this.saveToStorage();
            }
        },

        // API Methods
        async fetchActiveTimer() {
            try {
                const response = await this.apiCall('/api/timers/active');
                if (response.timer) {
                    this.timer = response.timer;
                    this.elapsedSeconds = response.elapsed_seconds || 0;
                } else {
                    this.resetTimer();
                }
                this.saveToStorage();
            } catch (error) {
                console.error('Error fetching active timer:', error);
                // Don't show error for authentication issues during init
                if (!error.message.includes('Unauthenticated') && !error.message.includes('401')) {
                    this.handleError('Failed to load timer state');
                }
                throw error; // Re-throw for init method to handle
            }
        },

        async startTimer() {
            // Validate required fields
            if (!this.newTimer.trackable_type) {
                this.handleError('Please select whether you\'re working on a Project or Issue');
                return;
            }
            
            if (!this.newTimer.trackable_id || this.newTimer.trackable_id === '' || this.newTimer.trackable_id === null) {
                const itemType = this.newTimer.trackable_type === 'Project' ? 'project' : 'issue';
                this.handleError(`Please select a ${itemType}`);
                return;
            }

            this.loading = true;
            try {
                // Convert simple type to full model path for backend
                const timerData = {
                    ...this.newTimer,
                    trackable_type: this.newTimer.trackable_type === 'Project' 
                        ? 'App\\Models\\Project' 
                        : this.newTimer.trackable_type === 'Issue' 
                        ? 'App\\Models\\Issue' 
                        : this.newTimer.trackable_type
                };
                
                const response = await this.apiCall('/api/timers/start', 'POST', timerData);
                this.timer = response.timer;
                this.elapsedSeconds = response.elapsed_seconds || 0;
                this.showStartModal = false;
                this.resetNewTimerForm();
                this.saveToStorage();
                this.handleSuccess('Timer started successfully');
                await this.fetchRecentEntries();
            } catch (error) {
                this.handleError(error.message || 'Failed to start timer');
            } finally {
                this.loading = false;
            }
        },

        async pauseTimer() {
            this.loading = true;
            try {
                const response = await this.apiCall('/api/timers/pause', 'POST');
                this.timer = response.timer;
                this.elapsedSeconds = response.elapsed_seconds || 0;
                this.saveToStorage();
                this.handleSuccess('Timer paused');
            } catch (error) {
                this.handleError(error.message || 'Failed to pause timer');
            } finally {
                this.loading = false;
            }
        },

        async resumeTimer() {
            this.loading = true;
            try {
                const response = await this.apiCall('/api/timers/resume', 'POST');
                this.timer = response.timer;
                this.elapsedSeconds = response.elapsed_seconds || 0;
                this.saveToStorage();
                this.handleSuccess('Timer resumed');
            } catch (error) {
                this.handleError(error.message || 'Failed to resume timer');
            } finally {
                this.loading = false;
            }
        },

        async stopTimer() {
            this.loading = true;
            try {
                const response = await this.apiCall('/api/timers/stop', 'POST');
                this.resetTimer();
                this.handleSuccess('Timer stopped and time entry created');
                await this.fetchRecentEntries();
            } catch (error) {
                this.handleError(error.message || 'Failed to stop timer');
            } finally {
                this.loading = false;
            }
        },

        async fetchRecentEntries() {
            try {
                const response = await this.apiCall('/api/timers/recent-entries');
                this.recentEntries = response.entries || [];
            } catch (error) {
                console.error('Error fetching recent entries:', error);
            }
        },

        async fetchProjects() {
            try {
                const response = await this.apiCall('/api/projects');
                this.projects = response.projects || [];
            } catch (error) {
                console.error('Error fetching projects:', error);
                this.projects = [];
                throw error; // Re-throw for init method to handle
            }
        },

        async fetchIssues() {
            try {
                const response = await this.apiCall('/api/issues');
                this.issues = response.issues || [];
            } catch (error) {
                console.error('Error fetching issues:', error);
                this.issues = [];
                throw error; // Re-throw for init method to handle
            }
        },

        // Get filtered issues for selected project
        get filteredIssues() {
            if (!this.newTimer.trackable_type || this.newTimer.trackable_type !== 'App\\Models\\Issue') {
                return this.issues;
            }
            // If we add project filtering later, we can filter here
            return this.issues;
        },

        // ============================================
        // ERROR HANDLING AND RECOVERY SYSTEM
        // ============================================
        
        // Error handling state
        errorLog: [],
        retryAttempts: {},
        offlineQueue: [],
        maxRetries: 3,
        retryDelay: 1000,
        
        // Enhanced API call with error handling and retry logic
        async apiCall(url, method = 'GET', data = null, options = {}) {
            const {
                retries = this.maxRetries,
                skipQueue = false,
                silent = false,
                timeout = 10000
            } = options;
            
            // If offline and not skipping queue, add to offline queue
            if (this.networkStatus === 'offline' && !skipQueue) {
                return this.queueOfflineAction(url, method, data, options);
            }
            
            const requestId = `${method}:${url}`;
            const currentAttempt = (this.retryAttempts[requestId] || 0) + 1;
            this.retryAttempts[requestId] = currentAttempt;
            
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);
                
                const fetchOptions = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    signal: controller.signal
                };

                if (data) {
                    fetchOptions.body = JSON.stringify(data);
                }

                const response = await fetch(url, fetchOptions);
                clearTimeout(timeoutId);
                
                // Reset retry count on success
                delete this.retryAttempts[requestId];
                
                let result;
                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    // Handle non-JSON responses (like HTML error pages)
                    const text = await response.text();
                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                    }
                    result = { data: text };
                }

                if (!response.ok) {
                    const error = new Error(result.message || result.error || `HTTP ${response.status}: ${response.statusText}`);
                    error.status = response.status;
                    error.response = result;
                    throw error;
                }

                return result;
                
            } catch (error) {
                this.logError(error, { url, method, data, attempt: currentAttempt });
                
                // Handle specific error types
                if (error.name === 'AbortError') {
                    error.message = 'Request timed out. Please check your connection.';
                } else if (error.status === 401) {
                    return this.handleAuthError(error, silent);
                } else if (error.status === 403) {
                    error.message = 'Access denied. You may not have permission for this action.';
                } else if (error.status === 404) {
                    error.message = 'Resource not found. It may have been deleted.';
                } else if (error.status === 422) {
                    return this.handleValidationError(error, silent);
                } else if (error.status >= 500) {
                    error.message = 'Server error. Please try again in a moment.';
                }
                
                // Retry logic for retryable errors
                if (this.shouldRetry(error, currentAttempt, retries)) {
                    if (!silent) {
                        this.showMessage(`Retrying... (${currentAttempt}/${retries})`, 'info');
                    }
                    await this.delay(this.retryDelay * currentAttempt);
                    return this.apiCall(url, method, data, { ...options, retries: retries - 1 });
                }
                
                // Reset retry count after all attempts
                delete this.retryAttempts[requestId];
                
                if (!silent) {
                    this.handleError(error.message);
                }
                
                throw error;
            }
        },
        
        // Determine if error should be retried
        shouldRetry(error, currentAttempt, maxRetries) {
            if (currentAttempt >= maxRetries) return false;
            
            // Retry on network errors, timeouts, and 5xx server errors
            return (
                error.name === 'AbortError' ||
                error.name === 'TypeError' ||
                !error.status ||
                error.status >= 500
            );
        },
        
        // Handle authentication errors
        async handleAuthError(error, silent) {
            if (!silent) {
                this.showMessage('Session expired. Please log in again.', 'error');
            }
            
            // Clear local state
            this.resetTimer();
            this.clearStorage();
            
            // Redirect to login after a delay
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            
            throw error;
        },
        
        // Handle validation errors
        handleValidationError(error, silent) {
            if (error.response && error.response.errors) {
                const errors = error.response.errors;
                const messages = Object.values(errors).flat();
                const message = messages.join(', ');
                
                if (!silent) {
                    this.showMessage(`Validation error: ${message}`, 'error');
                }
                
                error.validationErrors = errors;
                error.message = message;
            }
            
            throw error;
        },
        
        // Queue actions for when offline
        queueOfflineAction(url, method, data, options) {
            const action = {
                id: Date.now() + Math.random(),
                url,
                method,
                data,
                options,
                timestamp: new Date().toISOString(),
                retries: 0
            };
            
            this.offlineQueue.push(action);
            this.saveOfflineQueue();
            
            this.showMessage('Action queued for when connection is restored', 'info');
            
            // Return a promise that will resolve when the action is processed
            return new Promise((resolve, reject) => {
                action.resolve = resolve;
                action.reject = reject;
            });
        },
        
        // Process offline queue when connection is restored
        async processOfflineQueue() {
            if (this.offlineQueue.length === 0) return;
            
            this.showMessage(`Processing ${this.offlineQueue.length} queued actions...`, 'info');
            
            const queue = [...this.offlineQueue];
            this.offlineQueue = [];
            
            for (const action of queue) {
                try {
                    const result = await this.apiCall(
                        action.url,
                        action.method,
                        action.data,
                        { ...action.options, skipQueue: true, silent: true }
                    );
                    
                    if (action.resolve) {
                        action.resolve(result);
                    }
                } catch (error) {
                    if (action.retries < 2) {
                        action.retries++;
                        this.offlineQueue.push(action);
                    } else if (action.reject) {
                        action.reject(error);
                    }
                }
            }
            
            this.saveOfflineQueue();
            
            if (this.offlineQueue.length === 0) {
                this.showMessage('All queued actions processed successfully', 'success');
            } else {
                this.showMessage(`${this.offlineQueue.length} actions failed and will be retried`, 'warning');
            }
        },
        
        // Log errors for debugging
        logError(error, context = {}) {
            const errorEntry = {
                timestamp: new Date().toISOString(),
                message: error.message,
                stack: error.stack,
                status: error.status,
                context,
                userAgent: navigator.userAgent,
                url: window.location.href
            };
            
            this.errorLog.push(errorEntry);
            
            // Keep only last 50 errors
            if (this.errorLog.length > 50) {
                this.errorLog = this.errorLog.slice(-50);
            }
            
            // Log to console in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('Timer Store Error:', errorEntry);
            }
        },
        
        // Utility delay function
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        resetTimer() {
            this.timer = {
                id: null,
                status: 'stopped',
                description: '',
                trackable_type: '',
                trackable_id: null,
                started_at: null,
                elapsed_seconds: 0
            };
            this.elapsedSeconds = 0;
            this.saveToStorage();
        },

        resetNewTimerForm() {
            this.newTimer = {
                trackable_type: '',
                trackable_id: '',
                description: ''
            };
        },

        handleError(message) {
            this.error = message;
            console.error('Timer error:', message);
        },

        handleSuccess(message) {
            this.success = message;
            console.log('Timer success:', message);
        },

        // Utility Methods
        formatDuration(seconds) {
            if (!seconds || seconds < 0) return '0:00';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffHours / 24);

            if (diffDays > 0) {
                return `${diffDays}d ago`;
            } else if (diffHours > 0) {
                return `${diffHours}h ago`;
            } else {
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                return `${diffMinutes}m ago`;
            }
        },

        // Get display name for current timer's trackable
        get currentTimerDisplayName() {
            if (!this.timer.trackable) return 'Unknown';
            
            if (this.timer.trackable_type === 'App\\Models\\Project') {
                return this.timer.trackable.name || 'Unnamed Project';
            } else if (this.timer.trackable_type === 'App\\Models\\Issue') {
                return this.timer.trackable.title || 'Unnamed Issue';
            }
            
            return 'Unknown';
        },

        // Get project name by ID
        getProjectName(projectId) {
            if (!projectId) return null;
            const project = this.projects.find(p => p.id === projectId);
            return project ? project.name : 'Unknown Project';
        },

        // Get issue title by ID
        getIssueTitle(issueId) {
            if (!issueId) return null;
            const issue = this.issues.find(i => i.id === issueId);
            return issue ? issue.title : 'Unknown Issue';
        },

        // Get selected trackable display name for form
        get selectedTrackableDisplayName() {
            if (!this.newTimer.trackable_type || !this.newTimer.trackable_id) {
                return 'Select project or issue';
            }
            
            if (this.newTimer.trackable_type === 'App\\Models\\Project') {
                return this.getProjectName(parseInt(this.newTimer.trackable_id));
            } else if (this.newTimer.trackable_type === 'App\\Models\\Issue') {
                return this.getIssueTitle(parseInt(this.newTimer.trackable_id));
            }
            
            return 'Unknown';
        },
        
        // ============================================
        // SYNCHRONIZATION METHODS
        // ============================================
        
        // Setup network status monitoring
        setupNetworkMonitoring() {
            // Monitor online/offline status
            window.addEventListener('online', () => {
                console.log('Network connection restored');
                this.networkStatus = 'online';
                this.syncStatus = 'idle';
                
                // Show recovery message
                this.showMessage('Connection restored', 'success');
                
                // Process offline queue first
                this.processOfflineQueue().then(() => {
                    // Then sync with server
                    this.syncWithServer();
                    
                    // Restart periodic sync
                    this.startPeriodicSync();
                }).catch(error => {
                    console.error('Error processing offline queue:', error);
                    // Still try to sync even if queue processing fails
                    this.syncWithServer();
                });
            });
            
            window.addEventListener('offline', () => {
                console.log('Network connection lost');
                this.networkStatus = 'offline';
                this.syncStatus = 'offline';
                
                // Show offline message
                this.showMessage('Working offline - actions will be queued', 'info');
                
                // Stop periodic sync when offline
                if (this.syncInterval) {
                    clearInterval(this.syncInterval);
                    this.syncInterval = null;
                }
            });
            
            // Initial network status
            this.networkStatus = navigator.onLine ? 'online' : 'offline';
            
            // Periodic connection health check
            this.startConnectionHealthCheck();
        },
        
        // Monitor connection health with periodic checks
        startConnectionHealthCheck() {
            setInterval(() => {
                this.checkConnectionHealth();
            }, 30000); // Check every 30 seconds
        },
        
        // Check if connection is actually working
        async checkConnectionHealth() {
            if (this.networkStatus === 'offline') return;
            
            try {
                // Simple ping to check if server is reachable
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                const response = await fetch('/api/timers/active', {
                    method: 'HEAD',
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                // If we get any response, connection is working
                if (this.syncStatus === 'error') {
                    this.syncStatus = 'idle';
                    console.log('Connection health restored');
                }
                
            } catch (error) {
                // Connection might be down
                if (this.networkStatus === 'online') {
                    console.warn('Connection health check failed:', error.message);
                    this.syncStatus = 'error';
                }
            }
        },
        
        // Start periodic synchronization with server
        startPeriodicSync() {
            // Don't start if already running or offline
            if (this.syncInterval || this.networkStatus === 'offline') {
                return;
            }
            
            console.log('Starting periodic timer synchronization (every 30 seconds)');
            
            // Sync every 30 seconds
            this.syncInterval = setInterval(() => {
                if (this.networkStatus === 'online') {
                    this.syncWithServer();
                }
            }, 30000);
            
            // Initial sync
            setTimeout(() => this.syncWithServer(), 1000);
        },
        
        // Stop periodic synchronization
        stopPeriodicSync() {
            if (this.syncInterval) {
                console.log('Stopping periodic timer synchronization');
                clearInterval(this.syncInterval);
                this.syncInterval = null;
            }
        },
        
        // Synchronize timer state with server
        async syncWithServer() {
            // Skip if offline or already syncing
            if (this.networkStatus === 'offline' || this.syncStatus === 'syncing') {
                return;
            }
            
            this.syncStatus = 'syncing';
            
            try {
                // Fetch current server state
                const serverTimer = await this.fetchActiveTimerSilent();
                
                // Compare with local state and resolve conflicts
                const hasConflict = this.detectStateConflict(serverTimer);
                
                if (hasConflict) {
                    console.log('Timer state conflict detected, resolving...');
                    await this.resolveStateConflict(serverTimer);
                } else {
                    // Update local state with server data
                    this.updateLocalState(serverTimer);
                }
                
                this.lastSyncTime = new Date();
                this.syncStatus = 'idle';
                
                // Save updated state to localStorage
                this.saveToStorage();
                
            } catch (error) {
                console.warn('Timer sync failed:', error.message);
                this.syncStatus = 'error';
                
                // Don't show error messages for sync failures to avoid spam
                // The user will see the sync status indicator instead
            }
        },
        
        // Fetch active timer without showing errors (for sync)
        async fetchActiveTimerSilent() {
            const response = await fetch('/api/timers/active', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            return data.timer;
        },
        
        // Detect if local and server timer states conflict
        detectStateConflict(serverTimer) {
            const localTimer = this.timer;
            
            // No conflict if both are null/stopped
            if (!serverTimer && (!localTimer.id || localTimer.status === 'stopped')) {
                return false;
            }
            
            // Conflict if one has active timer and other doesn't
            if (!serverTimer && localTimer.id && localTimer.status !== 'stopped') {
                return true;
            }
            
            if (serverTimer && (!localTimer.id || localTimer.status === 'stopped')) {
                return true;
            }
            
            // Conflict if different timers are active
            if (serverTimer && localTimer.id && serverTimer.id !== localTimer.id) {
                return true;
            }
            
            // Conflict if same timer but different status
            if (serverTimer && localTimer.id === serverTimer.id && serverTimer.status !== localTimer.status) {
                return true;
            }
            
            return false;
        },
        
        // Resolve state conflicts between local and server
        async resolveStateConflict(serverTimer) {
            const localTimer = this.timer;
            
            console.log('Resolving timer state conflict:', {
                local: { id: localTimer.id, status: localTimer.status },
                server: serverTimer ? { id: serverTimer.id, status: serverTimer.status } : null,
                strategy: this.conflictResolution
            });
            
            switch (this.conflictResolution) {
                case 'server-wins':
                    // Server state takes precedence
                    this.updateLocalState(serverTimer);
                    this.showMessage('Timer synchronized with server', 'success');
                    break;
                    
                case 'local-wins':
                    // Keep local state, sync to server
                    if (localTimer.id && localTimer.status !== 'stopped') {
                        // Local timer is active, ensure server matches
                        await this.syncLocalToServer();
                    } else {
                        // Local timer is stopped, stop server timer if needed
                        if (serverTimer) {
                            await this.stopTimer();
                        }
                    }
                    break;
                    
                case 'merge':
                    // Intelligent merge based on timestamps
                    await this.mergeTimerStates(localTimer, serverTimer);
                    break;
                    
                default:
                    // Default to server-wins
                    this.updateLocalState(serverTimer);
                    break;
            }
        },
        
        // Update local state with server data
        updateLocalState(serverTimer) {
            if (!serverTimer) {
                // Server has no active timer
                this.timer = {
                    id: null,
                    status: 'stopped',
                    description: '',
                    trackable_type: '',
                    trackable_id: null,
                    trackable: null,
                    project: null,
                    issue: null,
                    started_at: null,
                    elapsed_seconds: 0
                };
                this.elapsedSeconds = 0;
            } else {
                // Update with server timer data
                this.timer = {
                    ...serverTimer,
                    project: serverTimer.project || null,
                    issue: serverTimer.issue || null
                };
                
                // Calculate elapsed time if timer is running
                if (serverTimer.status === 'running' && serverTimer.started_at) {
                    const startTime = new Date(serverTimer.started_at);
                    const now = new Date();
                    const elapsed = Math.floor((now - startTime) / 1000);
                    this.elapsedSeconds = Math.max(0, elapsed);
                } else {
                    this.elapsedSeconds = serverTimer.elapsed_seconds || 0;
                }
            }
        },
        
        // Sync local timer state to server
        async syncLocalToServer() {
            const localTimer = this.timer;
            
            if (!localTimer.id || localTimer.status === 'stopped') {
                return; // Nothing to sync
            }
            
            try {
                // Send current local state to server
                const response = await fetch('/api/timers/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        timer_id: localTimer.id,
                        status: localTimer.status,
                        elapsed_seconds: this.elapsedSeconds
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                console.log('Local timer state synced to server');
            } catch (error) {
                console.warn('Failed to sync local state to server:', error.message);
                throw error;
            }
        },
        
        // Intelligent merge of timer states
        async mergeTimerStates(localTimer, serverTimer) {
            // For now, implement simple logic:
            // - If local timer is more recent, keep local
            // - If server timer is more recent, use server
            // - In case of tie, prefer server
            
            const localTime = localTimer.started_at ? new Date(localTimer.started_at) : new Date(0);
            const serverTime = serverTimer?.started_at ? new Date(serverTimer.started_at) : new Date(0);
            
            if (localTime > serverTime) {
                // Local timer is more recent
                await this.syncLocalToServer();
                this.showMessage('Local timer state preserved', 'success');
            } else {
                // Server timer is more recent or equal
                this.updateLocalState(serverTimer);
                this.showMessage('Timer synchronized with server', 'success');
            }
        },
        
        // ============================================
        // TIMER STATE VALIDATION AND AUTO-CORRECTION
        // ============================================
        
        // Validate and correct timer state
        validateAndCorrectTimerState() {
            try {
                let correctionsMade = false;
                const issues = [];
                
                // Check for invalid timer status
                const validStatuses = ['running', 'paused', 'stopped'];
                if (!validStatuses.includes(this.timer.status)) {
                    issues.push(`Invalid status: ${this.timer.status}`);
                    this.timer.status = 'stopped';
                    correctionsMade = true;
                }
                
                // Check for running timer without start time
                if (this.timer.status === 'running' && !this.timer.started_at) {
                    issues.push('Running timer without start time');
                    this.timer.started_at = new Date().toISOString();
                    correctionsMade = true;
                }
                
                // Check for invalid start time
                if (this.timer.started_at) {
                    const startDate = new Date(this.timer.started_at);
                    if (isNaN(startDate.getTime())) {
                        issues.push('Invalid start time format');
                        this.timer.started_at = null;
                        this.timer.status = 'stopped';
                        correctionsMade = true;
                    } else if (startDate > new Date()) {
                        issues.push('Start time in the future');
                        this.timer.started_at = new Date().toISOString();
                        correctionsMade = true;
                    }
                }
                
                // Check for negative elapsed seconds
                if (this.elapsedSeconds < 0) {
                    issues.push('Negative elapsed seconds');
                    this.elapsedSeconds = 0;
                    correctionsMade = true;
                }
                
                // Check for unreasonably long timer (over 24 hours)
                if (this.elapsedSeconds > 24 * 60 * 60) {
                    issues.push('Timer running for over 24 hours');
                    // Don't auto-correct this, just log it
                    console.warn('Timer has been running for over 24 hours');
                }
                
                // Check for timer without trackable when it should have one
                if (this.timer.status !== 'stopped' && this.timer.id && !this.timer.trackable_type) {
                    issues.push('Active timer without trackable type');
                    // This might indicate data corruption, stop the timer
                    this.timer.status = 'stopped';
                    correctionsMade = true;
                }
                
                // Check for inconsistent timer ID and status
                if (!this.timer.id && this.timer.status !== 'stopped') {
                    issues.push('Timer without ID but not stopped');
                    this.timer.status = 'stopped';
                    correctionsMade = true;
                }
                
                if (correctionsMade) {
                    console.warn('Timer state corrections made:', issues);
                    this.logError(new Error('Timer state corrected'), {
                        issues,
                        correctedState: this.timer
                    });
                    
                    // Save corrected state
                    this.saveToStorage();
                    
                    // Show user message if significant corrections were made
                    if (issues.length > 1) {
                        this.showMessage('Timer state was corrected due to inconsistencies', 'warning');
                    }
                }
                
                return { correctionsMade, issues };
                
            } catch (error) {
                console.error('Error validating timer state:', error);
                this.logError(error, { action: 'validateTimerState' });
                
                // If validation itself fails, reset to safe state
                this.initializeDefaultState();
                this.saveToStorage();
                
                return { correctionsMade: true, issues: ['Validation failed - reset to default'] };
            }
        },
        
        // Periodic timer state validation
        startTimerStateValidation() {
            // Validate state every 5 minutes
            setInterval(() => {
                this.validateAndCorrectTimerState();
            }, 5 * 60 * 1000);
        },
        
        // Check for timer state drift and correct
        checkForTimerDrift() {
            if (this.timer.status !== 'running' || !this.timer.started_at) {
                return;
            }
            
            try {
                const startTime = new Date(this.timer.started_at);
                const now = new Date();
                const calculatedElapsed = Math.floor((now - startTime) / 1000);
                const drift = Math.abs(calculatedElapsed - this.elapsedSeconds);
                
                // If drift is more than 10 seconds, correct it
                if (drift > 10) {
                    console.warn(`Timer drift detected: ${drift} seconds`);
                    this.elapsedSeconds = calculatedElapsed;
                    this.saveToStorage();
                    
                    this.logError(new Error('Timer drift corrected'), {
                        drift,
                        correctedElapsed: calculatedElapsed
                    });
                }
                
            } catch (error) {
                console.error('Error checking timer drift:', error);
            }
        },
        
        // Emergency timer reset with user confirmation
        async emergencyTimerReset() {
            const confirmed = confirm(
                'This will reset your timer and clear all local data. ' +
                'Any unsaved timer data will be lost. Continue?'
            );
            
            if (!confirmed) return false;
            
            try {
                // Stop any running timer on server
                if (this.timer.id && this.timer.status !== 'stopped') {
                    await this.apiCall('/api/timers/stop', 'POST', {}, { silent: true });
                }
            } catch (error) {
                console.warn('Could not stop server timer during reset:', error.message);
            }
            
            // Clear all local state
            this.initializeDefaultState();
            this.clearStorage();
            this.errorLog = [];
            this.offlineQueue = [];
            this.retryAttempts = {};
            
            // Reset sync status
            this.syncStatus = 'idle';
            this.lastSyncTime = null;
            
            // Try to fetch fresh state from server
            try {
                await this.fetchActiveTimer();
                await this.fetchRecentEntries();
            } catch (error) {
                console.warn('Could not fetch fresh state after reset:', error.message);
            }
            
            this.showMessage('Timer reset completed successfully', 'success');
            return true;
        },
        
        // Setup cleanup handlers
        setupCleanup() {
            // Clean up intervals when page unloads
            window.addEventListener('beforeunload', () => {
                this.stopPeriodicSync();
                this.saveToStorage();
            });
            
            // Handle visibility change (tab switching)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    // Tab is hidden, save state
                    this.saveToStorage();
                } else {
                    // Tab is visible again, sync with server
                    if (this.networkStatus === 'online') {
                        setTimeout(() => this.syncWithServer(), 500);
                    }
                }
            });
        },
        
        // Show user message with auto-hide
        showMessage(message, type = 'info') {
            if (type === 'error') {
                this.error = message;
                this.success = '';
            } else {
                this.success = message;
                this.error = '';
            }
        },
        
        // Get sync status display text
        get syncStatusText() {
            switch (this.syncStatus) {
                case 'syncing': return 'Syncing...';
                case 'error': return 'Sync Error';
                case 'offline': return 'Offline';
                case 'idle': 
                    if (this.lastSyncTime) {
                        const ago = Math.floor((new Date() - this.lastSyncTime) / 1000);
                        if (ago < 60) return 'Synced';
                        if (ago < 3600) return `Synced ${Math.floor(ago / 60)}m ago`;
                        return `Synced ${Math.floor(ago / 3600)}h ago`;
                    }
                    return 'Ready';
                default: return 'Unknown';
            }
        },
        
        // Get sync status color class
        get syncStatusColor() {
            switch (this.syncStatus) {
                case 'syncing': return 'text-blue-500';
                case 'error': return 'text-red-500';
                case 'offline': return 'text-gray-500';
                case 'idle': return 'text-green-500';
                default: return 'text-gray-400';
            }
        },
        
        // Mobile Touch Optimizations
        touchStartY: 0,
        touchStartX: 0,
        touchStartTime: 0,
        modalTouchStartY: 0,
        modalInitialTransform: 0,
        
        // Handle touch start for widget
        handleTouchStart(event) {
            this.touchStartY = event.touches[0].clientY;
            this.touchStartX = event.touches[0].clientX;
            this.touchStartTime = Date.now();
        },
        
        // Handle touch move for widget
        handleTouchMove(event) {
            // Prevent default scrolling behavior for better touch control
            if (this.showStartModal) {
                event.preventDefault();
            }
        },
        
        // Handle touch end for widget
        handleTouchEnd(event) {
            const touchEndY = event.changedTouches[0].clientY;
            const touchEndX = event.changedTouches[0].clientX;
            const touchEndTime = Date.now();
            
            const deltaY = touchEndY - this.touchStartY;
            const deltaX = touchEndX - this.touchStartX;
            const deltaTime = touchEndTime - this.touchStartTime;
            
            // Detect swipe gestures
            const minSwipeDistance = 50;
            const maxSwipeTime = 300;
            
            if (deltaTime < maxSwipeTime) {
                // Vertical swipe down to minimize widget on mobile
                if (deltaY > minSwipeDistance && Math.abs(deltaX) < minSwipeDistance) {
                    this.handleSwipeDown();
                }
                // Horizontal swipe right to show recent entries
                else if (deltaX > minSwipeDistance && Math.abs(deltaY) < minSwipeDistance) {
                    this.showRecentEntries = true;
                }
                // Horizontal swipe left to hide recent entries
                else if (deltaX < -minSwipeDistance && Math.abs(deltaY) < minSwipeDistance) {
                    this.showRecentEntries = false;
                }
            }
        },
        
        // Handle modal touch start
        handleModalTouchStart(event) {
            this.modalTouchStartY = event.touches[0].clientY;
            this.modalInitialTransform = 0;
        },
        
        // Handle modal touch move
        handleModalTouchMove(event) {
            const currentY = event.touches[0].clientY;
            const deltaY = currentY - this.modalTouchStartY;
            
            // Only allow downward swipe
            if (deltaY > 0) {
                const transform = Math.min(deltaY, 200);
                event.currentTarget.style.transform = `translateY(${transform}px)`;
                this.modalInitialTransform = transform;
                
                // Add opacity effect
                const opacity = Math.max(0.5, 1 - (transform / 300));
                event.currentTarget.parentElement.style.backgroundColor = `rgba(0, 0, 0, ${opacity * 0.5})`;
            }
        },
        
        // Handle modal touch end
        handleModalTouchEnd(event) {
            const threshold = 100;
            
            if (this.modalInitialTransform > threshold) {
                // Close modal if swiped down enough
                this.showStartModal = false;
                this.resetNewTimerForm();
            } else {
                // Snap back to original position
                event.currentTarget.style.transform = 'translateY(0)';
                event.currentTarget.parentElement.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            }
            
            this.modalInitialTransform = 0;
        },
        
        // Handle swipe down gesture
        handleSwipeDown() {
            // On mobile, minimize the widget or show a compact view
            if (window.innerWidth < 768) {
                // Toggle between full and compact view
                this.showRecentEntries = false;
                
                // Add visual feedback
                this.showMessage('Swipe up to expand', 'info');
                setTimeout(() => {
                    this.success = '';
                }, 2000);
            }
        },
        
        // Provide touch feedback (visual haptic simulation)
        provideTouchFeedback(event) {
            const element = event.currentTarget;
            
            // Add touch feedback class
            element.classList.add('scale-95', 'brightness-110');
            
            // Remove feedback after short delay
            setTimeout(() => {
                element.classList.remove('scale-95', 'brightness-110');
            }, 150);
            
            // Vibrate if supported (mobile devices)
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
        },
        
        // Long press handler for additional actions
        handleLongPress(event, action) {
            let pressTimer;
            
            const startPress = () => {
                pressTimer = setTimeout(() => {
                    // Trigger long press action
                    this.handleLongPressAction(action);
                    
                    // Provide stronger haptic feedback
                    if ('vibrate' in navigator) {
                        navigator.vibrate([10, 50, 10]);
                    }
                }, 500);
            };
            
            const cancelPress = () => {
                clearTimeout(pressTimer);
            };
            
            event.currentTarget.addEventListener('touchstart', startPress);
            event.currentTarget.addEventListener('touchend', cancelPress);
            event.currentTarget.addEventListener('touchmove', cancelPress);
        },
        
        // Handle long press actions
        handleLongPressAction(action) {
            switch (action) {
                case 'timer-reset':
                    if (confirm('Reset timer? This will stop the current timer without saving.')) {
                        this.emergencyReset();
                    }
                    break;
                case 'quick-start':
                    // Quick start with last used settings
                    this.quickStartTimer();
                    break;
                default:
                    console.log('Unknown long press action:', action);
            }
        },
        
        // Quick start timer with last used settings
        quickStartTimer() {
            const lastEntry = this.recentEntries[0];
            if (lastEntry) {
                this.newTimer = {
                    trackable_type: lastEntry.trackable_type === 'App\\Models\\Project' ? 'Project' : 'Issue',
                    trackable_id: lastEntry.trackable_type === 'App\\Models\\Project' ? lastEntry.project_id : lastEntry.issue_id,
                    description: lastEntry.description || ''
                };
                this.startTimer();
            } else {
                this.openStartModal();
            }
        },
        
        // Emergency timer reset
        emergencyReset() {
            this.timer = {
                id: null,
                status: 'stopped',
                description: '',
                trackable_type: '',
                trackable_id: null,
                trackable: null,
                project: null,
                issue: null,
                started_at: null,
                elapsed_seconds: 0
            };
            this.elapsedSeconds = 0;
            this.saveToLocalStorage();
            this.showMessage('Timer reset successfully', 'info');
        }
    };
}
