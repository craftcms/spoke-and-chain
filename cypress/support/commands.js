// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
import 'cypress-audit/commands'

Cypress.Commands.add("cpVisit", (url, options) => {
    cy.visit('/' + Cypress.env('CP_TRIGGER') + url, options)
})

Cypress.Commands.add("cpLogin", (loginName, password) => {
    if (!loginName) {
        loginName = Cypress.env('CP_LOGIN')
    }

    if (!password) {
        password = Cypress.env('CP_PASSWORD')
    }

    cy.cpVisit('/')

    cy.get('input[name=username]').type(loginName);
    cy.get('input[name=password]').type(password);
    cy.get('#login-form').submit();
    cy.cpVisit('/dashboard')
})

Cypress.Commands.add("login", (loginName, password) => {
    if (!loginName) {
        loginName = Cypress.env('CP_LOGIN')
    }

    if (!password) {
        password = Cypress.env('CP_PASSWORD')
    }

    cy.visit('/account/login')

    cy.get('input[name=loginName]').type(loginName);
    cy.get('input[name=password]').type(password);
    cy.get('#login-form').submit();
    cy.visit('/')
})

Cypress.Commands.add("runAudit", () => {
    if(Cypress.env('ENABLE_LIGHTHOUSE')) {
        cy.lighthouse(Cypress.env('LIGHTHOUSE_OPTIONS'))
    }

    if(Cypress.env('ENABLE_PA11Y')) {
        cy.pa11y(Cypress.env('PA11Y_OPTIONS'));
    }
})

Cypress.Commands.add("setViewportSize", (size) => {
    if (Cypress._.isArray(size)) {
        cy.viewport(size[0], size[1])
    } else {
        cy.viewport(size)
    }
})

Cypress.Commands.add("addProductToCart", () => {
    cy.visit('/bikes')

    // Add a product to the cart
    cy.get('a.product-card').first()
        .click()

    cy.get('#buy button[type=submit]')
        .click({scrollBehavior: 'center'});
})

Cypress.Commands.add("navigateToCart", () => {
    cy.visit('/cart');
})
