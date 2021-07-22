describe('Cart', () => {
    it("should pass the audits", function () {
        cy.visit('/cart')
        cy.runAudit()
    })
})
