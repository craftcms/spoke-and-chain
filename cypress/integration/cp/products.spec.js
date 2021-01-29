describe('Products', () => {
    beforeEach(function() {
        cy.login()
    })

    it(`shows an error when trying to save an empty product`, function () {
        cy.cpVisit('/commerce/products/bike/new')

        cy.get('#main-form').submit()

        cy.get('#notifications .notification.error').contains('Couldn’t save product.')
    })
})
