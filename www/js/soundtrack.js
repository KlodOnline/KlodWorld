
const music = document.getElementById('background-music');
const toggleButton = document.getElementById('toggle-music');

allowLogs();

// Liste des musiques dans le répertoire "soundtrack"
const fullPlaylist = [];

// Liste de lecture
let playlist = [];

let currentTrackIndex = 0;
music.volume = 0.25;

// Charge la playlist depuis le répertoire "soundtrack"
const loadPlaylist = () => {
	fetch('soundtrack/')
		.then(response => response.text())
		.then(data => {
			const parser = new DOMParser();
			const doc = parser.parseFromString(data, 'text/html');
			const links = Array.from(doc.querySelectorAll('a'));
			fullPlaylist.splice(0, fullPlaylist.length);
			links.forEach(link => {
				const href = link.getAttribute('href');
				if (href && href.endsWith('.mp3')) {
					fullPlaylist.push('soundtrack/' + href);
				}
			});
			logMessage('Playlist chargée avec ' + fullPlaylist.length + ' musiques.');
			playRandomTrack();
			music.pause();
			toggleButton.textContent = 'Play Music';
		})
		.catch(error => {
			logMessage('Erreur lors du chargement des musiques : ' + error);
		});
}

// Réinitialise la playlist
const resetPlaylist = (ignoredSong="") => {
	if (fullPlaylist.length === 0) {
		logMessage("Error : No soundtrack in the playlist")
	}

	fullPlaylist.forEach(soundtrack => {
		if (soundtrack !== ignoredSong) {
			playlist.push(soundtrack);
		}
	})
}

// Fonction pour jouer une musique aléatoire
const playRandomTrack = () => {
	const randomIndex = Math.floor(Math.random() * playlist.length);
	music.src = playlist[randomIndex];
	logMessage(playlist[randomIndex]);
	playlist.splice(randomIndex, 1);
	if (playlist.length === 0) {
		resetPlaylist();
	}

    logMessage(`Lecture de la musique : ${playlist[randomIndex]}`);
    music.play().catch((error) => {
    	logForce("Erreur de lecture : ", error);
	});
};

// Fonction pour changer de musique
const playTrack = (index) => {
	music.src = playlist[index];
    music.play().catch((error) => {
    	logForce("Erreur de lecture : ", error);
	});
};

// Passer à une musique aléatoire après un délai
music.addEventListener('ended', () => {
	
	// Temps aléatoire entre min et max minutes
	const maxDelay = 6;
	const minDelay = 3;
	const delay = Math.floor(Math.random() * (maxDelay - minDelay + 1) + minDelay) * 60 * 1000; 

    logMessage(`Attente de ${delay / 60000} minutes avant de jouer le prochain morceau.`);
    setTimeout(() => { playRandomTrack(); }, delay);
});

// Gérer le bouton de pause/lecture
toggleButton.addEventListener('click', () => {
	console.log(music)
	if (music.paused) {
    	music.play();
        toggleButton.textContent = 'Stop Music';
	} else {
    	music.pause();
        toggleButton.textContent = 'Play Music';
	}
});


// Initialise la playlist avec toutes les soundtrack
loadPlaylist();