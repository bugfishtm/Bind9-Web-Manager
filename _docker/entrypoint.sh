#!/bin/sh

##
## Startup Text
##
echo "┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓"
echo "  DNSHTTP Docker Initialization"
echo "┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛"

##
## Your startup commands here
##
echo "[DNSHTTP] Initialization: Executing entry point."

##
## Update/Upgrade Handling
##
if [ "$sf_ct_update_on_start" = "1" ]; then
    echo "[DNSHTTP] Update: Update on Start is enabled. Performing Update."
    echo "[DNSHTTP] Update: Please wait, this may take a few Minutes."
    apt-get update -qq
        DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq
elif [ "$sf_ct_update_on_start" = "0" ]; then
    echo "[DNSHTTP] Update: Update on Start is disabled."
else
    echo "[DNSHTTP] Update: Invalid Value for Variable: sf_ct_update_on_start."
fi

##
## Timezone Update
##
echo "[DNSHTTP] Timezone: Updating Time Zone Locale to $sf_timezone."
if [ -n "$sf_timezone" ]; then
    ln -snf "/usr/share/zoneinfo/$sf_timezone" /etc/localtime
    echo "$sf_timezone" > /etc/timezone
fi

##
## Setup Permissions
##
echo "[DNSHTTP] Permissions: Setting Chmod to 0644 on: /etc/cron.d/suitefish.";
chmod 0644 /etc/cron.d/suitefish > /dev/null 2>&1
echo "[DNSHTTP] Permissions: Setting Chmod to 0770 on: /var/www.";
chmod 0770 /var/www -R > /dev/null 2>&1
echo "[DNSHTTP] Permissions: Setting Owner to www-data on: /var/www.";
chown www-data:www-data /var/www -R > /dev/null 2>&1
echo "[DNSHTTP] Permissions: Setting Owner to www-data on: /var/log/suitefish_log.log.";
chown www-data:www-data /var/log/suitefish_log.log > /dev/null 2>&1
echo "[DNSHTTP] Permissions: Setting Owner to www-data on: /var/log/suitefish_error.log.";
chown www-data:www-data /var/log/suitefish_error.log > /dev/null 2>&1

##
## Certificate Handling (including LetsEncrypt)
##
if [ "$sf_letsencrypt_enable" -eq 1 ]; then
    echo "[DNSHTTP] SSL-Certificate: LetsEncrypt Certificate creation is enabled."

    if ! command -v certbot >/dev/null 2>&1; then
        echo "[DNSHTTP] SSL-Certificate: Certbot not found, installing now."
        apt-get update -qq
                DEBIAN_FRONTEND=noninteractive apt-get install certbot -y -qq
        if [ $? -ne 0 ]; then
            echo "[DNSHTTP] SSL-Certificate: Certbot installation failed!"
                        if [ ! -f "/opt/sf_ssl/privkey.pem" ] || [ ! -f "/opt/sf_ssl/cert.pem" ]; then
                                echo "[DNSHTTP] SSL-Certificate: Certificate File cert.pem and privkey.pem NOT found in SSL-Storage."
                                echo "[DNSHTTP] SSL-Certificate: Starting Custom Certificate Generation."
                                unlink /opt/sf_ssl/privkey.pem > /dev/null 2>&1
                                unlink /opt/sf_ssl/cert.pem > /dev/null 2>&1
                                openssl req -x509 -nodes -days 7300 -newkey rsa:2048 \
                                 -keyout /opt/sf_ssl/privkey.pem \
                                 -out /opt/sf_ssl/cert.pem \
                                 -subj "/C=US/ST=State/L=City/O=Organization/OU=OrgUnit/CN=example.com" > /dev/null 2>&1
                        else 
                                echo "[DNSHTTP] SSL-Certificate: Fallback to Certificate in SSL-Storage."
                        fi
        fi
    fi
    if command -v certbot >/dev/null 2>&1; then
        echo "[DNSHTTP] SSL-Certificate: Creating/Renewing Certificate if required."
                certbot certonly --standalone \
                        --non-interactive \
                        --agree-tos \
                        --email $sf_letsencrypt_email \
                        -d $sf_letsencrypt_domain
                if [ -f "/etc/letsencrypt/live/$sf_letsencrypt_domain/privkey.pem" ] && [ -f "/etc/letsencrypt/live/$sf_letsencrypt_domain/cert.pem" ]; then
                        echo "[DNSHTTP] SSL-Certificate: LetsEncrypt Certificate Creation/Renewing successfull."
                        echo "[DNSHTTP] SSL-Certificate: Duplicating Certificate to SSL-Storage."
                        unlink /opt/sf_ssl/privkey.pem > /dev/null 2>&1
                        unlink /opt/sf_ssl/cert.pem > /dev/null 2>&1
                        cp "/etc/letsencrypt/live/$sf_letsencrypt_domain/privkey.pem" /opt/sf_ssl/privkey.pem
                        cp "/etc/letsencrypt/live/$sf_letsencrypt_domain/cert.pem" /opt/sf_ssl/cert.pem
                else
                        echo "[DNSHTTP] SSL-Certificate: LetsEncrypt Certificate Creation/Renewing failed."
                        echo "[DNSHTTP] SSL-Certificate: Fallback to Certificate in SSL-Storage."
                fi
    fi
fi

##
## Certificate Handling (without LetsEncrypt)
##
if [ "$sf_letsencrypt_enable" -eq 0 ]; then
    echo "[DNSHTTP] SSL-Certificate: LetsEncrypt Certificate creation is disabled."
        if [ ! -f "/opt/sf_ssl/privkey.pem" ] || [ ! -f "/opt/sf_ssl/cert.pem" ]; then
                echo "[DNSHTTP] SSL-Certificate: Certificate File cert.pem and privkey.pem NOT found in SSL-Storage."
                echo "[DNSHTTP] SSL-Certificate: Starting Custom Certificate Generation."
                unlink /opt/sf_ssl/privkey.pem > /dev/null 2>&1
                unlink /opt/sf_ssl/cert.pem > /dev/null 2>&1
                openssl req -x509 -nodes -days 7300 -newkey rsa:2048 \
                 -keyout /opt/sf_ssl/privkey.pem \
                 -out /opt/sf_ssl/cert.pem \
                 -subj "/C=US/ST=State/L=City/O=Organization/OU=OrgUnit/CN=example.com" > /dev/null 2>&1
        else
                echo "[DNSHTTP] SSL-Certificate: Certificate File cert.pem and privkey.pem found in SSL-Storage."
                echo "[DNSHTTP] SSL-Certificate: Skipping Custom Certificate Generation."
        fi
fi

##
## Database Operations
##
echo "[DNSHTTP] MySQL: Starting database service.";
service mariadb start > /dev/null 2>&1
echo "[DNSHTTP] MySQL: Waiting 5 seconds.";
sleep 5
echo "[DNSHTTP] MySQL: Update Initial Environment MySQL Root Password."
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$sf_db_pass';" > /dev/null 2>&1
echo "[DNSHTTP] MySQL: Create Initial MySQL Database if not exists."
mysql -u root -p"$sf_db_pass" -e "CREATE DATABASE IF NOT EXISTS $sf_db_db;" > /dev/null 2>&1

##
## Cronjob Operations
##
echo "[DNSHTTP] Cronjob: Starting Cron Service.";
service cron start > /dev/null 2>&1
echo "[DNSHTTP] Cronjob: Waiting 5 Seconds.";
sleep 5
echo "[DNSHTTP] Cronjob: Enable Cronjob File /etc/cron.d/suitefish.";
crontab /etc/cron.d/suitefish
echo "[DNSHTTP] Cronjob: Stop Cron Service - to be started by Supervisor.";
service cron stop > /dev/null 2>&1
echo "[DNSHTTP] Cronjob: Waiting 2 Seconds.";
sleep 2

##
## Cronjob Operations
##
echo "[DNSHTTP] Update Container Variables File /etc/container.env.";
printenv | sed 's/^export //g' > /etc/container.env
chmod 600 /etc/container.env
chown www-data:www-data /etc/container.env

##
## Restart Bind
##
echo "[DNSHTTP] Stop Bind 9";
mv /etc/bind/named.conf /etc/bind/named.conf.backup.overwritte



##
## Execute the main CMD Dockerfile command passed to the container
##
echo "[DNSHTTP] Initialization: Finished Executing Entry Point."
echo "[DNSHTTP] Initialization: Starting Main Container Prompt.";
exec "$@"