const gulp       = require('gulp');
const postcss    = require('gulp-postcss');
const sass       = require('gulp-sass')(require('sass'));
const wpPot      = require('gulp-wp-pot');
const minify     = require('gulp-minify');
var autoprefixer = require('autoprefixer');
const cssnano    = require('cssnano');
const { series } = require('gulp');

// Dev build of files watching scss, js and php for translations. 
function watch() {
  gulp.watch('./assets/src/scss/**/*.scss', style);
  gulp.watch('./assets/src/js/**/*.js', js);
}

// Compile Minified css. 
function style() {
  var plugins = [
    cssnano(),
    autoprefixer(),
  ];

  return gulp.src('./assets/src/scss/**/*.scss')
    .pipe(sass())
    .pipe(postcss(plugins))
    .pipe(gulp.dest('./assets/css'))
}

// Compile Minified JS. 
function js() {
  return gulp.src('./assets/src/js/**/*.js')
    .pipe(
      minify(
        {
          noSource: true,
          ext: {
            min: '.js'
          }
        }
      )
    )
    .pipe(gulp.dest('./assets/js'));
}

exports.style  = style;
exports.watch  = watch;
exports.js     = js;
exports.build  = series(style, js);