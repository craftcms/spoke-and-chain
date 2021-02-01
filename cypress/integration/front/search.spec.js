const searchQuery = 'rift zone'
describe('Search', () => {
    it(`should show search results`, function () {
        cy.visit('/')

        cy.get('#header button.search-toggle')
            .click()

        cy.get('input#search-input')
            .type(searchQuery)

        cy.get('form#search-form').submit()

        cy.get('h1').contains(`Search results for “${searchQuery}”`)

        cy.get('a.product-card')
            .its('length')
            .should('be.gt', 0)
    })
})
