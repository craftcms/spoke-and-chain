const {defineConfig} = require('cypress');
const {lighthouse, pa11y, prepareAudit} = require("cypress-audit");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://spokeandchain.ddev.site/',
    scrollBehavior: 'nearest',
    setupNodeEvents(on, config) {
      on('before:browser:launch', (browser = {}, launchOptions) => {
        // `args` is an array of all the arguments that will
        // be passed to browsers when it launches
        prepareAudit(launchOptions);

        if (browser.family === 'chromium' && browser.name !== 'electron') {
          // auto open devtools
          launchOptions.args.push('--ignore-certificate-errors')

          // whatever you return here becomes the launchOptions
          return launchOptions
        }
      })

      on("task", {
        lighthouse: lighthouse(), // calling the function is important
        pa11y: pa11y(), // calling the function is important
      });
    }
  },
  env: {
    'CP_TRIGGER': 'admin',
    'CP_LOGIN': 'admin@craftcms.com',
    'CP_PASSWORD': '__replace__',
    'ENABLE_LIGHTHOUSE': true,
    'LIGHTHOUSE_OPTIONS': {
      'performance': 0,
      'accessibility': 90,
      'best-practices': 0,
      'seo': 0,
      'pwa': 0
    },
    'ENABLE_PA11Y': false,
    'PA11Y_OPTIONS': {
      'runners': ['htmlcs'],
      'standard': 'WCAG2AA'
    }
  }
});
