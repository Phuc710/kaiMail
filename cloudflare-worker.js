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

      // ===== Parse content with proper encoding =====
      let textBody = "";
      let htmlBody = "";

      // Try to extract text/plain part
      const plainPart = raw.match(/Content-Type:\s*text\/plain[\s\S]*?\r?\n\r?\n([\s\S]*?)(\r?\n--|$)/i);
      if (plainPart) {
        const partFull = plainPart[0];
        let content = plainPart[1].trim();

        // Check encoding
        const encMatch = partFull.match(/Content-Transfer-Encoding:\s*([^\r\n]+)/i);
        const encoding = encMatch ? encMatch[1].toLowerCase().trim() : "";

        if (encoding === "quoted-printable") {
          textBody = decodeQuotedPrintableUtf8(content);
        } else if (encoding === "base64") {
          textBody = decodeBase64Utf8(content);
        } else {
          textBody = content;
        }
      }

      // Try to extract text/html part
      const htmlPart = raw.match(/Content-Type:\s*text\/html[\s\S]*?\r?\n\r?\n([\s\S]*?)(\r?\n--|$)/i);
      if (htmlPart) {
        const partFull = htmlPart[0];
        let content = htmlPart[1].trim();

        const encMatch = partFull.match(/Content-Transfer-Encoding:\s*([^\r\n]+)/i);
        const encoding = encMatch ? encMatch[1].toLowerCase().trim() : "";

        if (encoding === "quoted-printable") {
          htmlBody = decodeQuotedPrintableUtf8(content);
        } else if (encoding === "base64") {
          htmlBody = decodeBase64Utf8(content);
        } else {
          htmlBody = content;
        }
      }

      // Fallback: if no parts found, use raw
      if (!textBody && !htmlBody) {
        const bodyStart = raw.indexOf("\n\n");
        if (bodyStart !== -1) {
          textBody = raw.substring(bodyStart + 2).trim();
        }
      }

      // Prepare webhook payload
      const payload = {
        to: to,
        from: from,
        from_name: fromName,
        subject: subject,
        message_id: messageId,
        text: textBody,
        html: htmlBody,
        received_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      };

      console.log(`📩 Processing email: ${to} from ${from}`);

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
        bytes.push("=".charCodeAt(0));
      }
    } else {
      bytes.push(ch.charCodeAt(0));
    }
  }

  return new TextDecoder("utf-8").decode(new Uint8Array(bytes));
}

// =====================================================
//          BASE64 UTF-8 DECODER
// =====================================================
function decodeBase64Utf8(input) {
  if (!input) return "";

  const cleaned = input.replace(/\s+/g, "");
  const binary = atob(cleaned);
  const bytes = new Uint8Array(binary.length);

  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }

  return new TextDecoder("utf-8").decode(bytes);
}