'use strict'

/*==============================================================================
    Tchat v0.3.0 - Unified WebSocket Chat Module
    Handles real-time chat and socket communication
==============================================================================*/

function Tchat() {
  if (typeof token === 'undefined') {
    location.href = WEBSITE
    return
  }

  const socket = io.connect(`http://${WORLD_IP}:${CHATPORT}`, {
    query: { token }
  })

  let history = []
  let currKey = 0
  let fadeoutTimer = null

  const humanDate = () => {
    const d = new Date()
    return (
      `[${String(d.getHours()).padStart(2, '0')}:` +
      `${String(d.getMinutes()).padStart(2, '0')}:` +
      `${String(d.getSeconds()).padStart(2, '0')}]`
    )
  }

  const sendWS = (type, msg = '', callback = () => {}) => {
    const payload = JSON.stringify({ t: type, m: msg })
    socket.emit('COM', payload, callback)
  }

  const receiveMsg = (nick, msg, color = '#d3d3d3') => {
    msg = msg.replace(/\[[0-9]+,[0-9]+\]/g, (c) => `<span class="coords">${c}</span>`)
    const txt = `<span style="color:${color}">${humanDate()} [<span class="nick">${nick}</span>]: ${msg}</span><br/>`
    $('#messages')
      .append(txt)
      .animate({ scrollTop: $('#messages')[0].scrollHeight }, 50)
    if (document.hidden && color === '#F980ef') blinkTab('New whisp !')
    fadeToVisible()
  }

  const clientTalk = (msg) => receiveMsg('Client', msg, '#efcd25')

  const commands = {
    help: () =>
      clientTalk(
        `/help : This message\n` +
          `/ig : Number of players online\n` +
          `/w NICK : Private message to [NICK]`
      ),
    ig: () => sendWS('WLC'),
    iga: () => sendWS('WLCA'),
    w: (args) => {
      const nick = args[1]
      const msg = args.slice(2).join(' ')
      sendWS('WHISP', { nick, msg })
    }
  }

  const send = (msg) => sendWS('TCH', msg)

  const fadeToVisible = (withTimer = true) => {
    clearTimeout(fadeoutTimer)
    $('#tchat').stop(true, true).fadeTo(300, 1)
    if (withTimer) {
      fadeoutTimer = setTimeout(() => {
        $('#tchat').stop(true, true).fadeTo(15000, 0.1)
      }, 3000)
    }
  }

  // UI Setup
  $('#tchat').resizable({ containment: 'body' })
  $('#tchat').draggable({ containment: 'body' })

  $('#tchat').mouseleave(() => fadeToVisible())
  $('#tchat').mouseenter(() => fadeToVisible(false))
  $('#tchat_input').focus(() => fadeToVisible())

  $('#tchat').on('click', '.nick', function () {
    $('#tchat_input')
      .val('/w ' + $(this).text() + ' ')
      .focus()
    return false
  })

  $('#tchat').on('click', '.coords', function () {
    const coords = $(this).text().slice(1, -1).split(',')
    const latlng = H.coord_to_pixel(H.coord(coords, 'oddr'), H_W, H_H, H_S)
    Mv.goto([latlng[1], latlng[0]])
  })

  $('#tchat_input').keyup((e) => {
    fadeToVisible()

    if (e.key === 'ArrowUp') {
      currKey = Math.max(0, currKey - 1)
      $('#tchat_input').val(history[currKey] || '')
      return false
    }
    if (e.key === 'ArrowDown') {
      currKey = Math.min(history.length, currKey + 1)
      $('#tchat_input').val(history[currKey] || '')
      return false
    }
    if (e.key === 'Enter') {
      const txt = $('#tchat_input').val()
      if (!txt) return false

      history.push(txt)
      currKey = history.length

      if (txt.startsWith('/')) {
        const args = txt.split(' ')
        const cmd = args[0].slice(1)
        const func = commands[cmd]
        if (typeof func === 'function') {
          func(args)
        } else {
          clientTalk('Unknown command. Use /help for valid commands.')
        }
      } else {
        send(txt)
      }

      $('#tchat_input').val('')
      return false
    }
  })

  // WebSocket Incoming Msg
  socket.on('TCH', (data) => receiveMsg(data.name, data.msg, data.col))

  // Optionally expose for debugging
  this.socket = socket
  this.send = send
  this.ask = sendWS
  this.disconnect = () => socket.disconnect()
  this.reconnect = () => socket.connect()
}

const T = new Tchat()
