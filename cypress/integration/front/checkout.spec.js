const sizes = require('../../viewport-sizes')

describe('Checkout', () => {
    beforeEach(() => {
        // Define the user fixture
        cy.fixture('user').as('user')
    })

    sizes.forEach((size) => {
        it(`should add a product to the cart and checkout as guest on ${size} screen`, function () {
            // Set the viewport
            cy.setViewportSize(size)

            // Add a product to the cart
            cy.visit('/product/san-quentin-24')

            cy.get('#buy input[type=submit]')
                .click();

            // Navigate to the cart
            cy.get('button.cart-toggle')
                .click();

            cy.get('div.cart-menu a')
                .contains('Cart')
                .click();

            // Checkout as guest
            cy.get('a.button.submit')
                .contains('Checkout')
                .click();

            cy.get('form#guest-checkout input[type=text]')
                .type(this.user.email);

            cy.get('form#guest-checkout input[type=submit]')
                .click();

            // Shipping address
            cy.get('form#checkout-address input[name="shippingAddress[firstName]"]')
                .type(this.user.address.firstName);
            cy.get('form#checkout-address input[name="shippingAddress[lastName]"]')
                .type(this.user.address.lastName);
            cy.get('form#checkout-address input[name="shippingAddress[address1]"]')
                .type(this.user.address.address1);
            cy.get('form#checkout-address input[name="shippingAddress[city]"]')
                .type(this.user.address.city);
            cy.get('form#checkout-address input[name="shippingAddress[zipCode]"]')
                .type(this.user.address.zipCode);
            cy.get('form#checkout-address select[name="shippingAddress[countryId]"]')
                .select(this.user.address.countryId);
            cy.get('form#checkout-address input[type=submit]')
                .click();

            // Use the default shipping method
            cy.get('form#checkout-shipping-method input[type=submit]')
                .click();

            // Fill credit card details and pay
            cy.get('form#checkout-payment input[name="firstName"]')
                .type(this.user.card.firstName);
            cy.get('form#checkout-payment input[name="lastName"]')
                .type(this.user.card.lastName);
            cy.get('form#checkout-payment input[name="number"]')
                .type(this.user.card.number);
            cy.get('form#checkout-payment input[name="expiry"]')
                .type(this.user.card.expiry);
            cy.get('form#checkout-payment input[name="cvv"]')
                .type(this.user.card.cvv);
            cy.get('form#checkout-payment input[type=submit]')
                .click();

            // Success
            cy.get('h1')
                .contains('Success')

        })
    })

    // it("should add a product to the cart and checkout as logged-in user", function () {
    // })

    // responsive tests
})
