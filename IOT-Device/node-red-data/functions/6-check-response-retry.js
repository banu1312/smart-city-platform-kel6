const statusCode = msg.statusCode;
const MAX_RETRY = 5;

msg.retryCount = (msg.retryCount || 0) + 1;

if (statusCode === 200 || statusCode === 201) {
    node.status({ fill: "green", shape: "dot", text: "OK " + statusCode });
    return [msg, null];
}

// Retry pada error upstream (502/503) dan juga auth error (401) karena
// token mungkin expired/belum ready — retry akan trigger token refresh
const isRetryableError = statusCode === 401 || statusCode === 502 || statusCode === 503 || statusCode === undefined;

if (isRetryableError && msg.retryCount <= MAX_RETRY) {
    // Kalau 401, force token refresh di siklus retry berikutnya
    if (statusCode === 401) {
        flow.set("iot_access_token", null);
        flow.set("iot_token_expires_at", 0);
    }

    node.warn(`Response ${statusCode}, retry ke-${msg.retryCount} dalam 60 detik...`);
    node.status({ fill: "yellow", shape: "ring", text: `retry ${msg.retryCount}/${MAX_RETRY}` });
    return [null, msg];
}

node.error(`Gagal kirim telemetry setelah ${msg.retryCount} percobaan, status=${statusCode}: ` +
    JSON.stringify(msg.payload));
node.status({ fill: "red", shape: "dot", text: "FAILED " + statusCode });

return [null, null];
