function getConfig(env) {
  return {
    webhookUrl: env.WEBHOOK_URL || "",
    webhookSecret: env.WEBHOOK_SECRET || "",
  };
}

export default {
  // Handle HTTP requests (when visiting Worker URL)
  async fetch(request, env, ctx) {
    return new Response(`KaiMail Worker is running.\nEmail routing is active.`, {
      headers: { "content-type": "text/plain;charset=UTF-8" },
    });
  },

  // Handle incoming emails - Universal for all domains
  async email(message, env, ctx) {
    const config = getConfig(env);

    if (!config.webhookUrl || !config.webhookSecret) {
      console.error("Missing Worker env vars: WEBHOOK_URL or WEBHOOK_SECRET");
      return;
    }

    try {
      // Extract basic info
      const to = message.to;
      const from = message.from;

      // Get subject (may be encoded)
      const subject = message.headers.get("subject") || "(No subject)";

      const messageId = message.headers.get("message-id") || `msg_${Date.now()}`;

      // Get sender name from "From" header
      const fromHeader = message.headers.get("from") || from;
      let fromName = "";
      const nameMatch = fromHeader.match(/^"?([^"<]+)"?\s*<.*>$/);
      if (nameMatch) {
        fromName = nameMatch[1].trim();
      }

      // ----- Read raw MIME email -----
      const rawResponse = new Response(message.raw);
      const buffer = await rawResponse.arrayBuffer();
      const raw = new TextDecoder("utf-8").decode(buffer);

      // ===== Extract text/plain and text/html (RAW, no decode) =====
      let textBody = "";
      let htmlBody = "";

      // Try to extract text/plain part
      const plainMatch = extractMimePart(raw, "text/plain");
      if (plainMatch) {
        textBody = plainMatch;
      }

      // Try to extract text/html part
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

      // Prepare webhook payload with Vietnam timezone (Asia/Ho_Chi_Minh, GMT+7)
      const received_at = formatVietnamDateTime();

      const payload = {
        to,
        from,
        from_name: fromName,
        subject,
        message_id: messageId,
        text: textBody,
        html: htmlBody,
        received_at,
      };

      // Send to webhook
      const response = await fetch(config.webhookUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Webhook-Secret": config.webhookSecret,
        },
        body: JSON.stringify(payload),
      });

      const responseText = await response.text();

      if (!response.ok) {
        console.error(`Webhook failed: ${response.status} - ${responseText}`);
      } else {
        console.log(`Email forwarded successfully: ${to}`);
      }
    } catch (error) {
      console.error(`Error processing email: ${error.message}`);
      console.error(error.stack);
    }
  },
};

// =====================================================
//       EXTRACT MIME PART (RAW - no decoding)
// =====================================================
function extractMimePart(raw, contentType) {
  // Find boundaries first
  const boundaryMatch = raw.match(/boundary\s*=\s*"?([^"\r\n;]+)"?/i);
  const boundary = boundaryMatch ? boundaryMatch[1] : null;

  let parts = [];
  
  if (boundary) {
    // Split by boundary
    const boundaryRegex = new RegExp(`--${boundary.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'g');
    parts = raw.split(boundaryRegex);
  } else {
    parts = [raw];
  }

  // Find matching content type part
  for (const part of parts) {
    if (part.includes('--') && part === parts[parts.length - 1]) continue;
    
    const ctMatch = part.match(/Content-Type:\s*([^\r\n;]+)/i);
    if (!ctMatch) continue;
    
    const partContentType = ctMatch[1].trim().toLowerCase();
    if (!partContentType.includes(contentType.toLowerCase())) continue;

    // Get content after headers (after \r\n\r\n or \n\n)
    const headerEndMatch = part.match(/\r?\n\r?\n/);
    if (!headerEndMatch) continue;

    let content = part.substring(part.indexOf(headerEndMatch[0]) + headerEndMatch[0].length);
    
    // Remove trailing boundary marker if present
    content = content.replace(/\r?\n--[^\r\n]*$/, '').trim();

    // Return RAW content - no decoding here
    // PHP backend will handle decoding
    return content;
  }

  return "";
}

// =====================================================
//       DECODE MIME-ENCODED HEADERS (RFC 2047)
//       Note: PHP backend will handle decoding
// =====================================================
function decodeMimeHeader(input) {
  // Return as-is, let PHP backend decode
  return input || "";
}

// =====================================================
//          BASE64 UTF-8 DECODER
//          Note: PHP backend will handle decoding
// =====================================================
function decodeBase64Utf8(input) {
  // Return as-is, let PHP backend decode
  return input || "";
}

// =====================================================
//       QUOTED-PRINTABLE UTF-8 DECODER (IMPROVED)
//       Note: PHP backend will handle decoding
// =====================================================
function decodeQuotedPrintableUtf8(input) {
  // Return as-is, let PHP backend decode
  return input || "";
}

// =====================================================
//       VIETNAM DATETIME FORMATTER (GMT+7)
// =====================================================
function formatVietnamDateTime(date = new Date()) {
  const parts = new Intl.DateTimeFormat("en-GB", {
    timeZone: "Asia/Ho_Chi_Minh",
    hour12: false,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }).formatToParts(date);

  const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
  return `${map.year}-${map.month}-${map.day} ${map.hour}:${map.minute}:${map.second}`;
}
