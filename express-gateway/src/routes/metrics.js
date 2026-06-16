// Counter sederhana in-memory
// Akan diganti dengan prom-client jika dibutuhkan
const counters = {
	requests_total: 0,
	requests_success: 0,
	requests_error_4xx: 0,
	requests_error_5xx: 0,
	requests_rate_limited: 0,
};

// Middleware untuk tracking metrics — pasang di index.js
const metricsMiddleware = (req, res, next) => {
	counters.requests_total++;
	const originalJson = res.json.bind(res);

	res.json = (body) => {
		const code = body?.code || res.statusCode;
		if (code >= 200 && code < 400) counters.requests_success++;
		else if (code === 429) counters.requests_rate_limited++;
		else if (code >= 400 && code < 500) counters.requests_error_4xx++;
		else if (code >= 500) counters.requests_error_5xx++;
		return originalJson(body);
	};

	next();
};

const registerMetricsRoute = (app) => {
	// GET /metrics — format Prometheus text (internal only)
	app.get("/metrics", (req, res) => {
		const metrics = [
			`# HELP gateway_requests_total Total requests received`,
			`# TYPE gateway_requests_total counter`,
			`gateway_requests_total ${counters.requests_total}`,
			``,
			`# HELP gateway_requests_success Total successful requests`,
			`# TYPE gateway_requests_success counter`,
			`gateway_requests_success ${counters.requests_success}`,
			``,
			`# HELP gateway_requests_rate_limited Total rate limited requests`,
			`# TYPE gateway_requests_rate_limited counter`,
			`gateway_requests_rate_limited ${counters.requests_rate_limited}`,
			``,
			`# HELP gateway_requests_error_4xx Total 4xx errors`,
			`# TYPE gateway_requests_error_4xx counter`,
			`gateway_requests_error_4xx ${counters.requests_error_4xx}`,
			``,
			`# HELP gateway_requests_error_5xx Total 5xx errors`,
			`# TYPE gateway_requests_error_5xx counter`,
			`gateway_requests_error_5xx ${counters.requests_error_5xx}`,
		].join("\n");

		res.set("Content-Type", "text/plain; version=0.0.4");
		res.send(metrics);
	});
};

module.exports = { metricsMiddleware, registerMetricsRoute };
