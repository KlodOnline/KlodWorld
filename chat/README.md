# KlodOnline - Chat System (Node.js)

This is the real-time chat system used in **KlodOnline**. It is implemented as a standalone **Node.js server** using `socket.io` to manage WebSocket communication between connected clients.

> Important: This server is completely independent from the main game backend, which is managed by **Apache2** and handles all core game logic, HTTP routes, database access, and persistent state.

## Overview

- Each game world runs its own instance of the chat server.
- Players connect using WebSocket (`ws://` or `wss://`) with an authentication token.
- Upon connection:
  - The server verifies the JWT token.
  - If valid, the player's ID and name are extracted and registered.
  - The player is added to the `"ingame"` room.

## Client Integration

The client-side logic of the chat system is implemented as a dedicated module inside the game's graphical user interface (GUI), in `KlodWorld/www/js/tchat.js` relying on a IO module `KlodWorld/www/js/client_io.js`.

Actually this needs to be rewritten, as tchat.js should be a stand-alone socket io system.

## Authentication

- The client must provide a valid JWT token as a `token` query parameter.
- The token is verified using the `jwt_secret` defined in the world configuration (`world_ini`).
- If the token is invalid, or if the player has not paid (outside demo mode), the connection is refused.

## Features

### Commands

(todo : add here a description of chat commands)

## Scope and Responsibility

- This chat server is meant for real-time communication **only**. Sadly it needs to manage JWTokens, not sure if I can do something about it.

## Deployment Notes

- The chat server listens on a specific port (e.g. 2080) and expects to be proxied via Apache if HTTPS is required.
- The `jwt_secret` and other world-specific configurations must be provided at runtime via `world_ini`.
