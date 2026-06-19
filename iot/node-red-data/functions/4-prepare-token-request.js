msg._originalPayload = msg.payload;
msg._originalHeaders = msg.headers;

msg.payload = {
    grant_type: "client_credentials",
    client_id: env.get("OAUTH_CLIENT_ID") || "iot-device",
    client_secret: env.get("OAUTH_CLIENT_SECRET") || "iot-secret",
    scope: "service",
};

msg.headers = {
    "Content-Type": "application/json",
};

return msg;