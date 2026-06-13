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

module.exports = { sendSuccess };
