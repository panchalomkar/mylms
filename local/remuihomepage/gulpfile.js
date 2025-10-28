
// Gulp.
var gulp = require('gulp');
var exec = require('gulp-exec');
var notify = require("gulp-notify");
// Sass/CSS stuff.
var gulpSass = require('gulp-sass');
var dartSass = require('sass');
var concat = require('gulp-concat');
const sass = gulpSass(dartSass);

gulp.task('styles', function() {
    return gulp.src('scss/all.scss')
    .pipe(sass({
        outputStyle: 'compressed'
    }))
    .pipe(concat('styles.css'))
    .pipe(gulp.dest('.'));
});

gulp.task('purge', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/html/m40dev/admin/cli/purge_caches.php'))
    .pipe(notify('Purged All'));
}));

gulp.task('watch', function(done) {
    gulp.watch([
        './scss/**/*.scss',
        './scss/**/*.css'
    ], gulp.series('styles', 'purge'));
    gulp.watch([
        './amd/build/**.js'
    ], gulp.series('purge'));
    done();
});

gulp.task('default', gulp.series('styles', 'purge', 'watch'));
