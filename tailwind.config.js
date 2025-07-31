const defaultTheme = require('tailwindcss/defaultTheme')
let plugin = require('tailwindcss/plugin')

module.exports = {
  content: [
    "./www/themes/tmp/layouts/*.htm",
    "./www/themes/tmp/partials/**/*.htm",
    "./www/themes/tmp/pages/**/*.htm",
    "./www/themes/tmp/content/**/*.htm",
    "./www/plugins/saybme/ub/views/**/*.htm",
    "./www/plugins/saybme/ub/components/**/*.htm"
  ],  
  theme: {
    screens: {
      'mob_s': '320px',
      'mob_m': '375px',
      'mob_l': '425px',
      'table_l': '600px', 
      'table_sm': '768px', 
      'table': '1024px', 
      'laptop': '1240px',  
      'laptop_l': '1440px'
    }, 
    container: {
      center: true,
      padding: {
        DEFAULT: '0.625rem',
        'table_l': '1.25rem'
      }
    },
    extend: {
      animation: {
        marquee: 'marquee 25s linear infinite',
      },
      keyframes: {
        marquee: {
          '0%': { transform: 'translateX(0%)' },
          '50%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(-100%)' },
        }
      },
      colors: {
        'blue-1': '#0B2F4F',
        'blue-2': '#285A7F',
        'blue-3': '#34688C',
        'blue-4': '#2E6186',
        'grey-1': '#BDBDBD',
        'grey-2': '#F7F7F7',
        'grey-3': '#BFBFBF',
        'red-1': '#EF3232',
        'red-2': '#B80707',
        'red-3': '#D3463B'
      },
      fontSize: {
        'h1': ['3.125rem', {lineHeight: '4.25rem', letterSpacing: '-0.049rem', fontWeight: '800'}],
        'section-h1': ['2.5rem', {lineHeight: '2.875rem', letterSpacing: '-0.039rem', fontWeight: '800'}],
        'txt-20': ['1.25rem', {lineHeight: 'normal', letterSpacing: '-0.049rem', fontWeight: '700'}],
        'txt-22': ['1.375rem', {lineHeight: '2.375rem', letterSpacing: '-0.039rem', fontWeight: '800'}],
        'txt-25': ['1.563rem', {lineHeight: '2.875rem', letterSpacing: '-0.039rem', fontWeight: '800'}],
        'txt-26': ['1.625rem', {lineHeight: '2rem', letterSpacing: '-0.025rem', fontWeight: '400'}],
        'txt-30': ['1.875rem', {lineHeight: 'normal', letterSpacing: '-0.025rem', fontWeight: '700'}],
        'txt-40': ['2.5rem', {lineHeight: '2.875rem', letterSpacing: '-0.039rem', fontWeight: '800'}],
        'txt-50': ['3.125rem', {lineHeight: '3.5rem', letterSpacing: '-0.049rem', fontWeight: '800'}],
        'txt-60': ['3.75rem', {lineHeight: '4.125rem', letterSpacing: '-0.059rem', fontWeight: '800'}],
        'txt-80': ['5rem', {lineHeight: 'normal', letterSpacing: '-0.098rem', fontWeight: '800'}],
        'txt-100': ['6.25rem', {lineHeight: '4.125rem', letterSpacing: '-0.098rem', fontWeight: '800'}]
      },
      fontFamily: {
        'gilroy-regular': ['Gilroy Regular', ...defaultTheme.fontFamily.sans],
        'gilroy-bold': ['Gilroy Extra Bold', ...defaultTheme.fontFamily.sans],
        'gilroy-medium': ['Gilroy Medium', ...defaultTheme.fontFamily.sans],
        'bebas': ['"Bebas Neue"', ...defaultTheme.fontFamily.sans],
        'manrope': ['"Manrope", sans-serif', ...defaultTheme.fontFamily.sans]
      },
      backgroundImage: {
        'sota-small': "url('../images/bg/small-sota.svg')",
        'ub-container': "url('../images/bg/ub-container.jpg')",
        'logo-container': "url('../images/bg/logo.png')",
      },
      dropShadow: {
        'ub-1': '0px 4px 4px 0px rgba(0, 0, 0, 0.25)',
      }
    },
  },
  corePlugins: {
    aspectRatio: false,
  },
  daisyui: {
    themes: ["light"],
  },
  plugins: [
    require('@tailwindcss/aspect-ratio'),
    require("daisyui"),
    require('tailwind-scrollbar-hide'),
    plugin(function ({ addVariant }) {
      addVariant('childs-a', '>&a')
    })
  ],
}

