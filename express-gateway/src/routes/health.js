const axios = require("axios");

// Cek status satu upstream service
const checkService = async (name, url) => {
	try {
		const start = Date.now();
		const response = await axios.get(`${url}/health`, { timeout: 5000 });
		const latency = Date.now() - start;

		return {
			name,
			status: response.data?.status === "success" ? "ok" : "degraded",
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
		const upstreams = [
			{
				name: "oauth-server",
				url: process.env.OAUTH_SERVER_URL || "http://localhost:3002",
			},
			{
				name: "citizen-service",
				url: process.env.CITIZEN_SERVICE_URL || "http://localhost:8000",
			},
			{
				name: "traffic-service",
				url: process.env.TRAFFIC_SERVICE_URL || "http://localhost:8001",
			},
			{
				name: "env-service",
				url: process.env.ENV_SERVICE_URL || "http://localhost:8002",
			},
			{
				name: "python-ml-service",
				url: process.env.PYTHON_ML_URL || "http://localhost:5000",
			},
		];

		// Cek semua upstream secara paralel
		const results = await Promise.allSettled(
			upstreams.map(({ name, url }) => checkService(name, url)),
		);

		const services = results.map((r) =>
			r.status === "fulfilled" ? r.value : { status: "down", error: r.reason },
		);

		// Gateway dianggap degraded jika ada service yang down
		const allOk = services.every((s) => s.status === "ok");
		const anyDown = services.some((s) => s.status === "down");
		const overall = allOk ? "ok" : anyDown ? "degraded" : "degraded";

		// HTTP 200 jika gateway sendiri jalan, 503 jika semua upstream down
		const allDown = services.every((s) => s.status === "down");
		const httpCode = allDown ? 503 : 200;

		return res.status(httpCode).json({
			status: overall === "ok" ? "success" : "error",
			code: httpCode,
			message: `Gateway ${overall}. ${services.filter((s) => s.status === "ok").length}/${services.length} upstream services healthy.`,
			data: {
				gateway: "ok",
				overall,
				services,
			},
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	});
};

module.exports = { registerHealthRoute };
