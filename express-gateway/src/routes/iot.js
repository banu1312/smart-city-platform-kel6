const axios = require("axios");
const { verifyIoTToken } = require("../middleware/jwt");
const { sendError } = require("../utils/response");

// Forward manual ke upstream - lebih reliable daripada proxy untuk IoT
const forwardToUpstream = async (req, res, targetUrl) => {
	try {
		console.log(`[IoT Forward] ${req.method} ${targetUrl}`);

		const response = await axios({
			method: req.method,
			url: targetUrl,
			headers: {
				"Content-Type": "application/json",
				// Teruskan info user dari JWT ke upstream
				"X-User-Id": req.user?.sub || "",
				"X-User-Role": req.user?.role || "",
				"X-Client-Id": req.user?.client_id || "",
			},
			data: req.body,
			timeout: 10000,
		});

		return res.status(response.status).json(response.data);
	} catch (err) {
		if (err.code === "ECONNREFUSED" || err.code === "ENOTFOUND") {
			return sendError(res, `Upstream service unavailable: ${targetUrl}`, 502);
		}
		if (err.response) {
			return res.status(err.response.status).json(err.response.data);
		}
		console.error("[IoT Forward Error]", err.message);
		return sendError(res, "IoT forward error", 500);
	}
};

const registerIoTRoutes = (app) => {
	const trafficUrl = process.env.TRAFFIC_SERVICE_URL || "http://localhost:8001";
	const envUrl = process.env.ENV_SERVICE_URL || "http://localhost:8002";

	// POST /iot/traffic → Traffic Service /api/traffic/readings
	app.post("/iot/traffic", verifyIoTToken, (req, res) => {
		forwardToUpstream(req, res, `${trafficUrl}/api/traffic/readings`);
	});

	// POST /iot/air → Environment Service /api/environment/readings
	app.post("/iot/air", verifyIoTToken, (req, res) => {
		forwardToUpstream(req, res, `${envUrl}/api/environment/readings`);
	});

	// GET /iot/status - cek apakah IoT gateway aktif (tanpa auth)
	app.get("/iot/status", (req, res) => {
		res.json({
			status: "success",
			code: 200,
			message: "IoT Gateway is ready",
			data: {
				mqtt_broker: process.env.MQTT_BROKER_HOST || "mosquitto",
				accepted_topics: ["city/+/traffic", "city/+/air"],
				endpoints: [
					"POST /iot/traffic (requires IoT token)",
					"POST /iot/air (requires IoT token)",
				],
			},
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	});
};

module.exports = { registerIoTRoutes };
