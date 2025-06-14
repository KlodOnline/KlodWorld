/*==============================================================================
    HELPERS
==============================================================================*/

const jwt = require('jsonwebtoken');

class Helpers {
    constructor() {
        this.log = this.log.bind(this);
        this.verifyToken = this.verifyToken.bind(this);
    }

    log(txt, who = '') {
        const prefix = who !== '' ? `${who}: ` : '';
        const now = new Date();
        const date = now.toLocaleDateString('fr-FR');
        const time = now.toTimeString().split(' ')[0];
        console.log(`${date} [${time}] ${prefix}${txt}`);
    }

    verifyToken(token, secret) {
        try {
            return jwt.verify(token, secret);
        } catch (err) {
            this.log(`Token invalid: ${err.message}`, 'HELPERS');
            throw new Error('Invalid token');
        }
    }
}

module.exports = new Helpers();
