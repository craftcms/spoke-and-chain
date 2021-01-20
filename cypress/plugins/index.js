/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
const { lighthouse, pa11y, prepareAudit } = require("cypress-audit");

module.exports = (on, config) => {
    config.baseUrl = config.env.SITE_URL

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

    return config
}
