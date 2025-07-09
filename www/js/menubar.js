/*==============================================================================
    Menubar Module
==============================================================================*/
'use strict'

function Menubar() {
  this.next_tic = 0 // Time to the next tic
  this.last_turn = 0 // turn number
  this.client_time = 0 // Needed for clock
  this.last_mtime = 0 // Needed for status.json update
  this.recurrentFetching // Variable to store interval ID
  this.last_refresh_interval = 1
  this.tic_sec = 0

  // ==== METHODS ============================================================
  // This function schedule fetching of server data, and change is own
  // interval if needed according to updated info.
  this.scheduleFetching = function () {
    this.last_refresh_interval = this.refresh_interval()
    if (this.recurrentFetching) {
      clearInterval(this.recurrentFetching)
    }
    // Schedule Recurrent Fetching every (this.last_refresh_interval) ms :
    this.recurrentFetching = setInterval(() => {
      this.fetchData()
      this.updateTimer(this.last_refresh_interval / 1000)
      // Restart fetching with updated interval if needed :
      if (this.last_refresh_interval != this.refresh_interval()) {
        this.scheduleFetching()
      }
    }, this.last_refresh_interval)
  }
  // Refresh interval is done according to how long time needed to next tic
  this.refresh_interval = function () {
    if (this.sec_to_go() > 60) {
      return 5000
    }
    return 1000
  }
  // sec to go to the end of the next tic
  this.sec_to_go = function () {
    return this.next_tic - this.client_time
  }
  // Client timer, estimation of server time.
  this.updateTimer = function (time_elapsed) {
    this.client_time = this.client_time + time_elapsed

    // console.log('Current Time :'+formatTimestamp(this.client_time)+' / Next Tic :'+formatTimestamp(this.next_tic));
  }
  // Update information from server session data
  this.updateFromServer = function (data) {
    if (data.mod_time !== this.last_mtime) {
      this.last_turn = data.last_turn

      this.last_mtime = data.mod_time

      this.next_tic = data.timestamp + data.sec // Set next tic time
      // this.next_tic = data.mod_time + data.sec; // Set next tic time

      this.tic_sec = data.sec
      this.client_time = data.server_time // Syncing clocks
      this.lock = data.lock

      console.log('Fresh infos ! - Local Clock adjusted.')
      console.log(
        'Info : T:' +
          this.last_turn +
          ', mT:' +
          this.last_mtime +
          ', nT:' +
          this.next_tic +
          ', L:' +
          this.lock +
          ', cT:' +
          this.client_time
      )
      Mv.server_rst()
    }
  }
  // Method to retrieve data from the server
  this.fetchData = function () {
    $.getJSON('../includes/tic_reader.php', (data) => {
      this.updateFromServer(data)
      this.update_clock(this.next_tic, this.client_time)
      // console.log('Server locked ? = ' + this.lock);
    }).fail((data) => {
      console.error(data) // Log error
    })
  }
  // Update the graphical view of the clock ! --------------------------------
  this.update_clock = function (next_tic, time) {
    const sec_to_go = this.sec_to_go()

    // Color of text
    let sec_class = 'dn' // Red Class "alert"
    if (this.lock == true) {
      sec_class = 'ld'
    } // Orange Class "Locked"
    else {
      sec_class = 'go'
    } // Green Class "Ok"

    let display_time
    const abs_sec_to_go = Math.abs(sec_to_go)

    if (abs_sec_to_go < 60) {
      display_time = abs_sec_to_go + ' sec.'
    } else if (abs_sec_to_go < 3600) {
      display_time = Math.ceil(abs_sec_to_go / 60) + ' min.'
    } else {
      display_time = Math.ceil(abs_sec_to_go / 3600) + ' h'
    }

    if (sec_to_go < 0) {
      display_time = '-' + display_time // Affiche en négatif si déjà écoulé
    }

    $('#server_info').html(
      '<div id="' +
        sec_class +
        '">' +
        display_time +
        '</div><div id="turn">' +
        this.last_turn +
        '</div>'
    )
  }

  this.scheduleFetching() // Restart fetching with updated interval

  // Click event handling
  // $('#server_main').on('click', '.quit', function(e) {  Pp.show('ASK', 'QUIT');   });
  $('#server_main').on('click', '.quit', function (e) {
    Pp.confirmPopup({
      text: 'Want to leave now?',
      ok: function () {
        document.location.href = WEBSITE
      } // Redirige si confirmé
    })
  })
}
