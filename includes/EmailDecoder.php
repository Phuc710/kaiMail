<?php
/**
 * EmailDecoder - Handle email encoding/decoding
 * Decodes MIME-encoded headers and quoted-printable content from Cloudflare Worker
 */

class EmailDecoder
{
    /**
     * Decode MIME-encoded header (RFC 2047)
     * Example: =?UTF-8?Q?Hello_World?= → Hello World
     * Example: =?UTF-8?B?SGVsbG8gV29ybGQ=?= → Hello World
     */
    public static function decodeMimeHeader($input)
    {
        if (empty($input)) {
            return '';
        }

        // Pattern: =?charset?encoding?encoded-text?=
        return preg_replace_callback(
            '/=\?([^?]+)\?([BQbq])\?([^?]*)\?=/i',
            function ($matches) {
                $charset = $matches[1];
                $encoding = strtoupper($matches[2]);
                $encodedText = $matches[3];

                if ($encoding === 'B') {
                    // Base64 decoding
                    $decoded = base64_decode($encodedText, true);
                    if ($decoded !== false) {
                        return mb_convert_encoding($decoded, 'UTF-8', $charset);
                    }
                } elseif ($encoding === 'Q') {
                    // Quoted-Printable decoding (with underscore as space)
                    $withSpaces = str_replace('_', ' ', $encodedText);
                    $decoded = quoted_printable_decode($withSpaces);
                    return mb_convert_encoding($decoded, 'UTF-8', $charset);
                }

                return $matches[0]; // Return original if decode fails
            },
            $input
        );
    }

    /**
     * Decode quoted-printable content
     * Example: "=3D20" → " "
     * Example: "=C3=B6" → "ö"
     */
    public static function decodeQuotedPrintable($input)
    {
        if (empty($input)) {
            return '';
        }

        // Step 1: Remove soft line breaks (= at end of line)
        $input = preg_replace('/=\r?\n/', '', $input);

        // Step 2: Use PHP's built-in quoted_printable_decode
        $decoded = quoted_printable_decode($input);

        // Step 3: Ensure proper UTF-8 encoding
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            $decoded = mb_convert_encoding($decoded, 'UTF-8');
        }

        return $decoded;
    }

    /**
     * Decode email body (may be quoted-printable or base64)
     * This is called from webhook receiver
     */
    public static function decodeEmailBody($body, $encoding = 'quoted-printable')
    {
        if (empty($body)) {
            return '';
        }

        $encoding = strtolower(trim($encoding));

        if ($encoding === 'quoted-printable' || $encoding === 'quoted_printable') {
            return self::decodeQuotedPrintable($body);
        } elseif ($encoding === 'base64') {
            $decoded = base64_decode($body, true);
            if ($decoded !== false) {
                if (!mb_check_encoding($decoded, 'UTF-8')) {
                    $decoded = mb_convert_encoding($decoded, 'UTF-8');
                }
                return $decoded;
            }
        }

        // Return as-is if no encoding or unknown encoding
        if (!mb_check_encoding($body, 'UTF-8')) {
            return mb_convert_encoding($body, 'UTF-8');
        }

        return $body;
    }

    /**
     * Decode both header and body if needed
     * Called when processing webhook data
     */
    public static function processEmail(&$email)
    {
        // Decode subject header
        if (!empty($email['subject'])) {
            $email['subject'] = self::decodeMimeHeader($email['subject']);
        }

        // Decode from_name header
        if (!empty($email['from_name'])) {
            $email['from_name'] = self::decodeMimeHeader($email['from_name']);
        }

        // Decode text body if it contains quoted-printable
        if (!empty($email['text']) && strpos($email['text'], '=') !== false) {
            $email['text'] = self::decodeQuotedPrintable($email['text']);
        }

        // Decode HTML body if it contains quoted-printable
        if (!empty($email['html']) && strpos($email['html'], '=') !== false) {
            $email['html'] = self::decodeQuotedPrintable($email['html']);
        }

        return $email;
    }
}
