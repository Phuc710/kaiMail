<?php
/**
 * Email Service
 * Handles email creation and message retrieval
 */

require_once __DIR__ . '/../../includes/NameGenerator.php';
require_once __DIR__ . '/BaseService.php';

class EmailService extends BaseService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Calculate expiry date based on type
     * 
     * @param string $type Expiry type: '30days', '1year', '2years', 'forever'
     * @return string|null Expiry datetime or null for forever
     */
    public function calculateExpiry(string $type): ?string
    {
        if ($type === 'forever') {
            return null;
        }

        return match ($type) {
            '30days' => date('Y-m-d H:i:s', strtotime('+30 days')),
            '1year' => date('Y-m-d H:i:s', strtotime('+1 year')),
            '2years' => date('Y-m-d H:i:s', strtotime('+2 years')),
            default => null
        };
    }

    /**
     * Create new email
     * 
     * @param int $domainId Domain ID
     * @param string $domain Domain name
     * @param string $nameType Name type
     * @param string $expiryType Expiry type
     * @return string Created email address
     * @throws PDOException if email already exists
     */
    public function createEmail(int $domainId, string $domain, string $nameType, string $expiryType): string
    {
        // Use existing NameGenerator class
        $username = NameGenerator::generateUsername($nameType);
        $email = $username . '@' . $domain;
        $expiresAt = $this->calculateExpiry($expiryType);

        $stmt = $this->db->prepare("
            INSERT INTO emails (domain_id, email, name_type, expiry_type, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$domainId, $email, $nameType, $expiryType, $expiresAt]);

        return $email;
    }

    /**
     * Get email data by email address
     * 
     * @param string $email Email address
     * @return array|null Email data or null if not found
     */
    public function getEmailData(string $email): ?array
    {
        if (!$this->isValidEmail($email)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, email, is_expired FROM emails WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get messages for email
     * 
     * @param int $emailId Email ID
     * @return array List of messages
     */
    public function getMessages(int $emailId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                from_email,
                from_name,
                subject,
                body_text,
                body_html,
                is_read,
                received_at
            FROM messages 
            WHERE email_id = ? 
            ORDER BY received_at DESC
        ");

        $stmt->execute([$emailId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
