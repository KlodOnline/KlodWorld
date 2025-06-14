/*==============================================================================
    HELPERS
==============================================================================*/

class Helpers {
    constructor() {
        this.log = this.log.bind(this);
    }

    log(txt, who = '') {
        const prefix = who !== '' ? `${who}: ` : '';
        const now = new Date();
        const date = now.toLocaleDateString('fr-FR');
        const time = now.toTimeString().split(' ')[0];
        console.log(`${date} [${time}] ${prefix}${txt}`);
    }

    safelyParseJSON(json) {
        try {
            return JSON.parse(json);
        } catch (e) {
            this.log('Bad JSON Object !', 'HELPERS');
            // console.log(json);
            return {};
        }
    }
}

module.exports = new Helpers();
