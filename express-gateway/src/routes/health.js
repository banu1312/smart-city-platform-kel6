const axios = require("axios");

const checkService = async (name, url) => {
	try {
		const start = Date.now();
		const response = await axios.get(url, { timeout: 5000 });
		const latency = Date.now() - start;

		return {
			name,
			status: response.data?.status === "ok" ? "ok" : "degraded",
			latency_ms: latency,
			url,
		};
	} catch (err) {
		return {
			name,
			status: "down",
			latency_ms: null,
			url,
			error: err.code || err.message,
		};
	}
};

const registerHealthRoute = (app) => {
	app.get("/health", async (req, res) => {
		const backendUrl = process.env.BACKEND_URL || "http://localhost:8000";
		const mlUrl       = process.env.PYTHON_ML_URL || "http://localhost:5000";
		const oauthUrl    = process.env.OAUTH_SERVER_URL || "http://localhost:3002";

		const upstreams = [
			{ name: "oauth-server",          url: `${oauthUrl}/health` },
			{ name: "backend-smart-bin",     url: `${backendUrl}/api/smart-bin/health` },
			{ name: "backend-fleet",         url: `${backendUrl}/api/fleet/health` },
			{ name: "backend-citizen-report",url: `${backendUrl}/api/citizen-report/health` },
			{ name: "python-ml-service",     url: `${mlUrl}/health` },
		];

		const results = await Promise.allSettled(
			upstreams.map(({ name, url }) => checkService(name, url)),
		);

		const services = results.map((r) =>
			r.status === "fulfilled" ? r.value : { status: "down", error: r.reason },
		);

		const allOk   = services.every((s) => s.status === "ok");
		const anyDown = services.some((s) => s.status === "down");
		const overall = allOk ? "ok" : anyDown ? "degraded" : "degraded";
		const allDown = services.every((s) => s.status === "down");
		const httpCode = allDown ? 503 : 200;

		return res.status(httpCode).json({
			status: overall === "ok" ? "success" : "error",
			code: httpCode,
			message: `Gateway ${overall}. ${services.filter((s) => s.status === "ok").length}/${services.length} upstream services healthy.`,
			data: { gateway: "ok", overall, services },
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	});
};

module.exports = { registerHealthRoute };