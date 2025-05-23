# KlodWorld

## General Policies

- This is not a democracy. This project reflects *my* vision of what an MMO-Strategy game should be. Anyone who shares this vision is welcome. Suggestions are appreciated, but the final word is mine.
- Please **read and follow** the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification.
- English is the main language of the project to allow contributors from all over the world to participate.

## To-Do List

- Add a README in the `setup` folder to explain how to deploy a game world
- Refactor code to improve readability and make teamwork easier
- Review and harden the authentication process
- Implement a streamlined world setup (ensure `setup.sh` can launch a fully functional world with default names and passwords)
- Add monitoring/logging tools for live world management
- Optimize performance for large-scale, persistent simulations

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

## General Organization

_To be written._  
(This section will eventually describe the folder structure, server architecture, main components, and responsibilities.)

## Setup

See the `README.md` file in the [`setup`](./setup) folder for installation instructions.