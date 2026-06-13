const sendSuccess = (res, data, message = "OK", code = 200) => {
	return res.status(code).json({
		status: "success",
		code,
		data,
		message,
		service: "api-gateway",
		timestamp: new Date().toISOString(),
	});
};

const sendError = (res, message, code = 500, data = null) => {
	return res.status(code).json({
		status: "error",
		code,
		data,
		message,
		service: "api-gateway",
		timestamp: new Date().toISOString(),
	});
};

module.exports = { sendSuccess, sendError };
