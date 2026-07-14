const path = require("path");
const Dotenv = require("dotenv-webpack");
const TerserPlugin = require("terser-webpack-plugin");

module.exports = {
  mode: "production",
  entry: {
    index: "./www/html/js/index.js",
    diagnostics: "./www/html/diagnostics/js/index.js",
    plot: "./www/html/js/plot.js",
  },
  output: {
    path: path.resolve(__dirname, "www"),
    filename: (pathData) => {
      const name = pathData.chunk.name;
      if (name === "index") {
        return "html/js/index.min.js";
      } else if (name === "diagnostics") {
        return "html/diagnostics/js/index.min.js";
      } else if (name === "plot") {
        return "html/js/plot.min.js";
      }
      console.error("Error: Missing output filepath for this entry file");
    },
  },
  plugins: [new Dotenv({
    systemvars: true
  })],
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          sourceMap: false,
          keep_fnames: true,
          toplevel: true,
          compress: {
            drop_console: true,
            unused: false,
          },
          mangle: {
            // Specify functions that should not be mangled:
            reserved: ["init_map"],
          },
          output: {
            comments: false,
          },
        },
      }),
    ],
  },
};
