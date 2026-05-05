const path = require("path");
const { merge } = require("webpack-merge");
const { commonConfig, publicPaths } = require("./webpack.common.js");
const CopyWebpackPlugin = require('copy-webpack-plugin');
const LiveReloadPlugin = require('webpack-livereload-plugin');

const devConfig = merge(commonConfig, {
  plugins: [
    new LiveReloadPlugin(),
    // new CopyWebpackPlugin({
    //   patterns: [
    //     {
    //       from: path.join(__dirname, "/view/base/web/js/"),
    //       to: "../../../../../../../../pub/static/frontend/Branch8/hotai/en_US/Branch8_React/js/",
    //       force: true,
    //       globOptions: {
    //         ignore: ["**/*.txt"],
    //       },
    //     },
    //   ],
    // }),
  ]
});

module.exports = env => {
    return devConfig;
};
