FROM node:22-bullseye

WORKDIR /var/www

# node_modules や public ディレクトリの権限を node に設定
RUN mkdir -p /var/www/public && chown -R node:node /var/www

USER node

# デフォルトコマンドは docker-compose.yml で指定
