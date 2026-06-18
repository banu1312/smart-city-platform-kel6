require("dotenv").config();
const express = require("express");
const axios = require("axios");
const logger = require("./middleware/logger");
const {
	globalLimiter,
	authLimiter,
	oauthLimiter,
} = require("./middleware/rateLimit");
const { registerProxyRoutes } = require("./routes/proxy");
const { registerHealthRoute } = require("./routes/health");
const { registerIoTRoutes } = require("./routes/iot");
const { metricsMiddleware, registerMetricsRoute } = require("./routes/metrics");
const { sendError } = require("./utils/response");

const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(logger);
app.use(metricsMiddleware);
app.use(globalLimiter);

registerHealthRoute(app);
registerMetricsRoute(app);

const oauthUrl = process.env.OAUTH_SERVER_URL || "http://localhost:3002";

app.use("/oauth", oauthLimiter, async (req, res) => {
	try {
		const targetUrl = `${oauthUrl}/oauth${req.path === "/" ? "" : req.path}`;
		console.log(`[OAuth Forward] ${req.method} ${targetUrl}`);

		const response = await axios({
			method: req.method,
			url: targetUrl,
			headers: {
				"Content-Type": req.headers["content-type"] || "application/json",
				...(req.headers["authorization"] && {
					Authorization: req.headers["authorization"],
				}),
			},
			data: req.body,
			timeout: 10000,
		});

		return res.status(response.status).json(response.data);
	} catch (err) {
		if (err.code === "ECONNREFUSED" || err.code === "ENOTFOUND") {
			return sendError(res, "OAuth Server unavailable", 503);
		}
		if (err.response) {
			return res.status(err.response.status).json(err.response.data);
		}
		console.error("[OAuth Forward Error]", err.message);
		return sendError(res, "OAuth Server error", 500);
	}
});

registerIoTRoutes(app);
registerProxyRoutes(app, authLimiter);

app.use((req, res) => {
	sendError(res, `Route ${req.method} ${req.path} not found`, 404);
});

app.use((err, req, res, _next) => {
	console.error("[Gateway Error]", err.message);
	sendError(res, "Internal Gateway Error", 500);
});

const PORT = process.env.GATEWAY_PORT || 3000;
app.listen(PORT, () => {
	console.log(`[API Gateway] Running on port ${PORT}`);
	console.log(`  → Backend  : ${process.env.BACKEND_URL}`);
	console.log(`  → Python ML: ${process.env.PYTHON_ML_URL}`);
	console.log(`  → OAuth    : ${oauthUrl}`);
});

module.exports = app;