const registerIoTRoutes = (app) => {
	// GET /iot/status - info endpoint IoT (publik, tanpa auth)
	app.get("/iot/status", (req, res) => {
		res.json({
			status: "success",
			code: 200,
			message: "IoT Gateway is ready",
			data: {
				mqtt_broker: process.env.MQTT_BROKER_HOST || "mosquitto",
				accepted_topics: ["city/+/waste"],
				endpoints: [
					"POST /iot/telemetry (requires client_credentials token, forwarded as Bearer to Laravel /api/iot/telemetry)",
				],
			},
			service: "api-gateway",
			timestamp: new Date().toISOString(),
		});
	});
};

module.exports = { registerIoTRoutes };