FROM node:22-alpine AS base

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .

# ── DEV ──
FROM base AS dev
EXPOSE 80
CMD ["npx", "vite", "--host", "0.0.0.0", "--port", "80"]

# ── PROD ──
FROM base AS builder
ARG VITE_API_URL=/api/v1
ENV VITE_API_URL=${VITE_API_URL}
RUN npx vite build

FROM nginx:1.25-alpine AS prod
RUN apk add --no-cache curl
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -f http://localhost/ || exit 1
CMD ["nginx", "-g", "daemon off;"]
