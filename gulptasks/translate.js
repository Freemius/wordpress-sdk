const {src, dest, series} = require('gulp');
const sort = require('gulp-sort');
const wpPot = require('gulp-wp-pot');
const path = require('node:path');
const log = require('fancy-log');
const fs = require('node:fs');
const {po, mo} = require('gettext-parser');
const chalk = require('chalk');
const {uploadEnglishPoToTransifex, getTranslation} = require('./transifex');

const root = path.resolve(__dirname, '..');
const languagesFolder = path.resolve(root, './languages');
const freemiusPotPath = path.resolve(languagesFolder, './freemius.pot');

const LANGUAGE_CODES = [
    'cs_CZ',
    'da_DK',
    'de_DE',
    'es_ES',
    'fr_FR',
    'he_IL',
    'hu_HU',
    'it_IT',
    'ja',
    'nl_NL',
    'ru_RU',
    'ta',
    'zh_CN',
];

function translatePHP() {
    return src('**/*.php')
        .pipe(sort())
        .pipe(wpPot({
            destFile      : 'freemius.pot',
            package       : 'freemius',
            bugReport     : 'https://github.com/Freemius/wordpress-sdk/issues',
            lastTranslator: 'Vova Feldman <vova@freemius.com>',
            team          : 'Freemius Team <admin@freemius.com>',

            gettextFunctions: [
                // #region Methods from the Freemius class.
                {name: 'get_text_inline'},
                {name: 'get_text_x_inline', context: 2},

                {name: '$this->get_text_inline'},
                {name: '$this->get_text_x_inline', context: 2},

                {name: '$this->_fs->get_text_inline'},
                {name: '$this->_fs->get_text_x_inline', context: 2},

                {name: '$this->fs->get_text_inline'},
                {name: '$this->fs->get_text_x_inline', context: 2},

                {name: '$fs->get_text_inline'},
                {name: '$fs->get_text_x_inline', context: 2},

                {name: '$this->_parent->get_text_inline'},
                {name: '$this->_parent->get_text_x_inline', context: 2},

                // #endregion

                {name: 'fs_text_inline'},
                {name: 'fs_echo_inline'},
                {name: 'fs_esc_js_inline'},
                {name: 'fs_esc_attr_inline'},
                {name: 'fs_esc_attr_echo_inline'},
                {name: 'fs_esc_html_inline'},
                {name: 'fs_esc_html_echo_inline'},

                {name: 'fs_text_x_inline', context: 2},
                {name: 'fs_echo_x_inline', context: 2},
                {name: 'fs_esc_attr_x_inline', context: 2},
                {name: 'fs_esc_js_x_inline', context: 2},
                {name: 'fs_esc_js_echo_x_inline', context: 2},
                {name: 'fs_esc_html_x_inline', context: 2},
                {name: 'fs_esc_html_echo_x_inline', context: 2},
            ],
        }))
        .pipe(dest(freemiusPotPath));
}

function updateTranslationFiles(languageCode, translation) {
    const fileName = `freemius-${languageCode}`;
    // Do a parsing, just to be sure that the translation is valid.
    const parsedPo = po.parse(translation);

    const poFilePath = path.resolve(languagesFolder, `./${fileName}.po`);
    fs.writeFileSync(poFilePath, po.compile(parsedPo));

    const moFilePath = path.resolve(languagesFolder, `./${fileName}.mo`);
    fs.writeFileSync(moFilePath, mo.compile(parsedPo));
}

async function syncWithTransifex() {
    log('Updaing Transifex source...');
    const resource = await uploadEnglishPoToTransifex(freemiusPotPath);

    if (false === resource) {
        log.error('Failed to upload the English po file to Transifex.');
        return;
    }

    log('Transifex updated.');

    // Loop over LANGUAGE_CODE and download and update every one of them
    for (const code of LANGUAGE_CODES) {
        log(`Updating ${chalk.cyan(code)}...`);
        try {
            const translation = await getTranslation(code, resource);

            // Update the po file in the file system
            updateTranslationFiles(code, translation);

            log(`Updated ${chalk.cyan(code)}.`);
        } catch (e) {
            log.error(`Failed to get translation of ${chalk.red(code)}, skipping...`);
        }
    }
}

exports.createTranslation = series(translatePHP, syncWithTransifex);

exports.createPot = translatePHP;
