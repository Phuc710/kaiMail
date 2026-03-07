const CONFIG = {
  // Your webhook URL (the PHP endpoint that receives emails)
  webhookUrl: "https://tmail.kaishop.id.vn/api/webhook/receive-email.php",

  // Secret key - MUST MATCH the secret in config/webhook.php
  webhookSecret: "65a276de438f97d2b4496724e59d18d443168d3d2ed",

  // Your domain (for logging)
  domain: "kaishop.id.vn",
};

export default {
  // Handle HTTP requests (when visiting Worker URL)
  async fetch(request, env, ctx) {
    return new Response("✅ Tmail Worker is RUNNING!\n📧 Email routing is active.\n🌐 kaishop.id.vn", {
      headers: { "content-type": "text/plain;charset=UTF-8" },
    });
  },

  // Handle incoming emails
  async email(message, env, ctx) {
    try {
      // Extract basic info
      const to = message.to;
      const from = message.from;

      // Decode subject (may be MIME-encoded)
      const rawSubject = message.headers.get("subject") || "(No subject)";
      const subject = decodeMimeHeader(rawSubject);

      const messageId = message.headers.get("message-id") || `msg_${Date.now()}`;

      // Get sender name from "From" header and decode it
      const fromHeader = message.headers.get("from") || from;
      let fromName = "";
      const nameMatch = fromHeader.match(/^"?([^"<]+)"?\s*<.*>$/);
      if (nameMatch) {
        fromName = decodeMimeHeader(nameMatch[1].trim());
      }

      // ----- Read raw MIME email -----
      const rawResponse = new Response(message.raw);
      const buffer = await rawResponse.arrayBuffer();
      const raw = new TextDecoder("utf-8").decode(buffer);

      // ===== Parse content with proper encoding =====
      let textBody = "";
      let htmlBody = "";

      // Try to extract text/plain part with better regex
      const plainMatch = extractMimePart(raw, "text/plain");
      if (plainMatch) {
        textBody = plainMatch;
      }

      // Try to extract text/html part with better regex
      const htmlMatch = extractMimePart(raw, "text/html");
      if (htmlMatch) {
        htmlBody = htmlMatch;
      }

      // Fallback: if no parts found, use raw body
      if (!textBody && !htmlBody) {
        const bodyStart = raw.indexOf("\n\n");
        if (bodyStart !== -1) {
          textBody = raw.substring(bodyStart + 2).trim();
        }
      }

      // Prepare webhook payload with Vietnam timezone (GMT+7)
      const now = new Date();
      const vietnamTime = new Date(now.getTime() + (7 * 60 * 60 * 1000));
      const received_at = vietnamTime.toISOString().slice(0, 19).replace('T', ' ');

      const payload = {
        to: to,
        from: from,
        from_name: fromName,
        subject: subject,
        message_id: messageId,
        text: textBody,
        html: htmlBody,
        received_at: received_at,
      };

      // Send to webhook
      const response = await fetch(CONFIG.webhookUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Webhook-Secret": CONFIG.webhookSecret,
        },
        body: JSON.stringify(payload),
      });

      const responseText = await response.text();

      if (!response.ok) {
        console.error(`❌ Webhook failed: ${response.status} - ${responseText}`);
      } else {
        console.log(`✅ Email forwarded successfully: ${to}`);
      }
    } catch (error) {
      console.error(`❌ Error processing email: ${error.message}`);
      console.error(error.stack);
    }
  },
};

// =====================================================
//       EXTRACT MIME PART WITH DECODING
// =====================================================
function extractMimePart(raw, contentType) {
  // More robust regex to find content type section
  const regex = new RegExp(
    `Content-Type:\\s*${contentType.replace('/', '\\/')}[\\s\\S]*?\\r?\\n\\r?\\n([\\s\\S]*?)(?=\\r?\\n--[\\w-]+|$)`,
    'i'
  );

  const match = raw.match(regex);
  if (!match) return "";

  const partFull = match[0];
  let content = match[1];

  // Extract encoding
  const encMatch = partFull.match(/Content-Transfer-Encoding:\s*([^\r\n]+)/i);
  const encoding = encMatch ? encMatch[1].toLowerCase().trim() : "";

  // Decode based on encoding
  if (encoding === "quoted-printable") {
    return decodeQuotedPrintableUtf8(content);
  } else if (encoding === "base64") {
    return decodeBase64Utf8(content);
  } else {
    return content.trim();
  }
}

// =====================================================
//       DECODE MIME-ENCODED HEADERS (RFC 2047)
//       Example: =?UTF-8?Q?Hello?= or =?UTF-8?B?SGVsbG8=?=
// =====================================================
function decodeMimeHeader(input) {
  if (!input) return "";

  // Pattern: =?charset?encoding?encoded-text?=
  const mimePattern = /=\?([^?]+)\?([BQbq])\?([^?]*)\?=/g;

  return input.replace(mimePattern, (match, charset, encoding, encodedText) => {
    try {
      if (encoding.toUpperCase() === 'B') {
        // Base64 encoding
        return decodeBase64Utf8(encodedText);
      } else if (encoding.toUpperCase() === 'Q') {
        // Quoted-Printable encoding (with underscore as space)
        const withSpaces = encodedText.replace(/_/g, ' ');
        return decodeQuotedPrintableUtf8(withSpaces);
      }
    } catch (e) {
      console.error('MIME decode error:', e);
      return match; // Return original if decode fails
    }
    return match;
  });
}

// =====================================================
//       QUOTED-PRINTABLE UTF-8 DECODER
// =====================================================
function decodeQuotedPrintableUtf8(input) {
  if (!input) return "";

  // Remove soft line breaks: "=\r\n" or "=\n"
  let cleaned = input.replace(/=\r?\n/g, "");

  const bytes = [];
  for (let i = 0; i < cleaned.length; i++) {
    const ch = cleaned[i];
    if (ch === "=") {
      const hex = cleaned.slice(i + 1, i + 3);
      if (/^[0-9A-Fa-f]{2}$/.test(hex)) {
        bytes.push(parseInt(hex, 16));
        i += 2;
      } else {
        // Invalid sequence, keep the = character
        bytes.push("=".charCodeAt(0));
      }
    } else {
      bytes.push(ch.charCodeAt(0));
    }
  }

  try {
    return new TextDecoder("utf-8").decode(new Uint8Array(bytes));
  } catch (e) {
    console.error('UTF-8 decode error:', e);
    return input; // Return original if decode fails
  }
}

// =====================================================
//          BASE64 UTF-8 DECODER
// =====================================================
function decodeBase64Utf8(input) {
  if (!input) return "";

  try {
    const cleaned = input.replace(/\s+/g, "");
    const binary = atob(cleaned);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }

    return new TextDecoder("utf-8").decode(bytes);
  } catch (e) {
    console.error('Base64 decode error:', e);
    return input; // Return original if decode fails
  }
}