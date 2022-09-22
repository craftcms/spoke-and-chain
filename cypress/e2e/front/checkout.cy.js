const sizes = require('../../viewport-sizes')

sizes.forEach((size) => {
    describe(`Checkout on ${size} screen`, () => {
        beforeEach(() => {
            cy.setViewportSize(size)

            // Define the user fixture
            cy.fixture('user').as('user')
        })

        it(`should add a product to the cart and checkout as guest`, function () {
            // Add a product to the cart
            cy.visit('/product/san-quentin-24')

            cy.get('#buy button[type=submit]')
                .click({scrollBehavior: 'center'});

            // Navigate to the cart
            cy.get('button.cart-toggle')
                .click();

            cy.get('div.cart-menu a.button.submit')
                .contains('Check Out')
                .click();

            // Checkout as guest
            cy.get('#guest-checkout button[type=submit]')
                .contains('Continue as Guest');

            cy.get('#guest-checkout input[type=text]')
                .type(this.user.email);

            cy.get('#guest-checkout button[type=submit]')
                .click();

            // Shipping address
            cy.get('form#checkout-address input[name="shippingAddress[firstName]"]')
                .type(this.user.address.firstName);
            cy.get('form#checkout-address input[name="shippingAddress[lastName]"]')
                .type(this.user.address.lastName);
            cy.get('form#checkout-address input[name="shippingAddress[addressLine1]"]')
                .type(this.user.address.addressLine1);
            cy.get('form#checkout-address input[name="shippingAddress[locality]"]')
                .type(this.user.address.locality);
            cy.get('form#checkout-address input[name="shippingAddress[postalCode]"]')
                .type(this.user.address.postalCode);
            cy.get('form#checkout-address select[name="shippingAddress[countryCode]"]')
                .select(this.user.address.countryCode);
            cy.get('form#checkout-address button[type=submit]')
                .click();

            // Use the default shipping method
            cy.get('form#checkout-shipping-method input[type=radio][value="freeShipping"]')
                .click();

            cy.get('form#checkout-shipping-method button[type=submit]')
                .click();

            // Fill credit card details and pay
            cy.get('form#checkout-payment input[name="paymentForm[dummy][firstName]"]')
                .type(this.user.card.firstName);
            cy.get('form#checkout-payment input[name="paymentForm[dummy][lastName]"]')
                .type(this.user.card.lastName);
            cy.get('form#checkout-payment input[name="paymentForm[dummy][number]"]')
                .type(this.user.card.number);
            cy.get('form#checkout-payment input[name="paymentForm[dummy][expiry]"]')
                .type(this.user.card.expiry);
            cy.get('form#checkout-payment input[name="paymentForm[dummy][cvv]"]')
                .type(this.user.card.cvv);
            cy.get('form#checkout-payment button[type=submit]')
                .click();

            // Success
            cy.get('h1')
                .contains('Success')
        })
    })
})
