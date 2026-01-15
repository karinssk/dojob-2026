const { pool } = require("../config/database");

/**
 * Get current user from PHP session
 * This integrates with your existing PHP application's session system
 */
async function getCurrentUser(req) {
  try {
    // Method 1: Try to get from PHP application (most reliable)
    const phpUser = await getCurrentUserFromPHP(req);
    if (phpUser && phpUser.id) {
      console.log(`👤 User from PHP: ${phpUser.first_name} ${phpUser.last_name} (ID: ${phpUser.id})`);
      return phpUser;
    }

    // Method 2: Try to get user ID from request headers (if frontend sends it)
    let userId = req.headers["x-user-id"];
    
    if (userId) {
      console.log(`👤 User ID from header: ${userId}`);
      return await getUserById(userId);
    }

    // Method 3: Try to get from session cookie (if available)
    const sessionCookie = req.headers.cookie;
    if (sessionCookie) {
      // Extract PHP session ID from cookie
      const sessionMatch = sessionCookie.match(/PHPSESSID=([^;]+)/);
      if (sessionMatch) {
        const sessionId = sessionMatch[1];
        console.log(`🍪 PHP Session ID: ${sessionId}`);
        
        // Try to get user from session (you might need to adjust this based on your session storage)
        userId = await getUserFromSession(sessionId);
        if (userId) {
          return await getUserById(userId);
        }
      }
    }

    // Method 4: No valid user found
    console.warn("⚠️ Could not determine current user - no valid session found");
    return null;
    
  } catch (error) {
    console.error("❌ Error getting current user:", error);
    return null;
  }
}

/**
 * Get user by ID from database
 */
async function getUserById(userId) {
  try {
    const query = `
      SELECT id, first_name, last_name, email, image, user_type
      FROM rise_users 
      WHERE id = ? AND deleted = 0 AND status = 'active'
    `;
    
    const [rows] = await pool.execute(query, [userId]);
    
    if (rows.length > 0) {
      return rows[0];
    }
    
    return null;
  } catch (error) {
    console.error("❌ Error fetching user by ID:", error);
    return null;
  }
}

/**
 * Get user ID from PHP session
 * This is a placeholder - you'll need to implement based on your session storage
 */
async function getUserFromSession(sessionId) {
  try {
    // Option 1: If sessions are stored in database
    const query = `
      SELECT user_id FROM rise_sessions 
      WHERE session_id = ? AND expires > NOW()
    `;
    
    try {
      const [rows] = await pool.execute(query, [sessionId]);
      if (rows.length > 0) {
        return rows[0].user_id;
      }
    } catch (dbError) {
      // Session table might not exist, that's okay
      console.log("📝 Session table not found, trying alternative methods");
    }

    // Option 2: If sessions are stored in files (common PHP setup)
    // You might need to read session files from /tmp or your session.save_path
    
    // Option 3: Make HTTP request to your PHP application to validate session
    // This is more reliable but adds HTTP overhead
    
    return null;
  } catch (error) {
    console.error("❌ Error getting user from session:", error);
    return null;
  }
}

/**
 * Enhanced method: Make HTTP request to PHP app to get current user
 * This is the most reliable method as it uses your existing PHP session system
 */
async function getCurrentUserFromPHP(req) {
  try {
    const fetch = require('node-fetch');
    
    // Forward cookies to your PHP application
    const cookies = req.headers.cookie || '';
    console.log(`🍪 Forwarding cookies to PHP: ${cookies}`);
    
    // Make request to your PHP endpoint that returns current user info
    const response = await fetch('http://localhost:8888/dojob/api-user-simple.php', {
      method: 'GET',
      headers: {
        'Cookie': cookies,
        'User-Agent': 'DoJob-NodeJS-API',
      }
    });
    
    console.log(`📡 PHP endpoint response status: ${response.status}`);
    
    if (response.ok) {
      const result = await response.json();
      console.log(`� P HP endpoint response:`, result);
      
      if (result.success && result.data) {
        const userData = result.data;
        console.log(`👤 Current user from PHP: ${userData.first_name} ${userData.last_name} (ID: ${userData.id})`);
        return {
          id: userData.id,
          first_name: userData.first_name,
          last_name: userData.last_name,
          email: userData.email,
          image: userData.image,
          user_type: userData.user_type
        };
      } else {
        console.warn(`⚠️ PHP user endpoint returned error: ${result.error}`);
        return null;
      }
    } else {
      const errorText = await response.text();
      console.warn(`⚠️ PHP user endpoint returned ${response.status}: ${errorText}`);
      return null;
    }
    
  } catch (error) {
    console.error("❌ Error getting user from PHP:", error);
    return null;
  }
}

/**
 * Get current user ID (simplified version for backward compatibility)
 */
async function getCurrentUserId(req) {
  const user = await getCurrentUser(req);
  if (user && user.id) {
    console.log(`👤 Auto-detected user ID: ${user.id}`);
    return user.id;
  }
  
  console.warn("⚠️ No authenticated user found - operations may fail");
  return null;
}

module.exports = {
  getCurrentUser,
  getCurrentUserId,
  getCurrentUserFromPHP,
  getUserById
};