const { Encore } = require('@terminal42/contao-build-tools');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');

module.exports = Encore()
    .setOutputPath('public')
    .setPublicPath('/bundles/terminal42contaodamintegrator')
    .enableVueLoader(() => {}, {
        runtimeCompilerBuild: false
    })
    .addEntry('app', './view/js/app.js')
    .getWebpackConfig()
;