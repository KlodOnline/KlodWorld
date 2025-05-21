
const music = document.getElementById('background-music');
const toggleButton = document.getElementById('toggle-music');


// Liste des musiques dans le répertoire "soundtrack"
const playlist = [
	'soundtrack/03-Echoes_of_the_ancients.mp3',
	'soundtrack/02-Eternal_Struggle.mp3',
	'soundtrack/01-Eternal_Valor.mp3'
];

let currentTrackIndex = 0;
music.volume = 0.25;

// Fonction pour jouer une musique aléatoire
const playRandomTrack = () => {
	const randomIndex = Math.floor(Math.random() * playlist.length);
	music.src = playlist[randomIndex];
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

// Lecture automatique au chargement
playRandomTrack();

// et on met en pause ...
music.pause();
toggleButton.textContent = 'Play Music';

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
	if (music.paused) {
    	music.play();
        toggleButton.textContent = 'Stop Music';
	} else {
    	music.pause();
        toggleButton.textContent = 'Play Music';
	}
});
