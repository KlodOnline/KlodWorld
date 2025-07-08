# KlodWorld
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/KlodOnline/KlodWorld)

- For contribution guidelines, see [CONTRIBUTING.md](./CONTRIBUTING.md).

## Purpose

**KlodWorld** is the game server — it powers the actual gameplay. Players interact with it in real-time, and Game Masters use it to configure and manage game worlds.

Each world is independent and can have its own rules, defined via a Game Master interface. The server handles:
- Procedural map generation (biomes, terrain, hex wrapping, etc.)
- Empire and city management
- Logistics, economy, units, and combat
- Turn synchronization every 5 minutes
- Divine missions and AI systems
- Persistent world state and save/load

It is designed to support both:
- Free demo worlds (reset monthly)
- Premium persistent worlds

## General Code Organization

- **`chat/`**: Node.js server dedicated to real-time chat. Contains server files (`klodchat.js`), JS helpers, and `package.json` for dependencies.
- **`common/`**: Shared PHP libraries and files back/front
  - `includes/`: PHP functions for database access, hex map management, session handling, world generation, etc.  
  - `param/`: configuration files (`config.ini`, rules).  
  - `status.json`: global status or info file.
- **`game/`**: Main PHP backend logic for the game.  
  - `klodgame.php`: core game logic.  
  - `backend_init.php`: backend initialization.  
  - `turn_manager.php`: synchronous turn management.
- **`setup/`**: Installation scripts and SQL database schema (`worldserver.sql`).
- **`www/`**: Frontend web resources and files.  
  - `css/`: stylesheets.  
  - `js/`: client-side JavaScript for UI, interactions, chat client, etc.  
  - `includes/`: frontend PHP scripts.  
  - `pics/`: images and icons.  
  - `soundtrack/`: game music.  
  - Entry PHP files like `index.php`.


## Setup

See the `README.md` file in the [`setup`](./setup) folder for installation and maintenance instructions.
