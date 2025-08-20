#!/bin/bash
# Ensure Apache listens on Render's port
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
fi

# Start Apache
exec "$@"
