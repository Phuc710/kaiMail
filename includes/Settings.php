<?php
/**
 * Settings Helper Functions
 * KaiMail - Settings Management
 * 
 * Usage:
 *   Settings::get('default_domain');
 *   Settings::set('api_domains', 'domain1.com,domain2.com');
 *   Settings::addDomain('newdomain.com');
 *   Settings::removeDomain('olddomain.com');
 */

class Settings
{
    private static $cache = [];
    private static $db = null;

    /**
     * Initialize database connection
     */
    private static function init()
    {
        if (self::$db === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$db = getDB();
        }
    }

    /**
     * Get setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    public static function get($key, $default = null)
    {
        self::init();

        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $stmt = self::$db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();

            if ($result) {
                self::$cache[$key] = $result['setting_value'];
                return $result['setting_value'];
            }
        } catch (Exception $e) {
            error_log("Settings::get error: " . $e->getMessage());
        }

        return $default;
    }

    /**
     * Set setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success
     */
    public static function set($key, $value)
    {
        self::init();

        try {
            // Try update first
            $stmt = self::$db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);

            // If no rows affected, insert
            if ($stmt->rowCount() === 0) {
                $stmt = self::$db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }

            // Clear cache
            unset(self::$cache[$key]);
            return true;
        } catch (Exception $e) {
            error_log("Settings::set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all API domains
     * 
     * @return array List of domains
     */
    public static function getApiDomains()
    {
        $domainsStr = self::get('api_domains', 'kaishop.id.vn,trongnghia.store');
        return array_filter(array_map('trim', explode(',', $domainsStr)));
    }

    /**
     * Add domain to API domains
     * 
     * @param string $domain Domain to add
     * @return bool Success
     */
    public static function addDomain($domain)
    {
        $domain = strtolower(trim($domain));

        // Validate domain format
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $domain)) {
            error_log("Invalid domain format: $domain");
            return false;
        }

        $domains = self::getApiDomains();

        // Check if already exists
        if (in_array($domain, $domains)) {
            return true; // Already exists, not an error
        }

        $domains[] = $domain;
        $domainsStr = implode(',', $domains);

        return self::set('api_domains', $domainsStr);
    }

    /**
     * Remove domain from API domains
     * 
     * @param string $domain Domain to remove
     * @return bool Success
     */
    public static function removeDomain($domain)
    {
        $domain = strtolower(trim($domain));
        $domains = self::getApiDomains();

        // Filter out the domain
        $domains = array_filter($domains, function ($d) use ($domain) {
            return $d !== $domain;
        });

        if (empty($domains)) {
            error_log("Cannot remove last domain");
            return false;
        }

        $domainsStr = implode(',', $domains);
        return self::set('api_domains', $domainsStr);
    }

    /**
     * Get all settings
     * 
     * @return array All settings as key-value
     */
    public static function getAll()
    {
        self::init();

        try {
            $stmt = self::$db->query("SELECT setting_key, setting_value FROM settings");
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            error_log("Settings::getAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear cache
     */
    public static function clearCache()
    {
        self::$cache = [];
    }

    /**
     * Display all settings (for debugging)
     */
    public static function debug()
    {
        echo "<h3>Settings Debug</h3>";
        echo "<pre>";
        print_r(self::getAll());
        echo "</pre>";
    }
}
