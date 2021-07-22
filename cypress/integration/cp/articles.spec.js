describe('Articles', () => {
    beforeEach(function() {
        cy.cpLogin()
    })

    it(`shows an error when trying to save an empty article`, function () {
        cy.cpVisit('/entries/articles/new')

        cy.get('#save-btn-container button[type=submit]')
            .click()

        cy.get('#notifications .notification.error')
            .contains('Couldn’t publish draft.')
    })
})
