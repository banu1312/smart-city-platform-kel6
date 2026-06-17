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
	const backendUrl = process.env.BACKEND_URL || "http://localhost:8000";
	const mlUrl       = process.env.PYTHON_ML_URL || "http://localhost:5000";

	const protect = authLimiter ? [authLimiter, verifyJWT] : [verifyJWT];

	// BACKEND LARAVEL (single service, port 8000) 
	app.use("/api/bins",          ...protect, makeProxy(backendUrl));
	app.use("/api/fleet",         ...protect, makeProxy(backendUrl));
	app.use("/api/reports",       ...protect, makeProxy(backendUrl));

	//Health check tiap domain di backend - publik, tanpa JWT
	app.use("/api/smart-bin/health",      makeProxy(backendUrl));
	app.use("/api/fleet/health",          makeProxy(backendUrl));
	app.use("/api/citizen-report/health", makeProxy(backendUrl));

	// PYTHON ML SERVICE (port 5000) 
	app.use("/predict",  ...protect, makeProxy(mlUrl));
	app.use("/detect",   ...protect, makeProxy(mlUrl));
	app.use("/model",    ...protect, makeProxy(mlUrl));

	//  IoT Telemetry
	app.use(
		"/iot/telemetry",
		verifyIoTToken,
		makeProxy(backendUrl, { "^/iot/telemetry": "/api/iot/telemetry" }),
	);
};

module.exports = { registerProxyRoutes };