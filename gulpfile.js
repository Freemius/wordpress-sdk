require('dotenv').config();
const {parallel, watch} = require('gulp');
const livereload = require('gulp-livereload');
const {createTranslation} = require('./gulptasks/translate');
const {getSdkScssCompiler, scssSources} = require('./gulptasks/sass');
const {getSdkJSCompilers, jsSources} = require('./gulptasks/scripts');

const DEFAULT_GULP_WATCH_OPTIONS = {ignoreInitial: false, usePolling: true};

/**
 * Tasks related to translations of the SDK.
 * This will
 * 1. Create `languages/freemius.pot` file.
 * 2. Upload it to Transifex.
 * 3. Download latest translations from Transifex.
 * 4. Build `languages/freemius-xx_XX.po` and `languages/freemius-xx_XX.mo` files.
 */
exports.translate = createTranslation;

/**
 * The build task. This will build
 * 1. SASS files.
 * 2. JS files.
 */
exports.build = parallel(
    getSdkScssCompiler(true),
    ...getSdkJSCompilers(true)
);

exports.dev = function () {
    livereload.listen();

    watch(scssSources, DEFAULT_GULP_WATCH_OPTIONS, getSdkScssCompiler(false));

    watch(Object.values(jsSources), DEFAULT_GULP_WATCH_OPTIONS, parallel(...getSdkJSCompilers(false)));
}