module.exports = {
    purge: {
        enabled: (process.env.NODE_ENV === 'production'),
        content: [
            "./templates/**/*.{twig,html}",
            "./src/js/**/*.{js}",
        ],
        options: {
            whitelist: []
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
            }
        },
    },
    variants: {
        transitionProperty: ['responsive', 'hover', 'group-hover', 'focus'],
        translate: ['responsive', 'hover', 'focus', 'active', 'group-hover']
    },
    plugins: [],
}