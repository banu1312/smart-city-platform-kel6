const rateLimit = require("express-rate-limit");

// LIMITER 1: Global per IP
// Berlaku untuk SEMUA request yang masuk ke Gateway
// Limit: 100 request per 15 menit per IP
const globalLimiter = rateLimit({
	windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000,
	max: parseInt(process.env.RATE_LIMIT_MAX) || 100,
	standardHeaders: true, // kirim header RateLimit-* standar RFC
	legacyHeaders: false, // nonaktifkan header X-RateLimit-* lama
	keyGenerator: (req) => {
		// Ambil IP asli jika di belakang proxy/nginx
		return (
			req.headers["x-forwarded-for"]?.split(",")[0]?.trim() ||
			req.socket.remoteAddress ||
			req.ip
		);
	},
	handler: (req, res) => {
		return res.status(429).json({
			status: "error",
			code: 429,
			data: null,
			message:
				"Too many requests from this IP. Please try again in 15 minutes.",
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	},
	skip: (req) => {
		// Jangan rate-limit health check dan metrics
		return req.path === "/health" || req.path === "/metrics";
	},
});

// LIMITER 2: Per token JWT (authenticated user)
// Lebih longgar karena user sudah terautentikasi
// Limit: 500 request per jam per token
const authLimiter = rateLimit({
	windowMs: parseInt(process.env.AUTH_RATE_LIMIT_WINDOW_MS) || 60 * 60 * 1000,
	max: parseInt(process.env.AUTH_RATE_LIMIT_MAX) || 500,
	standardHeaders: true,
	legacyHeaders: false,
	keyGenerator: (req) => {
		// Key berdasarkan token JWT, bukan IP
		// Sehingga user yang sama dari IP berbeda tetap dihitung sebagai satu
		const authHeader = req.headers["authorization"] || "";
		const token = authHeader.replace("Bearer ", "");
		return token || req.ip;
	},
	handler: (req, res) => {
		return res.status(429).json({
			status: "error",
			code: 429,
			data: null,
			message: "Too many requests for this token. Limit: 500 requests/hour.",
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	},
});

// LIMITER 3: Khusus OAuth endpoint
// Lebih ketat untuk mencegah brute force login
// Limit: 20 request per 15 menit per IP
const oauthLimiter = rateLimit({
	windowMs: 15 * 60 * 1000,
	max: 20,
	standardHeaders: true,
	legacyHeaders: false,
	keyGenerator: (req) => {
		return (
			req.headers["x-forwarded-for"]?.split(",")[0]?.trim() ||
			req.socket.remoteAddress ||
			req.ip
		);
	},
	handler: (req, res) => {
		return res.status(429).json({
			status: "error",
			code: 429,
			data: null,
			message:
				"Too many authentication attempts. Please try again in 15 minutes.",
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	},
});

module.exports = { globalLimiter, authLimiter, oauthLimiter };
