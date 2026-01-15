<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ExtendedAuth extends BaseConfig
{
    /**
     * Extended session duration (3 months)
     * This setting extends both session and remember me cookie duration
     */
    public int $sessionDuration = 7776000; // 90 days * 24 * 60 * 60 seconds
    
    /**
     * Remember me cookie duration (3 months)
     */
    public int $rememberMeDuration = 7776000; // 90 days * 24 * 60 * 60 seconds
    
    /**
     * Session regeneration interval (24 hours)
     * How often to regenerate session ID for security
     */
    public int $regenerationInterval = 86400; // 24 hours
    
    /**
     * Auto-extend session on activity
     * Whether to automatically extend session when user is active
     */
    public bool $autoExtendOnActivity = true;
    
    /**
     * Minimum activity interval to extend session (1 hour)
     * Only extend session if last activity was more than this interval ago
     */
    public int $minActivityInterval = 3600; // 1 hour
}