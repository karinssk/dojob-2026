/**
 * Console User Info Display
 * This script automatically displays current user information in the browser console
 * after successful login to the DoJob application.
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        apiEndpoint: 'api/current_user', // Use CodeIgniter URL format (underscores, not hyphens)
        displayInterval: 5000, // Check every 5 seconds
        maxRetries: 3,
        debug: true
    };

    let userCheckInterval = null;
    let retryCount = 0;
    let lastKnownUser = null;

    /**
     * Display user information in console with nice formatting
     */
    function displayUserInfo(user) {
        if (!user || !user.id) {
            console.warn('🚫 No user data available');
            return;
        }

        // Check if this is the same user we already displayed
        if (lastKnownUser && lastKnownUser.id === user.id) {
            return; // Don't spam the console with the same user info
        }

        console.group('👤 Current User Information');
        console.log('🆔 User ID:', user.id);
        console.log('👋 Name:', `${user.first_name} ${user.last_name}`);
        console.log('📧 Email:', user.email);
        console.log('🏷️ User Type:', user.user_type);
        
        if (user.image) {
            console.log('🖼️ Profile Image:', user.image);
        }
        
        console.log('📊 Full User Object:', user);
        console.groupEnd();

        // Store the last known user to avoid duplicate logs
        lastKnownUser = user;
    }

    /**
     * Fetch current user information from the API
     */
    async function fetchCurrentUser() {
        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'GET',
                credentials: 'include', // Include cookies for session
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                
                if (result.success && result.data) {
                    displayUserInfo(result.data);
                    retryCount = 0; // Reset retry count on success
                    
                    // If we found a user, we can reduce the checking frequency
                    if (userCheckInterval) {
                        clearInterval(userCheckInterval);
                        userCheckInterval = setInterval(checkForUser, 30000); // Check every 30 seconds
                    }
                } else {
                    if (CONFIG.debug) {
                        console.log('👤 No authenticated user found:', result);
                    }
                }
            } else if (response.status === 401) {
                if (CONFIG.debug) {
                    console.log('🔒 User not authenticated (401)');
                }
            } else {
                console.warn(`🚨 API Error: ${response.status} - ${response.statusText}`);
            }
        } catch (error) {
            retryCount++;
            if (retryCount <= CONFIG.maxRetries) {
                if (CONFIG.debug) {
                    console.log(`🔄 Retry ${retryCount}/${CONFIG.maxRetries} - Error fetching user:`, error.message);
                }
            } else {
                console.error('❌ Failed to fetch user after maximum retries:', error);
                // Stop checking after max retries
                if (userCheckInterval) {
                    clearInterval(userCheckInterval);
                }
            }
        }
    }

    /**
     * Check for user with retry logic
     */
    function checkForUser() {
        fetchCurrentUser();
    }

    /**
     * Initialize the user checking system
     */
    function init() {
        console.log('🚀 Console User Info Display initialized');
        
        // Check immediately
        checkForUser();
        
        // Set up periodic checking
        userCheckInterval = setInterval(checkForUser, CONFIG.displayInterval);
        
        // Also check when the page becomes visible (user switched back to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkForUser();
            }
        });

        // Check when user navigates (for SPA-like behavior)
        window.addEventListener('popstate', function() {
            setTimeout(checkForUser, 1000); // Delay to allow page to load
        });
    }

    /**
     * Stop the user checking system
     */
    function stop() {
        if (userCheckInterval) {
            clearInterval(userCheckInterval);
            userCheckInterval = null;
        }
        console.log('🛑 Console User Info Display stopped');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose functions globally for manual control
    window.ConsoleUserInfo = {
        start: init,
        stop: stop,
        check: checkForUser,
        getCurrentUser: fetchCurrentUser
    };

    // Also check immediately if API endpoint exists
    fetchCurrentUser();

})();

// Welcome message
console.log('%c📋 DoJob Console User Info Loaded! 📋', 'color: #4CAF50; font-weight: bold; font-size: 14px;');
console.log('Use ConsoleUserInfo.check() to manually check current user');
console.log('Use ConsoleUserInfo.stop() to disable automatic checking');
