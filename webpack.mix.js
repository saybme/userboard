const mix = require('laravel-mix');

require('mix-tailwindcss');

mix.options({
    processCssUrls: false,
})

mix
mix.sass('./src/css/app.scss', './www/themes/tmp/assets/css')
.js('./src/js/app.js', './www/themes/tmp/assets/js').sourceMaps()
.postCss('./src/css/main.css', './www/themes/tmp/assets/css',[
    require('tailwindcss'),
]);