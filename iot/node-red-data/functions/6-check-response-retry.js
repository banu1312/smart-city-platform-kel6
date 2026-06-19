const statusCode = msg.statusCode;
const MAX_RETRY = 5;

msg.retryCount = (msg.retryCount || 0) + 1;

if (statusCode === 200 || statusCode === 201) {
    node.status({ fill: "green", shape: "dot", text: "OK " + statusCode });
    return [msg, null]; // output 1: sukses
}

const isRetryableError = statusCode === 502 || statusCode === 503 || statusCode === undefined;

if (isRetryableError && msg.retryCount <= MAX_RETRY) {
    node.warn(`Gateway tidak tersedia (status=${statusCode}), retry ke-${msg.retryCount} dalam 60 detik...`);
    node.status({ fill: "yellow", shape: "ring", text: `retry ${msg.retryCount}/${MAX_RETRY}` });
    return [null, msg]; // output 2: retry
}

node.error(`Gagal kirim telemetry setelah ${msg.retryCount} percobaan, status=${statusCode}: ` +
    JSON.stringify(msg.payload));
node.status({ fill: "red", shape: "dot", text: "FAILED " + statusCode });

return [null, null];