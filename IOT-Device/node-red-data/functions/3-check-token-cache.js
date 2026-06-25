const cachedToken = flow.get("iot_access_token");
const expiresAt = flow.get("iot_token_expires_at") || 0;
const now = Date.now();
const SAFETY_BUFFER_MS = 60 * 1000;

if (cachedToken && now < (expiresAt - SAFETY_BUFFER_MS)) {
    msg.headers = Object.assign({}, msg.headers, {
        Authorization: "Bearer " + cachedToken,
    });
    msg.needNewToken = false;
} else {
    msg.needNewToken = true;
}

return msg;