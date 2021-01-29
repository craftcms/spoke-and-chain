describe('Bikes', () => {
    it("should pass the audits", function () {
        cy.visit('/bikes')
        cy.runAudit()
    })
})
