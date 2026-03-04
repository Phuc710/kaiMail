<?php
/**
 * Message Service
 * Handle message retrieval and processing logic centralized here
 */

require_once __DIR__ . '/../config/database.php';

class MessageService
{
    /**
     * Get a single message by ID
     * Automatically helper to decode content and mark as read
     */
    public static function getMessage(int $id)
    {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT m.*, UNIX_TIMESTAMP(m.received_at) as ts, e.email 
            FROM messages m 
            JOIN emails e ON m.email_id = e.id 
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $message = $stmt->fetch();

        if (!$message) {
            return null;
        }

        // Mark as read
        $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$id]);

        return self::processMessage($message);
    }

    /**
     * Get messages list for an email ID
     */
    public static function getMessagesByEmailId(int $emailId, int $limit = 100)
    {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT id, from_email, from_name, subject, is_read, received_at,
                   UNIX_TIMESTAMP(received_at) as ts,
                   LEFT(body_text, 100) as preview
            FROM messages 
            WHERE email_id = ?
            ORDER BY received_at DESC
            LIMIT ?
        ");
        // Bind limit as integer for PDO consistency in some drivers (though execute array works usually)
        $stmt->bindValue(1, $emailId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Delete messages
     */
    public static function deleteMessages(int $emailId, array $ids = [], bool $deleteAll = false): int
    {
        $db = getDB();

        if ($deleteAll && $emailId) {
            $stmt = $db->prepare("DELETE FROM messages WHERE email_id = ?");
            $stmt->execute([$emailId]);
            return $stmt->rowCount();
        } elseif (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM messages WHERE id IN ($placeholders)";

            // If emailId is provided, safer to check ownership (optional based on usage)
            if ($emailId) {
                $sql .= " AND email_id = " . $emailId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($ids);
            return $stmt->rowCount();
        }

        return 0;
    }

    /**
     * Process message content (Decode Quoted-Printable, etc)
     */
    private static function processMessage(array $message): array
    {
        $bodyText = $message['body_text'] ?? '';
        $bodyHtml = $message['body_html'] ?? '';

        // Decode Quoted-Printable if detected
        if (strpos($bodyText, '=20') !== false || strpos($bodyText, '=3D') !== false) {
            $bodyText = quoted_printable_decode($bodyText);
        }
        if (strpos($bodyHtml, '=20') !== false || strpos($bodyHtml, '=3D') !== false) {
            $bodyHtml = quoted_printable_decode($bodyHtml);
        }

        // Clean up response structure
        // Create a smarter display name
        $fromName = $message['from_name'];
        $fromEmail = $message['from_email'];

        // Logic to detect "bad" or automated sender names
        // Matches: Empty, "Em1234", "bounce...", or just the email address itself
        $isSuspicious = empty($fromName) ||
            preg_match('/^(Em|Ma|No|Auto)\d+$/i', $fromName) ||
            strpos(strtolower($fromName), 'bounce') !== false ||
            strpos(strtolower($fromName), 'no-reply') !== false ||
            $fromName === $fromEmail;

        if ($isSuspicious || strpos($fromEmail, '.openai.com') !== false) {
            // Extract domain part
            $domain = substr(strrchr($fromEmail, "@"), 1);

            // Split by dot
            $parts = explode('.', $domain);
            $count = count($parts);

            // Dictionary for special brand casing
            $knownBrands = [
                'openai' => 'OpenAI',
                'github' => 'GitHub',
                'gitlab' => 'GitLab',
                'facebook' => 'Facebook',
                'youtube' => 'YouTube',
                'linkedin' => 'LinkedIn',
                'wordpress' => 'WordPress',
                'paypal' => 'PayPal',
                'microsoft' => 'Microsoft',
                'apple' => 'Apple',
                'google' => 'Google',
                'twitter' => 'Twitter',
                'amazon' => 'Amazon',
                'vercel' => 'Vercel',
                'netflix' => 'Netflix',
                'spotify' => 'Spotify'
            ];

            if ($count >= 2) {
                // Heuristic: Extract the main company name before the TLD
                // e.g. notify.github.com -> [notify, github, com] -> github

                $tld = end($parts);
                $main = prev($parts); // Second to last

                // Handle compound TLDs like co.uk, com.vn, com.br
                // If the 'main' part is short (<=3) and generic (co, com, net), take the next one
                if (strlen($main) <= 3 && $count >= 3 && in_array(strtolower($main), ['co', 'com', 'net', 'org', 'gov', 'edu'])) {
                    $main = prev($parts); // Third to last
                }

                $companyKey = strtolower($main);
                if (isset($knownBrands[$companyKey])) {
                    $fromName = $knownBrands[$companyKey];
                } else {
                    // Generic fallback: Capitalize the domain name
                    // e.g. 'linear' -> 'Linear', 'discord' -> 'Discord'
                    $fromName = ucfirst($main);
                }
            } else {
                // Fallback for weird domains (e.g. localhost)
                $parts = explode('@', $fromEmail);
                $fromName = ucfirst($parts[0]);
            }
        }

        // Clean up response structure
        return [
            'id' => $message['id'],
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'subject' => $message['subject'],
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'is_read' => true,
            'received_at' => $message['received_at'],
            'created_ts' => $message['ts'] ?? null,
            'recipient' => $message['email'] ?? null // Useful for admin
        ];
    }
}
