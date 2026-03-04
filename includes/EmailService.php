<?php
/**
 * Email Service
 * Centralized email management logic
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NameGenerator.php';

class EmailService
{
    /**
     * Create new emails
     */
    public static function createEmails(array $options, &$errors = []): array
    {
        $db = getDB();

        $nameType = $options['name_type'] ?? 'vn';
        $expiryType = 'forever';
        $customEmail = trim($options['email'] ?? '');
        $domain = trim($options['domain'] ?? '');
        $quantity = min(max(1, (int) ($options['quantity'] ?? 1)), 10);

        if (empty($domain)) {
            // Need logical fallback or error handling by caller before here ideally
            // But let's check DB for default if empty
            $stmt = $db->query("SELECT domain FROM domains WHERE is_active = 1 LIMIT 1");
            $row = $stmt->fetch();
            if ($row)
                $domain = $row['domain'];
            else {
                $errors[] = 'No domain available';
                return [];
            }
        }

        // Get domain ID
        $stmt = $db->prepare("SELECT id FROM domains WHERE domain = ?");
        $stmt->execute([$domain]);
        $domainRow = $stmt->fetch();

        if (!$domainRow) {
            $errors[] = "Domain not found: $domain";
            return [];
        }
        $domainId = $domainRow['id'];

        $createdEmails = [];

        for ($i = 0; $i < $quantity; $i++) {
            // Generate Username
            if ($nameType === 'custom' && $customEmail) {
                if (!preg_match('/^[a-z0-9]+$/', $customEmail)) {
                    $errors[] = 'Email can only contain lowercase letters and numbers';
                    continue;
                }
                $username = $customEmail . ($quantity > 1 ? '_' . ($i + 1) : '');
                $actualNameType = 'custom';
            } else {
                $username = NameGenerator::generateUsername($nameType);
                $actualNameType = $nameType;
            }

            $email = $username . '@' . $domain;

            // Check duplicate
            $stmt = $db->prepare("SELECT id FROM emails WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email exists: $email";
                continue;
            }

            // All generated emails are permanent.
            $expiresAt = null;

            // Insert
            $stmt = $db->prepare("
                INSERT INTO emails (domain_id, email, name_type, expiry_type, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$domainId, $email, $actualNameType, $expiryType, $expiresAt]);

            $createdEmails[] = [
                'id' => $db->lastInsertId(),
                'email' => $email,
                'name_type' => $actualNameType,
                'expiry_type' => $expiryType,
                'expires_at' => $expiresAt
            ];
        }

        return $createdEmails;
    }

    /**
     * Delete emails
     */
    public static function deleteEmails(array $ids, bool $deleteAll = false, string $filter = ''): int
    {
        $db = getDB();

        if ($deleteAll && $filter === 'expired') {
            $stmt = $db->prepare("DELETE FROM emails WHERE is_expired = 1");
            $stmt->execute();
            return $stmt->rowCount();
        } elseif (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM emails WHERE id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute($ids);
            return $stmt->rowCount();
        }

        return 0;
    }

    private static function calculateExpiry($type)
    {
        return null;
    }
}
