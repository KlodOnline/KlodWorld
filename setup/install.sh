#!/bin/bash
# ================================================
#   Manual variables
# ================================================
BASE_DIR="/var/klodworld"
LOG_DIR="/var/log/klodworld"

CONFIG_FILE="$BASE_DIR/common/param/config.ini"
APACHE_SITE="ssl-klodclient"
# APACHE_CONF_FILE="/etc/apache2/sites-available/ssl-klodclient.conf"
PHP_INI="/etc/php/8.2/apache2/php.ini"

LOGROTATE_FILE="/etc/logrotate.d/klodworld"

# Daemon names 
CHAT_DAEMON="klodchat"
GAME_DAEMON="klodgame"
GAME_WEBUI="klodwebui"

# Network parameters
# Adress of the public website for KlodOnline with protocol & port
MAIN_WEBSITE="https://127.0.0.1:1443"

# Adress of the current world
# IP should be public - domain name should be used here
WORLD_IP='127.0.0.1'
# External ports ! (Internals will be 8080 and 443)
# Ask to your adminsys if not sure, it's the exposed ports
CHAT_PORT="2080"
GAME_PORT="2443"

# BDD INFO
# Password is self generated if omitted.
# (put DB_PASS in # if in production)
DB_HOST="localhost"
DB_USER="klodadmin"
DB_PASS='Pw3Lqb6fuLspT7IrYp'
DB_NAME="klodonline"
BATCH_SIZE="2000"

# Ask personnlisation info
read -p "  Choose a name for the world (name): " world_name
read -p "  Enter columns number     (max_col): " max_col
read -p "  Enter rows number        (max_row): " max_row
read -p "  Choose demo mode      (true/false): " demo
read -p "  TIC length in seconds        (TIC): " tic_sec

# ================================================
#   Requisites installation
# ================================================
apt-get update
# Basic LAMP Stack
apt-get install mariadb-server apache2 php php-mysqli php-xml curl php-apcu -y
# Additionals php modules 
# apt-get install -y php-cli php-curl php-json php-xml php-mbstring
# apt-get install -y php-apcu
# NodeJS & modules
apt-get install nodejs npm -y

mkdir $LOG_DIR

echo ">>> End of requisites installations."

# ================================================
#   config.ini file creation
# ================================================

# Generate a password if undefined before
DB_PASS="${DB_PASS:=$(openssl rand -base64 12)}"

# Find Public IP if unprecised
WORLD_IP="${WORLD_IP:=$(curl -s ifconfig.me)}"

# Config INI generation
cat <<EOL > $CONFIG_FILE
; Configuration file
[sql]
host = "$DB_HOST"
user = "$DB_USER"
password = "$DB_PASS"
database = "$DB_NAME"
save_mode = "sql"
load_mode = "sql"
batch_size = $BATCH_SIZE

[world]
world_name = "$world_name"
max_col = $max_col
max_row = $max_row
demo = "$demo"
tic_sec = $tic_sec
website = "$MAIN_WEBSITE"
world_ip = "$WORLD_IP"
chat_port = $CHAT_PORT
game_port = $GAME_PORT
jwt_secret = "SECRET_KEY"
EOL

# Générer la commande SQL
sql_command="INSERT INTO world (name, address, demo) VALUES ('$world_name', 'https://$WORLD_IP:2443', '$demo');"

echo ">>> End of INI file configuration."

# ================================================
#   MariaDB Configuration
# ================================================

# Change Innodb buffer
# 320*200 ~8Mo bdd size Ground only
# 640*480 ~32Mo bdd size Ground only
echo "[mysqld]" >> /etc/mysql/my.cnf
echo "innodb_buffer_pool_size=256M" >> /etc/mysql/my.cnf

# Import SQL File (should be up to date)
mysql < worldserver.sql

# User creation
mysql -h "$DB_HOST" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASS';"

if [ $? -eq 0 ]; then
    echo "User $DB_USER created with sucess."
else
    echo "Faileure while creating user $DB_USER."
    exit 1
fi

# User right attribution
mysql -h "$DB_HOST" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'$DB_HOST';"

if [ $? -eq 0 ]; then
    echo "Granted access to $DB_USER on $DB_NAME@$DB_HOST."
else
    echo "Failure while granted access to $DB_USER."
    exit 1
fi

# Refresh privileges
mysql -h "$DB_HOST" -e "FLUSH PRIVILEGES;"

echo ">>> End of MariaDB configuration."

# ================================================
#	Apache Configuration
# ================================================
for site in /etc/apache2/sites-enabled/*; do
    a2dissite "$(basename "$site")"
done

# Activate SSL
sudo a2enmod ssl > /dev/null 2>&1

# Creating config file of web part of KlodWorld
echo "<VirtualHost *:443>

    DocumentRoot $BASE_DIR/www

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
    
    <Directory $BASE_DIR/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog $LOG_DIR/$GAME_WEBUI.log
    CustomLog $LOG_DIR/$GAME_WEBUI.log combined
        
</VirtualHost>" > /etc/apache2/sites-available/$APACHE_SITE.conf

a2ensite $APACHE_SITE
service apache2 reload

echo ">>> End of Apache configuration."

# ================================================
#   PHP Configuration
# ================================================

# Ajouter les lignes de configuration APCu
echo "" >> "$PHP_INI"
echo "[apcu]" >> "$PHP_INI"
echo "apc.shm_size = 256M" >> "$PHP_INI"
# echo "apc.mmap_file_mask = /dev/shm/apc_cache_file" >> "$PHP_INI"
echo "apc.shm_segments = 1" >> "$PHP_INI"

# ================================================
#   Chat Daemon Configuration
# ================================================

cd $BASE_DIR/chat
# npm audit fix --force
# npm install --force

# Init Script Creation
echo "#!/bin/sh
### BEGIN INIT INFO
# Provides:          $CHAT_DAEMON
# Required-Start:    \$local_fs
# Required-Stop:     \$local_fs
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Launch $CHAT_DAEMON daemon
### END INIT INFO

status() {
	echo '___________'
	if pgrep -xf 'nodejs $BASE_DIR/chat/$CHAT_DAEMON.js' > /dev/null; then
		echo \"   \e[32m●\e[0m $CHAT_DAEMON is\e[32m running\e[0m.\"
	else
        echo '  \e[31m●\e[0m $CHAT_DAEMON is\e[31m not running\e[0m.\'
	fi
    echo '    Last log entry :'
    echo '___________'
    if [ -f $LOG_DIR/$CHAT_DAEMON.log ]; then
        tail $LOG_DIR/$CHAT_DAEMON.log
    fi
    echo '___________'
}

start() {
    echo 'Starting $CHAT_DAEMON...'
    nodejs $BASE_DIR/chat/$CHAT_DAEMON.js >> $LOG_DIR/$CHAT_DAEMON.log 2>&1 &
}

stop() {
    echo -n 'Stopping $CHAT_DAEMON...'
    if pkill -xf 'nodejs $BASE_DIR/chat/$CHAT_DAEMON.js'; then
	    while pgrep -xf 'nodejs $BASE_DIR/chat/$CHAT_DAEMON.js' > /dev/null; do
	        echo -n '.'
	        sleep 1
	    done    
        echo '$CHAT_DAEMON stopped.'
    else
        echo '$CHAT_DAEMON is not started.'
    fi    
}

restart() {
    stop
    start
}

case "\$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    status)
        status
        ;;
    *)
        echo 'Usage: /etc/init.d/$CHAT_DAEMON {start|stop|status}'
        exit 1
        ;;
esac
exit 0
" | tee /etc/init.d/$CHAT_DAEMON > /dev/null

# Rendre le script exécutable
chmod +x /etc/init.d/$CHAT_DAEMON

# Ajouter le script aux niveaux d'exécution
update-rc.d $CHAT_DAEMON defaults

echo "Service '$CHAT_DAEMON' created with success."

# service $CHAT_DAEMON start
service $CHAT_DAEMON status

echo ">>> End of '$CHAT_DAEMON' daemon configuration."

# ================================================
#	Game Daemon Configuration
# ================================================

# Init Script Creation
echo "#!/bin/sh
### BEGIN INIT INFO
# Provides:          $GAME_DAEMON
# Required-Start:    \$local_fs
# Required-Stop:     \$local_fs
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Launch $GAME_DAEMON daemon
### END INIT INFO

status() {
	echo '___________'
	if pgrep -xf 'php $BASE_DIR/game/$GAME_DAEMON.php' > /dev/null; then
		echo \"   \e[32m●\e[0m $GAME_DAEMON is\e[32m running\e[0m.\"
	else
    	echo \"   \e[31m●\e[0m $GAME_DAEMON is\e[31m not running\e[0m.\"
	fi
    echo '    Last log entry :'
    echo '___________'
    if [ -f $LOG_DIR/$GAME_DAEMON.log ]; then
        tail $LOG_DIR/$GAME_DAEMON.log
    fi
    echo '___________'
}

start() {
    echo 'Starting $GAME_DAEMON...'
    php $BASE_DIR/game/$GAME_DAEMON.php >> $LOG_DIR/$GAME_DAEMON.log 2>&1 &
}

stop() {
    echo -n 'Stopping $GAME_DAEMON...'
    if pkill -xf 'php $BASE_DIR/game/$GAME_DAEMON.php'; then
	    while pgrep -xf 'php $BASE_DIR/game/$GAME_DAEMON.php' > /dev/null; do
	        echo -n '.'
	        sleep 1
	    done    
        echo '$GAME_DAEMON stopped.'
    else
        echo '$GAME_DAEMON is not started.'
    fi    
}

restart() {
    stop
    start
}

case "\$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    status)
        status
        ;;
    *)
        echo 'Usage: /etc/init.d/$GAME_DAEMON {start|stop|status}'
        exit 1
        ;;
esac
exit 0
" | tee /etc/init.d/$GAME_DAEMON > /dev/null

# Rendre le script exécutable
chmod +x /etc/init.d/$GAME_DAEMON

# Ajouter le script aux niveaux d'exécution
update-rc.d $GAME_DAEMON defaults

echo "Service $GAME_DAEMON created with success."

# service $GAME_DAEMON start
service $GAME_DAEMON status

echo ">>> End of '$GAME_DAEMON' daemon configuration."

# ================================================
#	Logrotate configuration
# ================================================

# Contenu de la configuration logrotate
cat <<EOF > $LOGROTATE_FILE
/var/log/klodworld/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 root adm
    copytruncate
}
EOF

echo ">>> End of logrotate configuration to $LOGROTATE_FILE."

# ================================================
#   FINI
# ================================================

echo ">>> End of KlodWorld configuration !"

# Afficher les résultats
echo "To add this world to KlodWeb, do in its Database :"
echo "$sql_command"
