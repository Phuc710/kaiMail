<?php
/**
 * Domain Service
 * Handles domain validation and retrieval
 */

require_once __DIR__ . '/BaseService.php';

class DomainService extends BaseService
{
    /**
     * Validate if domain exists and is active
     * 
     * @param string $domain Domain name to validate
     * @return bool True if valid
     */
    public function isValid(string $domain): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM domains WHERE domain = ? AND is_active = 1");
        $stmt->execute([$domain]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get domain ID
     * 
     * @param string $domain Domain name
     * @return int|null Domain ID or null if not found
     */
    public function getDomainId(string $domain): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM domains WHERE domain = ? AND is_active = 1");
        $stmt->execute([$domain]);
        $result = $stmt->fetch();

        return $result ? (int) $result['id'] : null;
    }

    /**
     * Get all active domains
     * 
     * @return array List of active domains
     */
    public function getActiveDomains(): array
    {
        $stmt = $this->db->query("SELECT domain FROM domains WHERE is_active = 1 ORDER BY domain");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
