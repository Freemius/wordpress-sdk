const gulp       = require('gulp');
const path       = require('path');
const wpPot      = require('gulp-wp-pot');
const gettext    = require('gulp-gettext');
const sort       = require('gulp-sort');
const pofill     = require('gulp-pofill');
const rename     = require('gulp-rename');
const clean      = require('gulp-clean');
const postcss    = require( 'gulp-postcss' );
const sass       = require('gulp-sass')(require('sass'));
const cssnano    = require( 'cssnano' );
const filesystem = require('fs');
const { series } = require('gulp');

function getFolders(dir) {
    return filesystem.readdirSync(dir)
        .filter(function (file) {
            return filesystem.statSync(path.join(dir, file)).isDirectory();
        });
}

const options         = require('./transifex-config.json');
const transifex       = require('gulp-transifex').createClient(options);
const languagesFolder = './languages/';
const folders         = getFolders(languagesFolder);

// Create POT out of PHP files
function prepare_source_pot() {
    return gulp.src('**/*.php')
        .pipe(sort())
        .pipe(wpPot({
            destFile        : 'freemius.pot',
            package         : 'freemius',
            bugReport       : 'https://github.com/Freemius/wordpress-sdk/issues',
            lastTranslator  : 'Vova Feldman <vova@freemius.com>',
            team            : 'Freemius Team <admin@freemius.com>',

            gettextFunctions: [
                {name: 'get_text_inline'},

                {name: 'fs_text_inline'},
                {name: 'fs_echo_inline'},
                {name: 'fs_esc_js_inline'},
                {name: 'fs_esc_attr_inline'},
                {name: 'fs_esc_attr_echo_inline'},
                {name: 'fs_esc_html_inline'},
                {name: 'fs_esc_html_echo_inline'},

                {name: 'get_text_x_inline', context: 2},
                {name: 'fs_text_x_inline', context: 2},
                {name: 'fs_echo_x_inline', context: 2},
                {name: 'fs_esc_attr_x_inline', context: 2},
                {name: 'fs_esc_js_x_inline', context: 2},
                {name: 'fs_esc_js_echo_x_inline', context: 2},
                {name: 'fs_esc_html_x_inline', context: 2},
                {name: 'fs_esc_html_echo_x_inline', context: 2}
            ]
        }))
        .pipe(gulp.dest(languagesFolder + 'freemius.pot'));
}

// Create English PO out of the POT.
function generate_source_po() {
    return gulp.src(languagesFolder + 'freemius.pot')
        .pipe(pofill({
            items: function (item) {
                // If msgstr is empty, use identity translation
                if (!item.msgstr.length) {
                    item.msgstr = [''];
                }
                if (!item.msgstr[0]) {
                    item.msgstr[0] = item.msgid;
                }
                return item;
            }
        }))
        .pipe(rename('freemius-en.po'))
        .pipe(gulp.dest(languagesFolder));
}

// Push updated po resource to transifex.
function push_transifex() {
    return gulp.src(languagesFolder + 'freemius-en.po')
        .pipe(transifex.pushResource());
}

// Download latest *.po translations.
function download_latest_po() {
    return gulp.src(languagesFolder + 'freemius-en.po')
    .pipe(transifex.pullResource());
}

function move_translations_to_root() {    
    return folders.map(function (folder) {
        return gulp.src(path.join(languagesFolder, folder, 'freemius-en.po'))
            .pipe(rename('freemius-' + folder + '.po'))
            .pipe(gulp.dest(languagesFolder));
    });
}

// Fill up empty translations with English.
function translations_english_fillup() {
    return gulp.src(languagesFolder + '*.po')
        .pipe(pofill({
            items: function (item) {
                // If msgstr is empty, use identity translation
                if (0 == item.msgstr.length) {
                    item.msgstr = [''];
                }
                if (0 == item.msgstr[0].length) {
                    // item.msgid[0] = item.msgid;
                    item.msgstr[0] = item.msgid;
                }
                return item;
            }
        }))
        .pipe(gulp.dest(languagesFolder));
}

// Cleanup temporary translation folders.
function cleanup_temp() {
    return folders.map(function (folder) {
        return gulp.src(path.join(languagesFolder, folder), {read: false})
            .pipe(clean());
    });
}

// Compile *.po to *.mo binaries for usage.
function compile_translations_to_mo() {
    return gulp.src(languagesFolder + '*.po')
        .pipe(gettext())
        .pipe(gulp.dest(languagesFolder))
}

// Run postcss processing for styles.
function style() {
    const plugins = [
        cssnano()
    ];

    return gulp.src( './assets/scss/**/*.scss' )
        .pipe( sass() )
        .pipe( postcss( plugins ) )
        .pipe( gulp.dest( './assets/css/' ) )
}

// Compile css only in dev mode.
function watch() {
    gulp.watch( './assets/scss/**/*.scss', style );
}

const update_transifex      = series(prepare_source_pot, generate_source_po, push_transifex);
const download_translations = series(update_transifex, download_latest_po);
const prepare_translations  = series(download_translations, move_translations_to_root);
const translations_fillup   = series(prepare_translations, translations_english_fillup);
const cleanup               = series(prepare_translations, cleanup_temp);
const compile_translations  = series(translations_fillup, compile_translations_to_mo);

exports.prepare_source_pot    = prepare_source_pot;
exports.update_transifex      = update_transifex;
exports.download_translations = download_translations;
exports.prepare_translations  = prepare_translations;
exports.translations_fillup   = translations_fillup;
exports.cleanup               = cleanup;
exports.cleanup_temp          = cleanup_temp;
exports.compile_translations  = compile_translations;
exports.style                 = style;
exports.watch                 = watch;
exports.default               = series(translations_fillup, cleanup, compile_translations);