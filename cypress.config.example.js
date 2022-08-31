const { lighthouse, pa11y, prepareAudit } = require("cypress-audit");

module.exports = {
  e2e: {
    baseUrl: 'https://spokeandchain.nitro/',
    scrollBehavior: 'nearest',
    setupNodeEvents(on, config) {
            
          on("before:browser:launch", (browser = {}, launchOptions) => {
            prepareAudit(launchOptions);
          });

          on("task", {
            lighthouse: lighthouse(),
            pa11y: pa11y(),
          }); 

    }    
  },
  env: {
    "CP_TRIGGER": "admin",
    "CP_LOGIN": "support@craftcms.com",
    "CP_PASSWORD": "NewPassword",
    "ENABLE_LIGHTHOUSE": true,
    "LIGHTHOUSE_OPTIONS": {
      "performance": 0,
      "accessibility": 90,
      "best-practices": 0,
      "seo": 0,
      "pwa": 0
    },
    "ENABLE_PA11Y": false,
    "PA11Y_OPTIONS": {
      "runners": ["htmlcs"],
      "standard": "WCAG2AA"
    }
  }  
};
