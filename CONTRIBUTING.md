
# Contributing to KlodOnline

Thanks for considering contributing to the project! To keep the codebase consistent and maintainable, please follow the guidelines below.

---

## General Guideline

- This is not a democracy. This project reflects *my* vision of what an MMO-Strategy game should be. Anyone who shares this vision is welcome. Suggestions are appreciated, but the final word is mine. And so :
- Any idea that would impact the gameplay ? Ask Tunkasina first.
- English is the main language of the project to allow contributors from all over the world to participate.
- Join the [Discord](https://discord.gg/UcyS3enr), and don't be an ass.

---

## Code Style Conventions

### PHP (Back-end: KlodWeb / KlodWorld)

- **Classes**: `PascalCase`  
  `class GameMaster`, `class PlayerStatsManager`

- **Methods**: `camelCase`  
  `function startTurn()`, `function addResource()`

- **Variables & properties**: `snake_case`  
  `$player_id`, `$current_turn`, `$world_config`

- **File names**: `snake_case.php`  
  `world_engine.php`, `game_logic.php`

- **Standards**: Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) when possible.

---

### JavaScript (Front-end, client logic, UI)

- **Everything**: `camelCase`  
  `let playerId = 0;`, `function sendMessage() {}`

- **File names**: `kebab-case.js`  
  `client-io.js`, `game-ui.js`

- **Modules**: Prefer ES6 syntax when possible  
  `export function updateWorldState() {}`

---

## Switching Between PHP and JS

Keep in mind:
| Language | Variables | Functions/Methods | Classes | Files         |
|----------|-----------|-------------------|---------|---------------|
| PHP      | `snake_case` | `CamelCase()`     | `CamelCase` | `snake_case.php` |
| JavaScript | `camelCase` | `camelCase()`     | `CamelCase` (if needed) | `kebab-case.js` |

---


### SQL / Database

- **Tables and fields**: `snake_case`  
  `player_data`, `world_settings`, `empire_id`

- **Naming**: Keep lowercase, no plural tables unless logical (`players`, `cities`)

---

## Commit Messages

Please **read and follow** the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification.
You can help yourself with [Angular convention](https://github.com/angular/angular/blob/22b96b9/CONTRIBUTING.md#-commit-message-guidelines) too.

### Examples:

- `feat(game): add support for divine missions`
- `fix(chat): prevent crash on empty messages`
- `docs(setup): add instructions for database reset`
- `refactor(world): extract army logic to separate file`
- `test(api): add tests for token validation`

### Types suggested :
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Formatting, missing semicolons, etc.
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `chore`: Maintenance, build tools, etc.

