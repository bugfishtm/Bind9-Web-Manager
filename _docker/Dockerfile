############################################################################################################
# Build Instructions
############################################################################################################
FROM bugfishtm/sf-base:1.3
ENV DEBIAN_FRONTEND=noninteractive

##
## Install Updates
##
RUN apt update -y && apt upgrade -y && apt-get clean

##
## Apache Copy Website Content
##
RUN rm -rf /var/www/html/*
COPY _source/ /var/www/html/
RUN rm -rf /var/www/html/_pma

##
## Cleanup Image
##
RUN apt-get install -y bind9 bind9-utils dnsutils
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

##
## Make executable
##
RUN chmod u+x /var/www/html/_cronjob/sync.php

##
## Copy Entrypoint
##
COPY ./entrypoint.sh /entrypoint.sh
RUN chmod u+x /entrypoint.sh

##
## Setup Cronjob
##
RUN echo "0 */2 * * * root /usr/bin/flock -n /var/run/cronjob.lock /usr/local/bin/php /var/www/html/_cronjob/sync.php 2>&1" > /etc/cron.d/suitefish \
 && chmod 0644 /etc/cron.d/suitefish
 
##
## PHP Max Upload and Post Size Settings
##
RUN echo "upload_max_filesize = 512M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 1200" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 1024M" >> /usr/local/etc/php/conf.d/uploads.ini
##
## Bind9 Daemon
##
RUN cat > /etc/supervisor/conf.d/bind9.conf << 'EOF'
[program:bind9]
command=/usr/sbin/named -f -c /etc/bind/named.conf
autostart=true
autorestart=true
user=root
stdout_logfile=/var/log/bind9.log
stderr_logfile=/var/log/bind9_error.log
EOF

##
## Add Entrypoint
##
ENTRYPOINT ["/entrypoint.sh"]

##
## Startup Instructions
##
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf", "--silent"]