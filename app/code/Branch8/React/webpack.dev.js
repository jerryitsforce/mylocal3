// const path = require("path");
const webpack = require("webpack");
const path = require("path");
const { merge } = require("webpack-merge");
const { commonConfig, publicPaths } = require("./webpack.common.js");
const ForkTsCheckerWebpackPlugin = require("fork-ts-checker-webpack-plugin");
const HtmlWebpackPlugin = require("html-webpack-plugin");
// const BundleAnalyzerPlugin = require("webpack-bundle-analyzer").BundleAnalyzerPlugin;

const PORT = 1234;

const devConfig = merge(commonConfig, {
    mode: "development",
    devtool: "inline-source-map",
    output: {
        publicPath: publicPaths.DEV,
    },
    plugins: [
        new HtmlWebpackPlugin({
            title: "Search Storefront Autocomplete",
            template: __dirname + "/public/index.html",
            inject: "body",
            filename: "index.html",
        }),
        new ForkTsCheckerWebpackPlugin(),
    ],
    devServer: {
        compress: true,
        // host: '',
        port: PORT,
        static: {
          directory: path.join(__dirname, 'dist'),
        },
        headers: {
          'Access-Control-Allow-Origin': '*',
          'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
          'Access-Control-Allow-Headers':
            'X-Requested-With, content-type, Authorization',
        },
        open: publicPaths.DEV,
        allowedHosts: ['all'],
        watchFiles: ['src/**/*', 'public/**/*', 'dist/**/*'],
        hot: true,
        liveReload: false,
        host: '0.0.0.0',
        client: {
          webSocketURL: `ws://localhost:${PORT}/ws`,
        },
      },
});

module.exports = env => {
    return devConfig;
};
