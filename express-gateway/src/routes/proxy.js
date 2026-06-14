const { createProxyMiddleware } = require("http-proxy-middleware");
const { verifyJWT, verifyIoTToken } = require("../middleware/jwt");
const { sendError } = require("../utils/response");

const makeProxy = (target, pathRewrite = {}) => {
	return createProxyMiddleware({
		target,
		changeOrigin: true,
		pathRewrite,
		onError: (err, req, res) => {
			console.error(`[Proxy Error] → ${target}: ${err.message}`);
			sendError(res, `Upstream service unavailable: ${target}`, 502);
		},
	});
};

// authLimiter diterima dari index.js agar bisa diganti saat testing
const registerProxyRoutes = (app, authLimiter) => {
	const citizenUrl = process.env.CITIZEN_SERVICE_URL || "http://localhost:8000";
	const trafficUrl = process.env.TRAFFIC_SERVICE_URL || "http://localhost:8001";
	const envUrl = process.env.ENV_SERVICE_URL || "http://localhost:8002";
	const mlUrl = process.env.PYTHON_ML_URL || "http://localhost:5000";

	// middleware stack: authLimiter → verifyJWT → proxy
	const protect = authLimiter ? [authLimiter, verifyJWT] : [verifyJWT];

	// Citizen Service 
	app.use("/api/citizens", ...protect, makeProxy(citizenUrl));
	app.use("/api/reports", ...protect, makeProxy(citizenUrl));
	app.use("/api/notifications", ...protect, makeProxy(citizenUrl));

	// Traffic Service
	app.use("/api/traffic", ...protect, makeProxy(trafficUrl));

	// Environment Service
	app.use("/api/environment", ...protect, makeProxy(envUrl));

	// Python ML Service
	app.use("/predict", ...protect, makeProxy(mlUrl));
	app.use("/detect", ...protect, makeProxy(mlUrl));
	app.use("/model", ...protect, makeProxy(mlUrl));

	// IoT Routes (client_credentials token)
	app.use(
		"/iot/traffic",
		verifyIoTToken,
		makeProxy(trafficUrl, { "^/iot/traffic": "/api/traffic/readings" }),
	);
	app.use(
		"/iot/air",
		verifyIoTToken,
		makeProxy(envUrl, { "^/iot/air": "/api/environment/readings" }),
	);
};

module.exports = { registerProxyRoutes };
