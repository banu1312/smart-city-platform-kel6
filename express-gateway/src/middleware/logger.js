const morgan = require("morgan");

// Format custom: timestamp | method | path | status | response-time
const customFormat =
	":date[iso] | :method :url | :status | :response-time ms | :remote-addr";

const logger = morgan(customFormat, {
	// Skip logging untuk /health biar ga menuhin log
	skip: (req) => req.path === "/health",
});

module.exports = logger;
