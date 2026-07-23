#!/bin/sh
set -e

mkdir -p /data/img/menus /data/img/plats

if [ -z "$(ls -A /data/img/menus 2>/dev/null)" ]; then
    cp -r /var/www/html/public/assets/img/menus/. /data/img/menus/ 2>/dev/null || true
fi
if [ -z "$(ls -A /data/img/plats 2>/dev/null)" ]; then
    cp -r /var/www/html/public/assets/img/plats/. /data/img/plats/ 2>/dev/null || true
fi

rm -rf /var/www/html/public/assets/img/menus /var/www/html/public/assets/img/plats
ln -s /data/img/menus /var/www/html/public/assets/img/menus
ln -s /data/img/plats /var/www/html/public/assets/img/plats
chown -R www-data:www-data /data

exec "$@"
