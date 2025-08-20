#!/bin/bash
# Replace Apache Listen port with Render's dynamic $PORT
if [ -n "$PORT" ]; then
    sed -i "s/^Listen .*/Listen $PORT/" /etc/apache2/ports.conf
fi

# Start Apache in the foreground
apache2-foreground