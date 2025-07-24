{{-- Timer Widget Component --}}
<div 
    x-data="timerStore()" 
    x-init="init()"
    class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 md:relative md:border md:rounded-lg md:shadow-md md:max-w-md"
    x-on:touchstart="handleTouchStart($event)"
    x-on:touchmove="handleTouchMove($event)"
    x-on:touchend="handleTouchEnd($event)"
>
    {{-- Timer Display --}}
    <div class="p-4">
        {{-- Active Timer Header --}}
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900">
                <span x-show="!timer.id" class="text-gray-500">No Active Timer</span>
                <span x-show="timer.id" class="text-green-600">Active Timer</span>
            </h3>
            <div class="flex items-center space-x-2">
                {{-- Timer Status Indicator --}}
                <div class="flex items-center">
                    <div 
                        x-show="timer.status === 'running'" 
                        class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                    ></div>
                    <div 
                        x-show="timer.status === 'paused'" 
                        class="w-2 h-2 bg-yellow-500 rounded-full"
                    ></div>
                    <div 
                        x-show="!timer.id || timer.status === 'stopped'" 
                        class="w-2 h-2 bg-gray-400 rounded-full"
                    ></div>
                </div>
                
                {{-- Sync Status Indicator --}}
                <div class="flex items-center text-xs" :title="syncStatusText">
                    <svg 
                        x-show="syncStatus === 'syncing'" 
                        class="w-3 h-3 text-blue-500 animate-spin" 
                        fill="none" 
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg 
                        x-show="syncStatus === 'idle'" 
                        class="w-3 h-3 text-green-500" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <svg 
                        x-show="syncStatus === 'error'" 
                        class="w-3 h-3 text-red-500" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <svg 
                        x-show="syncStatus === 'offline'" 
                        class="w-3 h-3 text-gray-500" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 hidden md:inline" :class="syncStatusColor" x-text="syncStatusText"></span>
                </div>
            </div>
        </div>

        {{-- Elapsed Time Display --}}
        <div class="text-center mb-4">
            <div 
                class="text-3xl font-mono font-bold text-gray-900 cursor-pointer select-none swipe-indicator"
                x-text="formattedTime"
                @touchstart="handleLongPress($event, 'timer-reset')"
                @click="timer.id ? null : openStartModal()"
                title="Tap to start timer, long press for options"
            ></div>
            
            {{-- Current Timer Info --}}
            <div x-show="timer.id && timer.status !== 'stopped'" class="mt-2 space-y-1">
                <div class="text-sm font-medium text-gray-800" x-text="currentTimerDisplayName"></div>
                
                {{-- Project info for issues --}}
                <div x-show="timer.project" class="flex items-center justify-center text-xs text-gray-600">
                    <div 
                        class="w-2 h-2 rounded-full mr-1" 
                        :style="'background-color: ' + (timer.project ? timer.project.color : '#6B7280')"
                    ></div>
                    <span x-text="timer.project ? timer.project.name : ''"></span>
                </div>
                
                {{-- Issue priority indicator --}}
                <div x-show="timer.issue" class="flex items-center justify-center text-xs">
                    <span 
                        class="px-2 py-1 rounded-full text-white text-xs font-medium"
                        :style="'background-color: ' + (timer.issue ? timer.issue.priority_color || '#6B7280' : '#6B7280')"
                        x-text="timer.issue ? timer.issue.priority.toUpperCase() : ''"
                    ></span>
                </div>
            </div>
            
            <div x-show="timer.description" class="text-sm text-gray-600 mt-2" x-text="timer.description"></div>
        </div>

        {{-- Timer Controls --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            {{-- Start Timer Button --}}
            <button
                x-show="!timer.id || timer.status === 'stopped'"
                @click="openStartModal()"
                @touchstart="provideTouchFeedback($event); handleLongPress($event, 'quick-start')"
                class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-4 py-3 min-h-[44px] rounded-lg font-medium transition-all duration-200 flex items-center justify-center touch-manipulation select-none"
                title="Tap to start new timer, long press for quick start"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1"></path>
                </svg>
                Start
            </button>

            {{-- Pause Button --}}
            <button
                x-show="timer.status === 'running'"
                @click="pauseTimer()"
                @touchstart="provideTouchFeedback($event)"
                class="bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-800 text-white px-4 py-3 min-h-[44px] rounded-lg font-medium transition-all duration-200 flex items-center justify-center touch-manipulation select-none"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6"></path>
                </svg>
                Pause
            </button>

            {{-- Resume Button --}}
            <button
                x-show="timer.status === 'paused'"
                @click="resumeTimer()"
                @touchstart="provideTouchFeedback($event)"
                class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-4 py-3 min-h-[44px] rounded-lg font-medium transition-all duration-200 flex items-center justify-center touch-manipulation select-none"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1"></path>
                </svg>
                Resume
            </button>

            {{-- Stop Button --}}
            <button
                x-show="timer.id && (timer.status === 'running' || timer.status === 'paused')"
                @click="stopTimer()"
                @touchstart="provideTouchFeedback($event)"
                class="bg-red-600 hover:bg-red-700 active:bg-red-800 text-white px-4 py-3 min-h-[44px] rounded-lg font-medium transition-all duration-200 flex items-center justify-center touch-manipulation select-none"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                </svg>
                Stop
            </button>
        </div>

        {{-- Error Handling and Recovery UI --}}
        <div x-show="syncStatus === 'error' || error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start space-x-2">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-red-800">Timer Error</h4>
                    <p class="text-sm text-red-700 mt-1" x-text="error || 'Synchronization error occurred'"></p>
                    
                    {{-- Error Recovery Options --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button 
                            @click="syncWithServer()" 
                            class="text-xs bg-red-100 hover:bg-red-200 text-red-800 px-2 py-1 rounded border border-red-300 transition-colors"
                        >
                            Retry Sync
                        </button>
                        
                        <button 
                            @click="validateAndCorrectTimerState()" 
                            class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-2 py-1 rounded border border-yellow-300 transition-colors"
                        >
                            Fix State
                        </button>
                        
                        <button 
                            @click="if(confirm('This will reset your timer. Continue?')) { emergencyTimerReset(); }" 
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 px-2 py-1 rounded border border-gray-300 transition-colors"
                        >
                            Reset Timer
                        </button>
                        
                        <button 
                            @click="error = ''; syncStatus = 'idle'" 
                            class="text-xs text-red-600 hover:text-red-800 px-2 py-1 transition-colors"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Offline Mode Indicator --}}
        <div x-show="networkStatus === 'offline'" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <h4 class="text-sm font-medium text-yellow-800">Working Offline</h4>
                    <p class="text-sm text-yellow-700">Timer actions will be saved and synced when connection is restored.</p>
                    <div x-show="offlineQueue.length > 0" class="text-xs text-yellow-600 mt-1">
                        <span x-text="offlineQueue.length"></span> action(s) queued for sync
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Success Messages --}}
        <div x-show="success" x-transition class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-green-700" x-text="success"></p>
                <button 
                    @click="success = ''" 
                    class="ml-auto text-green-600 hover:text-green-800"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Quick Actions (Mobile) --}}
        <div class="mt-3 md:hidden">
            <button
                @click="showRecentEntries = !showRecentEntries"
                class="w-full text-sm text-gray-600 hover:text-gray-800 py-2"
            >
                <span x-show="!showRecentEntries">Show Recent Entries</span>
                <span x-show="showRecentEntries">Hide Recent Entries</span>
            </button>
        </div>
    </div>

    {{-- Recent Time Entries (Collapsible on Mobile) --}}
    <div 
        x-show="showRecentEntries || window.innerWidth >= 768"
        x-transition
        class="border-t border-gray-200 bg-gray-50 p-4"
    >
        <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Entries</h4>
        <div class="space-y-2 max-h-32 overflow-y-auto custom-scrollbar touch-manipulation" style="-webkit-overflow-scrolling: touch;">
            <template x-for="entry in recentEntries" :key="entry.id">
                <div class="flex justify-between items-center text-sm bg-white p-2 rounded border">
                    <div>
                        <div class="font-medium" x-text="entry.display_name || (entry.trackable_type.split('\\\\').pop() + ' #' + entry.trackable_id)"></div>
                        <div class="text-gray-500" x-text="entry.description || 'No description'"></div>
                        <div x-show="entry.project && entry.issue" class="flex items-center text-xs text-gray-600 mt-1">
                            <div 
                                class="w-2 h-2 rounded-full mr-1" 
                                :style="'background-color: ' + (entry.project ? entry.project.color : '#6B7280')"
                            ></div>
                            <span x-text="entry.project ? entry.project.name : ''"></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-mono" x-text="formatDuration(entry.duration_seconds)"></div>
                        <div class="text-xs text-gray-400" x-text="formatDate(entry.created_at)"></div>
                    </div>
                </div>
            </template>
            <div x-show="recentEntries.length === 0" class="text-sm text-gray-500 text-center py-2">
                No recent entries
            </div>
        </div>
    </div>

    {{-- Start Timer Modal --}}
    <div 
        x-show="showStartModal" 
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="showStartModal = false; resetNewTimerForm()"
    >
        <div 
            class="bg-white rounded-lg p-6 w-full max-w-md transform transition-transform"
            x-on:touchstart="handleModalTouchStart($event)"
            x-on:touchmove="handleModalTouchMove($event)"
            x-on:touchend="handleModalTouchEnd($event)"
        >
            <h3 class="text-lg font-semibold mb-4">Start New Timer</h3>
            


            <form @submit.prevent="startTimer()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">What are you working on?</label>
                    <select 
                        x-model="newTimer.trackable_type"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 min-h-[44px] text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 touch-manipulation"
                        required
                        @change="onTrackableTypeChange()"
                    >
                        <option value="">Select type...</option>
                        <option value="Project">Project</option>
                        <option value="Issue">Issue</option>
                    </select>
                    
                    <!-- Project/Issue Selection -->
                    <div class="mb-4" x-show="newTimer.trackable_type">
                        <!-- Project Selection -->
                        <div x-show="newTimer.trackable_type === 'Project'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Project:</label>
                            <select 
                                x-model="newTimer.trackable_id"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 min-h-[44px] text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 touch-manipulation"
                                :required="newTimer.trackable_type === 'Project'"
                                @change="console.log('Selected Project ID:', newTimer.trackable_id)"
                            >
                                <option value="">Select project...</option>
                                <template x-for="project in projects" :key="project.id">
                                    <option :value="project.id" x-text="project.name"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Issue Selection -->
                        <div x-show="newTimer.trackable_type === 'Issue'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Issue:</label>
                            <select 
                                x-model="newTimer.trackable_id"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 min-h-[44px] text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 touch-manipulation"
                                :required="newTimer.trackable_type === 'Issue'"
                                @change="console.log('Selected Issue ID:', newTimer.trackable_id)"
                            >
                                <option value="">Select issue...</option>
                                <template x-for="issue in filteredIssues" :key="issue.id">
                                    <option :value="issue.id" x-text="`${issue.title} (${issue.project ? issue.project.name : 'No Project'})`"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <input 
                        type="text" 
                        x-model="newTimer.description"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 min-h-[44px] text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 touch-manipulation"
                        placeholder="What are you working on?"
                        autocomplete="off"
                        autocapitalize="sentences"
                    >
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button 
                        type="button" 
                        @click="showStartModal = false; resetNewTimerForm()"
                        @touchstart="provideTouchFeedback($event)"
                        class="px-6 py-3 min-h-[44px] text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg font-medium transition-all duration-200 touch-manipulation select-none"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        @touchstart="provideTouchFeedback($event)"
                        class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-6 py-3 min-h-[44px] rounded-lg font-medium transition-all duration-200 touch-manipulation select-none"
                        :disabled="loading"
                    >
                        <span x-show="!loading">Start Timer</span>
                        <span x-show="loading">Starting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Error Message --}}
    <div 
        x-show="error" 
        x-transition
        class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50"
    >
        <span x-text="error"></span>
        <button @click="error = ''" class="ml-2 text-red-500 hover:text-red-700">&times;</button>
    </div>

    {{-- Success Message --}}
    <div 
        x-show="success" 
        x-transition
        class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50"
    >
        <span x-text="success"></span>
        <button @click="success = ''" class="ml-2 text-green-500 hover:text-green-700">&times;</button>
    </div>
</div>
