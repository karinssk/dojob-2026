/**
 * Task Modal User Info Display
 * Specifically for the DoJob task modal comment section
 * This will display current user info in console when you open task modals
 */

(function() {
    'use strict';

    let currentUser = null;
    
    // Function to display user info in console with nice formatting
    function displayUserInConsole(user) {
        if (!user || !user.id) return;
        
        console.group('👤 Current User Information - Task Modal');
        console.log('%c🆔 User ID: ' + user.id, 'color: #2196F3; font-weight: bold;');
        console.log('%c👋 Name: ' + user.full_name, 'color: #4CAF50; font-weight: bold;');
        console.log('%c📧 Email: ' + user.email, 'color: #FF9800;');
        console.log('%c🏷️ Type: ' + user.user_type, 'color: #9C27B0;');
        
        if (user.image) {
            try {
                // Parse the serialized image data
                const imageMatch = user.image.match(/s:9:"file_name";s:\d+:"([^"]+)"/);
                if (imageMatch) {
                    const imageName = imageMatch[1];
                    console.log('%c🖼️ Profile Image: ' + imageName, 'color: #607D8B;');
                }
            } catch (e) {
                console.log('%c🖼️ Profile Image: ' + user.image, 'color: #607D8B;');
            }
        }
        
        console.log('%c📊 Complete User Data:', 'color: #795548;', user);
        console.groupEnd();
        
        // Store globally for use in comments
        window.currentDoJobUser = user;
    }

    // Fetch current user
    async function fetchCurrentUser() {
        try {
            const response = await fetch('api/current_user', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    currentUser = result.data;
                    displayUserInConsole(currentUser);
                    return currentUser;
                } else {
                    console.warn('⚠️ No authenticated user found');
                    return null;
                }
            } else {
                console.warn(`⚠️ API Error: ${response.status}`);
                return null;
            }
        } catch (error) {
            console.error('❌ Error fetching user:', error);
            return null;
        }
    }

    // Function to show user info when task modal opens
    function onTaskModalOpen() {
        console.log('📋 Task modal opened - displaying current user info...');
        if (currentUser) {
            displayUserInConsole(currentUser);
        } else {
            fetchCurrentUser();
        }
    }

    // Function to inject user info into comment form
    function injectUserInfoIntoCommentForm() {
        if (!currentUser) return;

        // Try to find comment forms and add user info
        const commentForms = document.querySelectorAll('form[action*="comment"], .comment-form, #comment-form');
        
        commentForms.forEach(form => {
            if (!form.querySelector('.current-user-info')) {
                const userInfoDiv = document.createElement('div');
                userInfoDiv.className = 'current-user-info';
                userInfoDiv.style.cssText = `
                    background: #e3f2fd;
                    border: 1px solid #2196f3;
                    border-radius: 4px;
                    padding: 8px 12px;
                    margin: 8px 0;
                    font-size: 12px;
                    color: #1976d2;
                `;
                userInfoDiv.innerHTML = `
                    👤 Commenting as: <strong>${currentUser.full_name}</strong> (ID: ${currentUser.id})
                `;
                form.insertBefore(userInfoDiv, form.firstChild);
            }
        });
    }

    // Watch for modal/dialog openings
    function watchForModals() {
        // Watch for common modal/dialog selectors
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if it's a modal or contains modal-like content
                        if (
                            node.classList?.contains('modal') ||
                            node.classList?.contains('popup') ||
                            node.querySelector?.('.modal') ||
                            node.querySelector?.('.popup') ||
                            node.querySelector?.('[data-toggle="modal"]') ||
                            (node.id && (node.id.includes('modal') || node.id.includes('task')))
                        ) {
                            setTimeout(() => {
                                onTaskModalOpen();
                                injectUserInfoIntoCommentForm();
                            }, 500);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialize when page loads
    function init() {
        console.log('🚀 Task Modal User Info initialized for: ' + window.location.href);
        
        // Fetch user immediately
        fetchCurrentUser().then(user => {
            if (user) {
                // Set up modal watching
                watchForModals();
                
                // Also check periodically for comment forms
                setInterval(() => {
                    if (currentUser) {
                        injectUserInfoIntoCommentForm();
                    }
                }, 3000);
            }
        });

        // Listen for clicks on task-related elements
        document.addEventListener('click', function(e) {
            const target = e.target;
            if (
                target.closest('[data-task-id]') ||
                target.closest('.task-row') ||
                target.closest('.task-item') ||
                target.matches('[onclick*="task"]') ||
                target.textContent.toLowerCase().includes('comment')
            ) {
                setTimeout(() => {
                    onTaskModalOpen();
                    injectUserInfoIntoCommentForm();
                }, 1000);
            }
        });
    }

    // Expose functions globally for manual use
    window.TaskModalUserInfo = {
        getCurrentUser: () => currentUser,
        refreshUser: fetchCurrentUser,
        showUserInfo: () => currentUser ? displayUserInConsole(currentUser) : fetchCurrentUser(),
        injectUserInfo: injectUserInfoIntoCommentForm
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    console.log('%c📋 Task Modal User Info Loaded!', 'color: #4CAF50; font-weight: bold; font-size: 14px;');
    console.log('Available commands: TaskModalUserInfo.showUserInfo(), TaskModalUserInfo.refreshUser()');

})();
