# Static site for https://cf7-to-webhook.valney.dev, served by nginx.
#
# Easypanel app service settings:
#   - Build: Dockerfile, with this folder (ci/website) as the build path
#   - Port: 8080 (the unprivileged image cannot bind to 80)
#   - Health check: /healthz
#
# Run locally:
#   docker build -t cf7-to-webhook-website ci/website
#   docker run --rm -p 8080:8080 cf7-to-webhook-website

FROM nginxinc/nginx-unprivileged:stable-alpine

COPY nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public/ /usr/share/nginx/html/

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD wget -q -O /dev/null http://127.0.0.1:8080/healthz || exit 1
