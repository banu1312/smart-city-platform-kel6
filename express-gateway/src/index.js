require("dotenv").config();
const express = require("express");
const logger = require("./middleware/logger");
const { registerProxyRoutes } = require("./routes/proxy");
const { sendError } = require("./utils/response");

const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// logger
app.use(logger);

// Public endpoint: Health check 
// (masih skeleton)
app.get("/health", (req, res) => {
	res.json({
		status: "success",
		code: 200,
		message: "API Gateway is running",
		data: { gateway: "ok" },
		service: "api-gateway",
		timestamp: new Date().toISOString(),
	});
});

// Public endpoint: OAuth token (forward ke oauth-server) 
const { createProxyMiddleware } = require("http-proxy-middleware");
const oauthUrl = process.env.OAUTH_SERVER_URL || "http://localhost:3002";
app.use(
	"/oauth",
	createProxyMiddleware({
		target: oauthUrl,
		changeOrigin: true,
		on: {
			error: (err, req, res) => {
				sendError(res, "OAuth Server unavailable", 503);
			},
		},
	}),
);

// Protected proxy routes
registerProxyRoutes(app);

// 404 handler
app.use((req, res) => {
	sendError(res, `Route ${req.method} ${req.path} not found`, 404);
});

// Global error handler
app.use((err, req, res, _next) => {
	console.error("[Gateway Error]", err.message);
	sendError(res, "Internal Gateway Error", 500);
});

// Start server
const PORT = process.env.GATEWAY_PORT || 3000;
app.listen(PORT, () => {
	console.log(`[API Gateway] Running on port ${PORT}`);
	console.log(`  → Citizen  : ${process.env.CITIZEN_SERVICE_URL}`);
	console.log(`  → Traffic  : ${process.env.TRAFFIC_SERVICE_URL}`);
	console.log(`  → Env      : ${process.env.ENV_SERVICE_URL}`);
	console.log(`  → Python ML: ${process.env.PYTHON_ML_URL}`);
	console.log(`  → OAuth    : ${process.env.OAUTH_SERVER_URL}`);
});

module.exports = app;
