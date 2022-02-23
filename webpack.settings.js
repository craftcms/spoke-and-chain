// webpack.settings.js - webpack settings config

// node modules
require('dotenv').config();

// Webpack settings exports
// noinspection WebpackConfigHighlighting
module.exports = {
    name: "Spoke & Chain",
    copyright: "Pixel & Tonic",
    paths: {
        src: {
            base: "./src/",
            css: "./src/css/",
            js: "./src/js/"
        },
        dist: "./web/assets/dist/",
        templates: "./templates/"
    },
    urls: {
        live: process.env.LIVE_SITE_URL || "http://example.com",
        local: process.env.PRIMARY_SITE_URL || "http://local.craft.test/",
        publicPath: process.env.PUBLIC_PATH || "/assets/dist/"
    },
    vars: {
        cssName: "styles"
    },
    entries: {
        "app": "app.js"
    },
    devServerConfig: {
        public: () => process.env.DEVSERVER_PUBLIC || "http://localhost:8080",
        host: () => process.env.DEVSERVER_HOST || "localhost",
        poll: () => process.env.DEVSERVER_POLL || false,
        port: () => process.env.DEVSERVER_PORT || 8080,
        https: () => process.env.DEVSERVER_HTTPS || false,
    },
    manifestConfig: {
        basePath: ""
    },
    createSymlinkConfig: [
        // {
        //     origin: "img/favicons/favicon.ico",
        //     symlink: "./favicon.ico"
        // }
    ],
};