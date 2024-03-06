const {src, dest} = require('gulp');
const {createGulpEsbuild} = require('gulp-esbuild');
const concat = require('gulp-concat');
const plumber = require('gulp-plumber');
const terser = require('gulp-terser');
const sass = require('gulp-sass')(require('sass'));
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const sourcemaps = require('gulp-sourcemaps');
const livereload = require('gulp-livereload');
const log = require('fancy-log');
const packageImporter = require('node-sass-package-importer');

const getEsBuildOptions = (buildSourceMap = true) => {
    const esbuildOptions = {
        minify: false,
        target: 'es2015',
        bundle: false,
    };

    if (buildSourceMap) {
        esbuildOptions.sourcemap = 'inline';
    }

    return esbuildOptions;
};

const devEsBuild = () =>
    createGulpEsbuild({
        incremental: true,
        pipe       : true,
    });

const prodEsBuild = () =>
    createGulpEsbuild({
        pipe: true,
    });

/**
 * @param {string[]|string} sourceJsFiles
 * @param {string} outputLocation
 * @param {string} outputFileName
 * @param {boolean} isProd
 */
function getJsCompiler(sourceJsFiles, outputLocation, outputFileName, isProd) {
    return function compileJS() {
        let task = src(sourceJsFiles)
            .pipe(plumber())
            .pipe(sourcemaps.init().on('error', log.error))
            .pipe(concat(outputFileName))
            // Save inline the source map, the terser will remove it in production.
            .pipe(sourcemaps.write().on('error', log.error));

        if (isProd) {
            task = task.pipe(prodEsBuild()(getEsBuildOptions(false)));
        } else {
            task = task.pipe(devEsBuild()(getEsBuildOptions(true)));
        }

        if (isProd) {
            task = task.pipe(terser({toplevel: false}));
        } else {
            task = task.pipe(livereload());
        }

        task = task.pipe(dest(outputLocation));

        return task;
    };
}


function getPostCssPlugins(isProd = false) {
    // The postcss plugins will read from package.json for the browserslist.
    const postCssPlugins = [
        // Add auto-prefixer to remove outdated vendor prefixes.
        autoprefixer({remove: true}),
    ];

    if (isProd) {
        postCssPlugins.push(
            cssnano({
                preset: [
                    'default',
                    {
                        discardComments: {
                            removeAll: true,
                        },
                    },
                ],
            }),
        );
    }

    return postCssPlugins;
}

function getSCSSCompiler(sourceScssFiles, outputLocation, isProd = false) {
    return function compileCss() {
        let task = src(sourceScssFiles)
            .pipe(plumber());

        if (!isProd) {
            task = task.pipe(sourcemaps.init());
        }

        task = task.pipe(
                sass.sync({
                        outputStyle: isProd ? 'compressed' : 'expanded',
                        importer   : packageImporter(),
                    })
                    .on('error', sass.logError),
            )
            .pipe(postcss(getPostCssPlugins(isProd)));

        if (!isProd) {
            task = task.pipe(sourcemaps.write('./'));
        }

        task = task.pipe(dest(outputLocation));

        if (!isProd) {
            task = task.pipe(livereload());
        }

        return task;
    };
}

module.exports = {
    getJsCompiler,
    getSCSSCompiler,
};
