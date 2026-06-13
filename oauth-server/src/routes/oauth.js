const express = require("express");
const router = express.Router();
const TokenStore = require("../models/tokenStore");

// ─────────────────────────────────────────────
// POST /oauth/token
// Mendukung: password, client_credentials, refresh_token
// ─────────────────────────────────────────────
router.post("/oauth/token", (req, res) => {
	const {
		grant_type,
		username,
		password,
		client_id,
		client_secret,
		refresh_token,
		scope,
	} = req.body;

	// Validasi client dulu di semua grant type
	const client = TokenStore.getClient(client_id, client_secret);
	if (!client) {
		return res.status(401).json({
			status: "error",
			code: 401,
			message: "Invalid client credentials",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	// Cek grant_type didukung oleh client ini
	if (!client.grants.includes(grant_type)) {
		return res.status(400).json({
			status: "error",
			code: 400,
			message: `Grant type '${grant_type}' not allowed for this client`,
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	// ── GRANT: password ─────────────────────────
	if (grant_type === "password") {
		if (!username || !password) {
			return res.status(400).json({
				status: "error",
				code: 400,
				message: "username and password are required",
				service: "oauth-server",
				timestamp: new Date().toISOString(),
			});
		}

		const user = TokenStore.getUser(username, password);
		if (!user) {
			return res.status(401).json({
				status: "error",
				code: 401,
				message: "Invalid username or password",
				service: "oauth-server",
				timestamp: new Date().toISOString(),
			});
		}

		const accessToken = TokenStore.createAccessToken(
			user,
			client,
			scope || "read write",
		);
		const refreshToken = TokenStore.createRefreshToken(user, client);

		return res.json({
			status: "success",
			code: 200,
			data: {
				access_token: accessToken.token,
				token_type: "Bearer",
				expires_in: 3600,
				refresh_token: refreshToken.token,
				scope: scope || "read write",
			},
			message: "Token issued successfully",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	// ── GRANT: client_credentials ─────────────────
	if (grant_type === "client_credentials") {
		const accessToken = TokenStore.createAccessToken(
			null,
			client,
			scope || "service",
		);

		return res.json({
			status: "success",
			code: 200,
			data: {
				access_token: accessToken.token,
				token_type: "Bearer",
				expires_in: 3600,
				scope: scope || "service",
			},
			message: "Token issued successfully",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	// ── GRANT: refresh_token ─────────────────────
	if (grant_type === "refresh_token") {
		if (!refresh_token) {
			return res.status(400).json({
				status: "error",
				code: 400,
				message: "refresh_token is required",
				service: "oauth-server",
				timestamp: new Date().toISOString(),
			});
		}

		const storedRefresh = TokenStore.getRefreshToken(refresh_token);
		if (!storedRefresh) {
			return res.status(401).json({
				status: "error",
				code: 401,
				message: "Invalid or expired refresh token",
				service: "oauth-server",
				timestamp: new Date().toISOString(),
			});
		}

		// Rotate token — hapus refresh lama, buat baru
		TokenStore.revokeToken(refresh_token);
		const newAccess = TokenStore.createAccessToken(
			storedRefresh.user,
			storedRefresh.client,
			scope,
		);
		const newRefresh = TokenStore.createRefreshToken(
			storedRefresh.user,
			storedRefresh.client,
		);

		return res.json({
			status: "success",
			code: 200,
			data: {
				access_token: newAccess.token,
				token_type: "Bearer",
				expires_in: 3600,
				refresh_token: newRefresh.token,
				scope: scope || "read write",
			},
			message: "Token refreshed successfully",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	return res.status(400).json({
		status: "error",
		code: 400,
		message: `Unsupported grant_type: ${grant_type}`,
		service: "oauth-server",
		timestamp: new Date().toISOString(),
	});
});

// ─────────────────────────────────────────────
// POST /oauth/introspect
// Dipakai Gateway untuk validasi token secara internal
// ─────────────────────────────────────────────
router.post("/oauth/introspect", (req, res) => {
	// Validasi client secret di header Basic Auth
	const authHeader = req.headers["authorization"] || "";
	const base64 = authHeader.replace("Basic ", "");
	let clientId, clientSecret;

	try {
		const decoded = Buffer.from(base64, "base64").toString("utf-8");
		[clientId, clientSecret] = decoded.split(":");
	} catch {
		return res.status(401).json({
			status: "error",
			code: 401,
			message: "Missing or invalid Authorization header",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	const client = TokenStore.getClient(clientId, clientSecret);
	if (!client) {
		return res.status(401).json({ active: false });
	}

	const { token } = req.body;
	if (!token) {
		return res.status(400).json({
			status: "error",
			code: 400,
			message: "token is required",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	const tokenData = TokenStore.getAccessToken(token);
	if (!tokenData) {
		return res.json({ active: false });
	}

	return res.json({
		active: true,
		sub: tokenData.user ? String(tokenData.user.id) : tokenData.client.clientId,
		username: tokenData.user ? tokenData.user.username : null,
		role: tokenData.user ? tokenData.user.role : "service",
		client_id: tokenData.client.clientId,
		scope: tokenData.scope,
		exp: Math.floor(tokenData.expiresAt.getTime() / 1000),
	});
});

// ─────────────────────────────────────────────
// POST /oauth/revoke
// ─────────────────────────────────────────────
router.post("/oauth/revoke", (req, res) => {
	const { token } = req.body;
	if (!token) {
		return res.status(400).json({
			status: "error",
			code: 400,
			message: "token is required",
			service: "oauth-server",
			timestamp: new Date().toISOString(),
		});
	}

	TokenStore.revokeToken(token);

	return res.json({
		status: "success",
		code: 200,
		message: "Token revoked successfully",
		service: "oauth-server",
		timestamp: new Date().toISOString(),
	});
});

module.exports = router;
