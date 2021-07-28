// webpack.dev.js - developmental builds

// node modules
const {merge} = require('webpack-merge');
const path = require('path');
const sane = require('sane');
const webpack = require('webpack');

// webpack plugins
const DashboardPlugin = require('webpack-dashboard/plugin');

// config files
const common = require('./webpack.common.js');
const pkg = require('./package.json');
const settings = require('./webpack.settings.js');

// Configure the webpack-dev-server
const configureDevServer = () => {
    return {
        public: settings.devServerConfig.public(),
        contentBase: path.resolve(__dirname, settings.paths.dist),
        host: settings.devServerConfig.host(),
        port: settings.devServerConfig.port(),
        https: !!parseInt(settings.devServerConfig.https()),
        quiet: true,
        hot: true,
        // hotOnly: true,
        allowedHosts: [
            'host.docker.internal',
            '.test',
            '.nitro',
        ],
        overlay: true,
        stats: 'errors-only',
        watchOptions: {
            poll: !!parseInt(settings.devServerConfig.poll()),
            ignored: /node_modules/,
        },
        headers: {
            'Access-Control-Allow-Origin': '*'
        },
        // Use sane to monitor all of the templates files and sub-directories
        before: (app, server) => {
            const watcher = sane(path.join(__dirname, settings.paths.templates), {
                glob: ['**/*'],
                poll: !!parseInt(settings.devServerConfig.poll()),
            });
            watcher.on('change', function (filePath, root, stat) {
                console.log('  File modified:', filePath);
                server.sockWrite(server.sockets, "content-changed");
            });
        },
    };
};

// Configure Image loader
const configureImageLoader = () => {
    return {
        test: /\.(png|jpe?g|gif|svg|webp)$/i,
        use: [{
            loader: 'file-loader',
            options: {
                name: 'img/[name].[hash].[ext]'
            }
        }]
    };
};

// Configure the Postcss loader
const configurePostcssLoader = () => {
    return {
        test: /\.(pcss|css)$/,
        use: [
            {
                loader: 'style-loader',
                options: { injectType: "singletonStyleTag" },
            },
            {
                loader: 'css-loader',
                options: {
                    importLoaders: 2,
                    sourceMap: true
                }
            },
            {
                loader: 'resolve-url-loader'
            },
            {
                loader: 'postcss-loader',
                options: {
                    sourceMap: true
                }
            }
        ]
    };
};

// Development module exports
module.exports = [
    merge(
        common.webpackConfig, {
            output: {
                filename: path.join('./js', '[name].[hash].js'),
                publicPath: settings.devServerConfig.public() + '/',
            },
            mode: 'development',
            devtool: 'inline-source-map',
            devServer: configureDevServer(),
            module: {
                rules: [
                    configurePostcssLoader(),
                    configureImageLoader(),
                ],
            },
            plugins: [
                new webpack.HotModuleReplacementPlugin(),
                new DashboardPlugin(),
            ],
        }
    ),
];