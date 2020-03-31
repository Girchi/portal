const merge = require("webpack-merge");
const config = require("./webpack.common.js");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = merge(config, {
    mode: "production",
    module: {
        rules: [
            { test: /\.css$/, use: [MiniCssExtractPlugin.loader, "css-loader"] }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "react.bundle.css"
        })
    ]
});
