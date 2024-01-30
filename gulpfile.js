require('dotenv').config();
const {createTranslation} = require('./gulptasks/translate');

exports.translate = createTranslation;
