// module exports
module.exports = {
  mode: 'jit',
  purge: {
    content: [
      './src/templates/**/*.{twig,html}',
      './src/vue/**/*.{vue,html}',
    ],
    layers: [
      'base',
      'components',
      'utilities',
    ],
    mode: 'layers',
    options: {
      whitelist: [
        './src/css/components/*.css',
        './src/css/elements/*.css',
        './src/css/libs/*.css',
        './src/css/utilities/*.css',
      ],
    }
  },
  theme: {
    container: {
      center: true,
      padding: '1.5rem',
    },
    screens: {
      sm: '640px',
      md: '768px',
      lg: '1024px',
      xl: '1280px',
      xxl: '1440px',
    },
    extend: {
      inset: {
        '1/2': '50%',
      },
      spacing: {
        '0.5': '0.125rem',
      },
      fontSize: {
        xxs: '.625rem'
      },
      transitionProperty: {
        height: 'height',
        position: 'top, right, bottom, left',
        spacing: 'margin, padding',
      }
    },
    listStyleType: {
      square: 'square',
    }
  },
  variants: {
    textColor: ['responsive', 'hover', 'focus', 'group-hover'],
    transitionProperty: ['responsive', 'hover', 'group-hover', 'focus'],
    translate: ['responsive', 'hover', 'focus', 'active', 'group-hover']
  },
  plugins: [],
};
