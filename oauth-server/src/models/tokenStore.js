const { v4: uuidv4 } = require("uuid");
const jwt = require("jsonwebtoken");

// In-memory store (sementara) - bisa diganti dengan MySQL di sprint berikutnya
const accessTokens = new Map();
const refreshTokens = new Map();
const clients = new Map([
	[
		"smartcity-app",
		{
			clientId: "smartcity-app",
			clientSecret: "smartcity-secret",
			grants: ["password", "client_credentials", "refresh_token"],
		},
	],
	[
		"iot-device",
		{
			clientId: "iot-device",
			clientSecret: "iot-secret",
			grants: ["client_credentials"],
		},
	],
]);

// User dummy - nanti diambil dari tabel citizens di MySQL
const users = new Map([
	["admin", { id: 1, username: "admin", password: "admin123", role: "admin" }],
	[
		"warga1",
		{ id: 2, username: "warga1", password: "warga123", role: "citizen" },
	],
]);

const TokenStore = {
	// Buat access token JWT
	createAccessToken(user, client, scope) {
		const payload = {
			sub: user ? user.id : client.clientId,
			username: user ? user.username : null,
			role: user ? user.role : "service",
			client_id: client.clientId,
			scope: scope || "read",
			jti: uuidv4(),
		};

		const token = jwt.sign(payload, process.env.JWT_SECRET, {
			expiresIn: process.env.JWT_EXPIRES_IN || "1h",
		});

		const expiresAt = new Date(Date.now() + 3600 * 1000);
		accessTokens.set(token, {
			token,
			user: user || null,
			client,
			scope,
			expiresAt,
		});

		return { token, expiresAt };
	},

	// Buat refresh token (opaque, bukan JWT)
	createRefreshToken(user, client) {
		const token = uuidv4() + "-" + uuidv4();
		const expiresAt = new Date(Date.now() + 7 * 24 * 3600 * 1000); // 7 hari

		refreshTokens.set(token, {
			token,
			user,
			client,
			expiresAt,
		});

		return { token, expiresAt };
	},

	getAccessToken(token) {
		const data = accessTokens.get(token);
		if (!data) return null;
		if (new Date() > data.expiresAt) {
			accessTokens.delete(token);
			return null;
		}
		return data;
	},

	getRefreshToken(token) {
		const data = refreshTokens.get(token);
		if (!data) return null;
		if (new Date() > data.expiresAt) {
			refreshTokens.delete(token);
			return null;
		}
		return data;
	},

	revokeToken(token) {
		const wasAccess = accessTokens.delete(token);
		const wasRefresh = refreshTokens.delete(token);
		return wasAccess || wasRefresh;
	},

	getClient(clientId, clientSecret) {
		const client = clients.get(clientId);
		if (!client) return null;
		if (clientSecret && client.clientSecret !== clientSecret) return null;
		return client;
	},

	getUser(username, password) {
		const user = users.get(username);
		if (!user) return null;
		if (user.password !== password) return null;
		return user;
	},
};

module.exports = TokenStore;
