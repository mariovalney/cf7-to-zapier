# Static site for https://cf7-to-webhook.valney.dev, served by nginx.
#
# The build context is the repository root, which is why every path below starts with ci/website.
#
# Easypanel app service settings:
#   - Source: this repository, branch master, path /
#   - Build: Dockerfile, file ci/website/Dockerfile
#   - Domain port: 8080 (the unprivileged image cannot bind to 80)
#
# Run locally, from the repository root:
#   docker build -f ci/website/Dockerfile -t cf7-to-webhook-website .
#   docker run --rm -p 8080:8080 cf7-to-webhook-website

FROM nginxinc/nginx-unprivileged:stable-alpine

COPY ci/website/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ci/website/public/ /usr/share/nginx/html/

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD wget -q -O /dev/null http://127.0.0.1:8080/healthz || exit 1
