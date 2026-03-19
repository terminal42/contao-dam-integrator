const { Encore } = require('@terminal42/contao-build-tools');

module.exports = Encore()
    .setOutputPath('public')
    .setPublicPath('/bundles/terminal42contaodamintegrator')
    .enableVueLoader(() => {}, {
        runtimeCompilerBuild: false
    })
    .addEntry('app', './assets/js/app.js')
    .getWebpackConfig()
;
