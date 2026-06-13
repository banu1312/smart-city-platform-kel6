const { createProxyMiddleware } = require("http-proxy-middleware");
const { verifyJWT, verifyIoTToken } = require("../middleware/jwt");
const { sendError } = require("../utils/response");

/**
 * Buat proxy middleware dengan error handling bawaan.
 * Jika upstream service down, return 502 bukan crash.
 */
const makeProxy = (target, pathRewrite = {}) => {
	return createProxyMiddleware({
		target,
		changeOrigin: true,
		pathRewrite,
		on: {
			error: (err, req, res) => {
				console.error(`[Proxy Error] ${target}: ${err.message}`);
				sendError(res, `Upstream service unavailable: ${target}`, 502);
			},
		},
	});
};

const registerProxyRoutes = (app) => {
	const citizenUrl = process.env.CITIZEN_SERVICE_URL || "http://localhost:8000";
	const trafficUrl = process.env.TRAFFIC_SERVICE_URL || "http://localhost:8001";
	const envUrl = process.env.ENV_SERVICE_URL || "http://localhost:8002";
	const mlUrl = process.env.PYTHON_ML_URL || "http://localhost:5000";

	// Citizen Service (port 8000) 
	app.use("/api/citizens", verifyJWT, makeProxy(citizenUrl));
	app.use("/api/reports", verifyJWT, makeProxy(citizenUrl));
	app.use("/api/notifications", verifyJWT, makeProxy(citizenUrl));

	// Traffic Service (port 8001)
	app.use("/api/traffic", verifyJWT, makeProxy(trafficUrl));

	// Environment Service (port 8002)
	app.use("/api/environment", verifyJWT, makeProxy(envUrl));

	// Python ML Service (port 5000)
	app.use("/predict", verifyJWT, makeProxy(mlUrl));
	app.use("/detect", verifyJWT, makeProxy(mlUrl));
	app.use("/model", verifyJWT, makeProxy(mlUrl));

	//  IoT Routes - dari Node-RED, pakai client_credentials token
	app.use(
		"/iot",
		verifyIoTToken,
		makeProxy(trafficUrl, { "^/iot/traffic": "/api/traffic/readings" }),
	);
	app.use(
		"/iot",
		verifyIoTToken,
		makeProxy(envUrl, { "^/iot/air": "/api/environment/readings" }),
	);
};

module.exports = { registerProxyRoutes };
