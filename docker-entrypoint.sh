
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
exec "$@"
