// webpack.common.js - common webpack config

// node modules
const path = require('path');
const {merge} = require('webpack-merge');

// webpack plugins
const ManifestPlugin = require('webpack-manifest-plugin');
const WebpackNotifierPlugin = require('webpack-notifier');

// config files
const pkg = require('./package.json');
const settings = require('./webpack.settings.js');

// Configure Babel loader
const configureBabelLoader = () => {
    return {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
            loader: 'babel-loader',
            options: {
                plugins: [
                    '@babel/plugin-syntax-dynamic-import',
                    [
                        "@babel/plugin-transform-runtime",
                        { "regenerator": true }
                    ]
                ],
            },
        },
    };
};

// Configure Entries
const configureEntries = () => {
    let entries = {};
    for (const [key, value] of Object.entries(settings.entries)) {
        entries[key] = path.resolve(__dirname, settings.paths.src.js + value);
    }

    return entries;
};

// Configure Font loader
const configureFontLoader = () => {
    return {
        test: /\.(ttf|eot|woff2?)$/i,
        use: [
            {
                loader: 'file-loader',
                options: {
                    name: 'fonts/[name].[ext]'
                }
            }
        ]
    };
};

// Configure Manifest
const configureManifest = (fileName) => {
    return {
        fileName: fileName,
        basePath: settings.manifestConfig.basePath,
        map: (file) => {
            file.name = file.name.replace(/(\.[a-f0-9]{32})(\..*)$/, '$2');
            return file;
        },
    };
};


// The base webpack config
const webpackConfig = {
    name: pkg.name,
    entry: configureEntries(),
    output: {
        path: path.resolve(__dirname, settings.paths.dist),
        publicPath: settings.urls.publicPath
    },
    resolve: {
        alias: {
        }
    },
    module: {
        rules: [
            configureBabelLoader(),
            configureFontLoader(),
        ],
    },
    plugins: [
        new ManifestPlugin(
            configureManifest('manifest.json')
        ),
        new WebpackNotifierPlugin({title: 'Webpack', excludeWarnings: true, alwaysNotify: true}),
    ]
};

// Common module exports
// noinspection WebpackConfigHighlighting
module.exports = {
    webpackConfig,
};