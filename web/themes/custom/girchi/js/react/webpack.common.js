const path = require("path");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const webpack = require("webpack");
const dotenv = require("dotenv");

// call dotenv and it will return an Object with a parsed key
const env = dotenv.config().parsed;

// reduce it to a nice object, the same as before
const envKeys = Object.keys(env).reduce((prev, next) => {
    prev[`${next}`] = JSON.stringify(env[next]);
    return prev;
}, {});

module.exports = {
    entry: "./app/index.js",
    output: {
        path: path.resolve(__dirname, "dist"),
        filename: "react.bundle.js"
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: [
                            "@babel/preset-react",
                            [
                                "@babel/preset-env",
                                {
                                    modules: false,
                                    loose: false
                                }
                            ]
                        ]
                    }
                }
            }
        ]
    },
    plugins: [
        new CleanWebpackPlugin(),
        new webpack.DefinePlugin({
            "process.env": envKeys
        })
    ]
};
