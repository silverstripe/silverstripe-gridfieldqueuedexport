const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const config = [
  // Main JS bundle
  new JavascriptWebpackConfig('js', PATHS, 'silverstripe/gridfieldqueuedexport')
    .setEntry({
      GridFieldQueuedExportButton: `${PATHS.SRC}/js/GridFieldQueuedExportButton.js`,
    })
    .getConfig(),
  // sass to css
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      GridFieldQueuedExportButton: `${PATHS.SRC}/styles/GridFieldQueuedExportButton.scss`,
    })
    .getConfig(),
];

module.exports = config;
