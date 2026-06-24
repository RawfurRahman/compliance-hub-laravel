const http = require('http');
const crypto = require('crypto');

const secret = "my_super_secret_n8n_key_123";

const server = http.createServer((req, res) => {
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString(); // convert Buffer to string
    });
    req.on('end', () => {
        const signatureHeader = req.headers['x-hub-signature'];
        const timestampHeader = req.headers['x-timestamp'];

        // Simulated extraction from multipart or JSON
        let evidenceFileId = '';
        if (body.includes('evidence_file_id=')) {
            // simple parse for testing
            evidenceFileId = body.split('evidence_file_id=')[1].split('&')[0];
        }

        const payloadToSign = `${timestampHeader}.${evidenceFileId}`;
        const expectedSignature = crypto.createHmac('sha256', secret)
                                        .update(payloadToSign)
                                        .digest('hex');

        if (!signatureHeader || expectedSignature !== signatureHeader) {
            res.writeHead(403, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'Invalid HMAC signature' }));
            return;
        }

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, message: 'Signature valid' }));
    });
});

server.listen(5679, () => {
    console.log('Mock n8n server running on port 5679');
});
