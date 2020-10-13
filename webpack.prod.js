// webpack.prod.js - production builds

// node modules
const glob = require('glob-all');
const {merge} = require('webpack-merge');
const moment = require('moment');
const path = require('path');
const webpack = require('webpack');

// webpack plugins
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const CreateSymlinkPlugin = require('create-symlink-webpack-plugin');
const ImageminWebpWebpackPlugin = require('imagemin-webp-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

// config files
const common = require('./webpack.common.js');
const pkg = require('./package.json');
const settings = require('./webpack.settings.js');

// Configure file banner
const configureBanner = () => {
    return {
        banner: [
            '/*!',
            ' * @project        ' + settings.name,
            ' * @name           ' + '[filebase]',
            ' * @author         ' + pkg.author.name,
            ' * @build          ' + moment().format('llll') + ' ' + Intl.DateTimeFormat().resolvedOptions().timeZone,
            ' * @copyright      Copyright (c) ' + moment().format('YYYY') + ' ' + settings.copyright,
            ' */',
            ''
        ].join('\n'),
        raw: true
    };
};

// Configure Clean webpack
const configureCleanWebpack = () => {
    return {
        root: path.resolve(__dirname, settings.paths.dist),
        verbose: true,
        dry: false
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
        },
            {
                loader: 'img-loader',
                options: {
                    plugins: [
                        require('imagemin-gifsicle')({
                            interlaced: true,
                        }),
                        require('imagemin-mozjpeg')({
                            progressive: true,
                            arithmetic: false,
                        }),
                        require('imagemin-optipng')({
                            optimizationLevel: 5,
                        }),
                        require('imagemin-svgo')({
                            plugins: [{
                                convertPathData: false
                            }, ]
                        }),
                    ]
                }
            }
        ]
    };
};

// Configure optimization
const configureOptimization = () => {
    return {
        minimizer: [
            new TerserPlugin(
                configureTerser()
            ),
            new OptimizeCSSAssetsPlugin({
                cssProcessorOptions: {
                    map: {
                        inline: false,
                        annotation: true,
                    },
                    safe: true,
                    discardComments: true
                },
            })
        ]
    };
};

// Configure Postcss loader
const configurePostcssLoader = () => {
    return {
        test: /\.(pcss|css)$/,
        use: [
            MiniCssExtractPlugin.loader,
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

// Configure terser
const configureTerser = () => {
    return {
        cache: true,
        parallel: true,
        sourceMap: true
    };
};

// Production module exports
module.exports = [
    merge(
        common.webpackConfig, {
            output: {
                filename: path.join('./js', '[name].[chunkhash].js'),
            },
            stats: {warnings:false},
            mode: 'production',
            devtool: 'source-map',
            optimization: configureOptimization(),
            module: {
                rules: [
                    configurePostcssLoader(),
                    configureImageLoader(),
                ],
            },
            plugins: [
                new CleanWebpackPlugin(
                    configureCleanWebpack()
                ),
                new MiniCssExtractPlugin({
                    path: path.resolve(__dirname, settings.paths.dist),
                    filename: path.join('./css', '[name].[chunkhash].css'),
                }),
                new webpack.BannerPlugin(
                    configureBanner()
                ),
                new CreateSymlinkPlugin(
                    settings.createSymlinkConfig,
                    true
                ),
                new webpack.optimize.ModuleConcatenationPlugin(),
                new ImageminWebpWebpackPlugin(),
            ]
        }
    ),
];