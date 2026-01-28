<?php
/**
 * Email Controller
 * Handles email-related API endpoints
 */

class EmailController
{
    private EmailService $emailService;
    private DomainService $domainService;

    public function __construct(EmailService $emailService, DomainService $domainService)
    {
        $this->emailService = $emailService;
        $this->domainService = $domainService;
    }

    /**
     * Create new email(s)
     * POST /api/emails
     */
    public function create(): void
    {
        $input = getJsonInput();

        // Validate input
        $count = (int) ($input['count'] ?? 1);
        $nameType = $input['name_type'] ?? 'en';
        $expiryType = $input['expiry_type'] ?? 'forever';
        $domain = strtolower(trim($input['domain'] ?? ''));

        // Validation
        if (empty($domain)) {
            jsonResponse(['error' => 'Domain is required'], 400);
        }

        if ($count < 1 || $count > 100) {
            jsonResponse(['error' => 'Count must be between 1 and 100'], 400);
        }

        if (!in_array($nameType, ['vn', 'en', 'custom'])) {
            jsonResponse(['error' => 'Invalid name_type. Must be: vn, en, or custom'], 400);
        }

        if (!in_array($expiryType, ['30days', '1year', '2years', 'forever'])) {
            jsonResponse(['error' => 'Invalid expiry_type. Must be: 30days, 1year, 2years, or forever'], 400);
        }

        // Check domain exists
        $domainId = $this->domainService->getDomainId($domain);
        if (!$domainId) {
            jsonResponse([
                'error' => 'Domain not found or inactive',
                'domain' => $domain,
                'available_domains' => $this->domainService->getActiveDomains()
            ], 404);
        }

        // Create emails
        $createdEmails = [];
        $attempts = 0;
        $maxAttempts = $count * 10; // Prevent infinite loop

        while (count($createdEmails) < $count && $attempts < $maxAttempts) {
            $attempts++;
            try {
                $email = $this->emailService->createEmail($domainId, $domain, $nameType, $expiryType);
                $createdEmails[] = $email;
            } catch (PDOException $e) {
                // Email already exists, try again
                continue;
            }
        }

        if (count($createdEmails) < $count) {
            jsonResponse([
                'error' => 'Could not generate requested number of unique emails',
                'requested' => $count,
                'created' => count($createdEmails),
                'emails' => $createdEmails
            ], 500);
        }

        jsonResponse([
            'success' => true,
            'count' => count($createdEmails),
            'emails' => $createdEmails
        ]);
    }

    /**
     * Get messages for email
     * GET /api/emails/{email}/messages
     */
    public function getMessages(string $email): void
    {
        // Validate email format
        $email = strtolower(trim($email));

        if (empty($email)) {
            jsonResponse(['error' => 'Email is required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Invalid email format'], 400);
        }

        // Get email data
        $emailData = $this->emailService->getEmailData($email);

        if (!$emailData) {
            jsonResponse(['error' => 'Email not found'], 404);
        }

        if ($emailData['is_expired']) {
            jsonResponse(['error' => 'Email has expired'], 410);
        }

        // Get messages
        $messages = $this->emailService->getMessages($emailData['id']);

        jsonResponse([
            'success' => true,
            'email' => $emailData['email'],
            'message_count' => count($messages),
            'messages' => $messages
        ]);
    }
}
