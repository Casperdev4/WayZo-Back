const plugin = require('tw-elements/plugin');

module.exports = {
  content: [
    './templates/**/*.html.twig',
    './assets/**/*.js',
    './node_modules/tw-elements/dist/js/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [plugin],
};







