# Setup

This folder contains the scripts and tools needed to automate the installation of a _KlodWorld_ server.

## To-Do List

- `setup.sh` actually add daemons the "old way" because of Docker images (files in `/etc/init.d` and usage with `service [name] start|stop` etc.) The script should detect if we have **systemctl** available or not and behave properly according to.

## Usage

### Requirements

- A working LAMP environment (Linux + Apache + MySQL + PHP)
  - Can be a virtual machine, container (CT), or bare-metal server
- Root access to install and configure services
- Access to the KlodWeb database to register the world server

### How-to

To deploy a **KlodWorld** server on your own infrastructure/homelab:
1. Set up a LAMP environment (can be a VM, container, or bare-metal).
2. Copy the source files to `/var/klodworld/`.
3. Navigate to the `/var/klodworld/setup` directory.
4. If needed, edit the Manual variables section in `install.sh`.
5. Run `setup.sh` with root privileges.
Your server should now be accessible at: `https://<your-ip-or-domain>:443`
6. At the end of the setup, follow the prompts to register the world server in the **KlodWeb** database. This step is necessary to make the world accessible to players via the portal.

Your server should now be accessible at `https://<your-ip-or-domain>:443` for the web interface, and at `https://<your-ip-or-domain>:8080` for the nodejs websocket allowing that system. 

## Advanced Maintenance

### Services explanations

Server runs critical daemons : 
- **apache2** which allows :
  - Players to have a GUI at `www/index.php`
  - Game Masters to have a GUI at `www/gm_index.php`
  - Main website to have a status at `www/status.php`
- **klodgame** which runs turns in infinite loop at `game/klodgame.php`
- **klodchat** which allows players interactions at `chat/klodchat.js`
- **mariadb** which well... you knows.

klodgame & klodchat are custom daemons. They must be managed by usual command `service [daemon] start|stop|status`. Ports are defined by `common/param/config.ini`.

### Logs

Server-side logging belongs to /var/log/klodworld and are managed by logrotate, as it should.

### Manual Database Reset

In some cases (e.g. full reset, testing, corrupted data), you may need to reset the KlodWorld database.

#### ⚠️ WARNING

This process **erases all data** in the current world (players, cities, empires, etc.). Use with caution.

#### Steps

- **Stop services**  
  `service klodchat stop && service klodgame stop`

- **Drop the existing database** (in MariaDB prompt)  
  `DROP DATABASE IF EXISTS klodonline;`

- **Recreate the database from schema**  
  `mysql < worldserver.sql`

- **Restart services**  
  `service klodchat restart && service klodgame restart`

Your world instance should now be reset and ready for a clean start.
