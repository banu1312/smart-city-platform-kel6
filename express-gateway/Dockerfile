FROM node:18-alpine

# Set working directory
WORKDIR /app

# Copy package files dulu (layer caching — hanya re-install jika package.json berubah)
COPY package*.json ./

# Install production dependencies saja
RUN npm ci --only=production

# Copy source code
COPY src/ ./src/
COPY .env.example ./

# Expose port
EXPOSE 3000

# Health check — Docker akan restart container jika /health tidak merespons
HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD wget -qO- http://localhost:3000/health || exit 1

# Jalankan server
CMD ["node", "src/index.js"]