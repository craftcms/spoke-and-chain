describe('Articles', () => {
    beforeEach(function() {
        cy.cpLogin()
    })

    it(`shows an error when trying to save an empty article`, function () {
        cy.cpVisit('/entries/articles/new')

        cy.get('#action-buttons button[type=submit]')
            .click()

        cy.get('#notifications .notification[data-type=error] .notification-message')
            .contains('Couldn’t create entry.')
    })
})
