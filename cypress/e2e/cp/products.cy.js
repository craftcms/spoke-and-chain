describe('Products', () => {
    beforeEach(function() {
        cy.cpLogin()
    })

    it(`shows an error when trying to save an empty product`, function () {
        cy.cpVisit('/commerce/products/bike/new')

        cy.get('#save-btn-container button[type=submit]')
            .click()

        cy.get('#notifications .notification[data-type=error] .notification-message')
            .contains('Couldn’t save product.')
    })
})
