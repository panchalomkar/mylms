/* jshint node:true */
'use strict';
module.exports = function(grunt) {
    grunt.initConfig({
        uglify: {
            dist: {
                files: [{
                    expand: true, cwd: 'amd/src',
                    src: ['*.js'], dest: 'amd/build', ext: '.min.js'
                }]
            }
        },
        jshint: {
            options: { esversion: 6, browser: true, strict: true },
            src: ['amd/src/**/*.js']
        },
        watch: {
            amd: { files: ['amd/src/**/*.js'], tasks: ['jshint','uglify'] }
        }
    });
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['jshint','uglify']);
};
