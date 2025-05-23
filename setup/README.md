# Setup

This folder contains the scripts and tools needed to automate the installation of a _KlodWeb_ server.

## Requirements

- A working LAMP environment (Linux + Apache + MySQL + PHP)
  - Can be a virtual machine, container (CT), or bare-metal server
- Root access to install and configure services
- Access to the KlodWeb database to register the world server

## Usage

To deploy a **KlodWorld** server on your own infrastructure/homelab:
1. Set up a LAMP environment (can be a VM, container, or bare-metal).
2. Copy the source files to `/var/klodweb/`.
3. Navigate to the `/var/klodweb/setup` directory.
4. If needed, edit the Manual variables section in `install.sh`.
5. Run `setup.sh` with root privileges.
Your server should now be accessible at: `https://<your-ip-or-domain>:443`
6. At the end of the setup, follow the prompts to register the world server in the **KlodWeb** database. This step is necessary to make the world accessible to players via the portal.
Your server should now be accessible at `https://<your-ip-or-domain>:443` for the web interface, and at `https://<your-ip-or-domain>:8080` for the nodejs websocket allowing that system. 
