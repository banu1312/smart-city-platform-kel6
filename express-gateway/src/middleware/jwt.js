const jwt = require("jsonwebtoken");

const verifyJWT = (req, res, next) => {
	const authHeader = req.headers["authorization"];

	if (!authHeader || !authHeader.startsWith("Bearer ")) {
		return res.status(401).json({
			status: "error",
			code: 401,
			message: "Authorization header missing or malformed. Use: Bearer <token>",
			data: null,
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	}

	const token = authHeader.split(" ")[1];

	try {
		const decoded = jwt.verify(token, process.env.JWT_SECRET);
		// Tempel payload ke request agar bisa dipakai handler berikutnya
		req.user = decoded;
		req.token = token;
		next();
	} catch (err) {
		if (err.name === "TokenExpiredError") {
			return res.status(401).json({
				status: "error",
				code: 401,
				message: "Token has expired. Please refresh your token.",
				data: null,
				service: "api-gateway",
				timestamp: new Date().toISOString(),
			});
		}
		if (err.name === "JsonWebTokenError") {
			return res.status(401).json({
				status: "error",
				code: 401,
				message: "Invalid token signature.",
				data: null,
				service: "api-gateway",
				timestamp: new Date().toISOString(),
			});
		}
		return res.status(401).json({
			status: "error",
			code: 401,
			message: "Token verification failed.",
			data: null,
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	}
};

// Middleware khusus untuk IoT device — validasi scope 'service'
const verifyIoTToken = (req, res, next) => {
	verifyJWT(req, res, () => {
		if (
			req.user &&
			(req.user.role === "service" || req.user.scope?.includes("service"))
		) {
			return next();
		}
		return res.status(403).json({
			status: "error",
			code: 403,
			message: "Forbidden: IoT scope required.",
			data: null,
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	});
};

module.exports = { verifyJWT, verifyIoTToken };
