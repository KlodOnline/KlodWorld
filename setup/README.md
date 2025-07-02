# Setup

This folder contains some of the scripts and tools needed to automate the installation of a _KlodWorld_ server.

## How-To (for Dev)
### The Docker Way
 - Navigate to your own projects folder
 - `git clone --branch develop https://github.com/KlodOnline/KlodWorld.git`
 - If you use Docker Desktop, allow the KlodWorld folder to be shared with the container:
   `Settings > Resources > File sharing > [Add your KlodWorld path]`
 - `cd KlodWorld`
 - `make up` (will create the whole stack)

Your server should now be accessible at: `https://127.0.0.1:2443`.

⚠️You need to add your new Klodworld on the world list of KlodWeb to access it ! In ***KlodWeb folder***, do :
   - `make sh-db`
   - `mariadb -p` (default password for dev env is `rootpass`)
   - `use klodwebsite;`
   - `INSERT INTO world (name, address, demo) VALUES ('Worldname', 'https://127.0.0.1:2443', 'false');`

To open an interactive shell inside the containers:
   - db     → `make sh-db`
   - php    → `make sh-php`
   - apache → `make sh-apache`
   - node   → `make sh-node`
   - game   → `make sh-game`

If you need to connect to the database from inside the container, run:
- `make sh-db`
- Then inside the container: `mariadb -p`
- Enter the root password defined in your `docker-compose.yml` (`rootpass` by default).

To reset the database (⚠️ destructive):
- `make clean-db`

Other useful commands:
- `make ps`   # Show the status of all containers
- `make logs` # Follow the logs of all containers
- `make down` # Stop and remove all containers

## How-To (for Production)
Work in progress.

### Requirements
Work in progress.

### How-to
Work in progress.
