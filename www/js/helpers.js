/*------------------------------------------------------------------------------
    garder des fonctions globales ? OUI
------------------------------------------------------------------------------*/

/* -----------------------------------------------------------------------------
	LOGGING LOGIC
----------------------------------------------------------------------------- */
let LOG_LEVEL = 0

var allowLogs = function ($level = 1) {
  LOG_LEVEL = $level
}
var disableLogs = function () {
  LOG_LEVEL = 0
}
var logMessage = function (stringData) {
  if (LOG_LEVEL >= 1) {
    const error = new Error()
    const stackLine = error.stack.split('\n')[1] // Ligne de l'appelant
    const fileName = stackLine.trim().split('/').pop()
    console.log('[' + fileName + '] ' + stringData)
  }
}
var logForce = function (stringData, who = '?') {
  const old_log_level = LOG_LEVEL
  LOG_LEVEL = 1
  logMessage(stringData, who)
  LOG_LEVEL = old_log_level
}

/* -----------------------------------------------------------------------------
	VARIOUS :
----------------------------------------------------------------------------- */

var Chronographe = function () {
  this.chrono_timers = {}

  var myTimer = function (name) {
    this.name = name
    this.start_time = performance.now()
    this.stop_time = null
    this.steps = []
    this.stop = function () {
      this.stop_time = performance.now()
    }
    this.step = function () {
      this.steps.push(performance.now())
    }
    this.log = function (min_time) {
      if (this.stop_time - this.start_time < min_time) {
        return false
      }

      logMessage(this.name + ' : ')
      //            logMessage(' Launched @'+this.start_time);
      for (const each_step in this.steps) {
        logMessage(1 * each_step + 1 + '- ' + (this.steps[each_step] - this.start_time) + ' ms.')
      }
      logMessage(' End : ' + (this.stop_time - this.start_time) + ' ms.')
    }
  }

  this.start = function (name) {
    timer = new myTimer(name)
    this.chrono_timers[name] = timer
  }
  this.stop = function (name) {
    this.chrono_timers[name].stop()
  }
  this.step = function (name) {
    this.chrono_timers[name].step()
  }

  this.log = function (min_time = 0) {
    for (const each_name in this.chrono_timers) {
      this.chrono_timers[each_name].log(min_time)
    }
  }
}

var blinkTab = function (message) {
  // Thanks to : https://howto.lintel.in/how-to-blink-browser-tab/
  var oldTitle = document.title,
    timeoutId,
    blink = function () {
      // Blink Title :
      if (document.title !== message) {
        document.title = message
      } else {
        document.title = '.'
      }
    },
    clear = function () {
      // function to set title back to original
      clearInterval(timeoutId)
      document.title = oldTitle
      window.onmousemove = null
      timeoutId = null
    }
  if (!timeoutId) {
    timeoutId = setInterval(blink, 1000)
    // stop changing title on moving the mouse
    window.onmousemove = clear
  }
}
var tic_to_date_OLD = function (exec_turn) {
  // Exec turn, c'est le tour d'execution ok.
  // Last turn, c'est le dernier joué
  // Il a donc EXEC - Last = Tour attente
  // -les secondes ecoulees depuis le last.
  let turn_to_go = exec_turn - Mb.last_turn
  let seconds_to_go = turn_to_go * Rules.tick
  let seconds_past = Rules.tick - Mb.next_tic
  let real_seconds_to_go = seconds_to_go - seconds_past

  let ms_to_go = real_seconds_to_go * 1000

  var now = Date.now()
  var date_in_ms = now + ms_to_go
  var tic_date = new Date(date_in_ms)

  return tic_date
}

var tic_to_date = function (exec_turn) {
  // Nombre de tours restant à exécuter
  let turn_to_go = exec_turn - Mb.last_turn

  // Temps total nécessaire pour atteindre le tour cible
  let seconds_to_go = turn_to_go * Rules.tick

  // Temps restant dans le tick actuel
  let now_seconds = Math.floor(Date.now() / 1000) // Heure actuelle en secondes
  let seconds_remaining_in_tick = Mb.next_tic - now_seconds

  // Temps réel restant avant le prochain tour
  let real_seconds_to_go = seconds_to_go - (Rules.tick - seconds_remaining_in_tick)

  // Conversion en millisecondes et calcul de la date cible
  let ms_to_go = real_seconds_to_go * 1000
  let tic_date = new Date(Date.now() + ms_to_go)

  return tic_date
}

var time_to_txt = function (date) {
  var now = new Date()
  var time_txt =
    String(date.getHours()).padStart(2, '0') +
    ':' +
    String(date.getMinutes()).padStart(2, '0') +
    ':' +
    String(date.getSeconds()).padStart(2, '0')
  var day_txt = 'Today'
  if (now.getDay() !== date.getDay()) {
    day_txt =
      String(date.getDate()).padStart(2, '0') +
      '/' +
      String(date.getMonth() + 1).padStart(2, '0') +
      '/' +
      String(date.getFullYear()).padStart(2, '0')
  }
  var full_date = day_txt + ' - ' + time_txt
  return full_date
}
var helper_color_class = function (player_color) {
  let string_color = player_color

  // logMessage('Color : '+string_color);

  if (string_color[0] == '#') {
    string_color = 'Ba Ba'
  }
  const stg_each_color = string_color.split(' ')
  const color_class = 'Bg' + stg_each_color[0] + ' Bo' + stg_each_color[1]
  return color_class
}

var string_short = function (a_string) {
  let hash = 0
  const strlen = a_string.length
  if (strlen === 0) {
    return hash
  }
  for (let i = 0; i < strlen; i++) {
    const c = a_string.charCodeAt(i)
    // hash = ((hash << 5) - hash) + c;
    hash = hash + c
    // hash = hash & hash; // Convert to 32bit integer
  }
  return hash
}

function formatTimestamp(timestamp) {
  const date = new Date(timestamp * 1000) // Convertir en millisecondes
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${hours}:${minutes}:${seconds}`
}

function getClassName(obj) {
  if (obj && obj.constructor && obj.constructor.name) {
    return obj.constructor.name // Récupère le nom de la classe
  }
  return null // Si l'objet n'a pas de constructeur
}

function entitiesHash(entities) {
  if (!entities || entities.length === 0) {
    return null // Pas d'entités, pas de hash
  }

  // Générer une empreinte simple unique
  return entities
    .map((entity) => JSON.stringify(entity)) // Convertir chaque entité en string JSON
    .join('|') // Combiner toutes les chaînes avec un séparateur
    .split('') // Transformer en tableau de caractères
    .reduce((acc, char) => acc + char.charCodeAt(0), 0) // Calculer la somme des codes ASCII
}

function entitiesIdString(entities) {
  if (!Array.isArray(entities) || entities.length === 0) {
    return null // Pas d'entités, pas de string
  }

  // Extraire les ID et les joindre en une seule chaîne
  return entities.map((entity) => entity.id).join('|')
}
