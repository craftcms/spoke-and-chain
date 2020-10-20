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
        screens: {
            sm: '640px',
            md: '768px',
            lg: '1024px',
            xl: '1280px',
            xxl: '1440px',
        },
        extend: {},
    },
    variants: {},
    plugins: [],
}