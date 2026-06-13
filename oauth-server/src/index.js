require("dotenv").config();
const express = require("express");
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
const oauthRoutes = require("./routes/oauth");
app.use("/", oauthRoutes);

// Health check
app.get("/health", (req, res) => {
	res.json({
		status: "success",
		code: 200,
		message: "OAuth Server is running",
		service: "oauth-server",
		timestamp: new Date().toISOString(),
	});
});

const PORT = process.env.OAUTH_PORT || 3002;
app.listen(PORT, () => {
	console.log(`[OAuth Server] Running on port ${PORT}`);
});

module.exports = app;
