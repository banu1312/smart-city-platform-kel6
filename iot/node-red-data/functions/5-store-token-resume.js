const body = msg.payload;

if (!body || body.status !== "success" || !body.data || !body.data.access_token) {
    node.error("Gagal mendapatkan token dari OAuth server: " + JSON.stringify(body));
    return null;
}

const accessToken = body.data.access_token;
const expiresInSec = body.data.expires_in || 3600;
const expiresAt = Date.now() + expiresInSec * 1000;

flow.set("iot_access_token", accessToken);
flow.set("iot_token_expires_at", expiresAt);

node.log(`Token IoT baru diperoleh, berlaku ${expiresInSec} detik.`);

msg.payload = msg._originalPayload;
msg.headers = Object.assign({}, msg._originalHeaders, {
    Authorization: "Bearer " + accessToken,
});

delete msg._originalPayload;
delete msg._originalHeaders;

return msg;