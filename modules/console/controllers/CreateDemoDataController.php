<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace modules\console\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\events\MailEvent;
use craft\commerce\models\Address;
use craft\commerce\models\Country;
use craft\commerce\models\Customer;
use craft\commerce\models\OrderStatus;
use craft\commerce\models\State;
use craft\commerce\Plugin;
use craft\commerce\records\Transaction;
use craft\commerce\services\Emails;
use craft\console\Controller;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\ElementException;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use DateInterval;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use yii\base\Event;

/**
 * Allows you to create Commerce demo data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class CreateDemoDataController extends Controller
{
    /**
     * Maximum number of carts to generate per day.
     */
    const CARTS_PER_DAY_MAX = 3;

    /**
     * Maximum number of customers to generate.
     */
    const CUSTOMERS_MAX = 50;

    /**
     * Minimum number of customers to generate.
     */
    const CUSTOMERS_MIN = 40;

    /**
     * Maximum number of orders to generate per day.
     */
    const ORDERS_PER_DAY_MAX = 3;

    /**
     * Maximum number of products per order/cart.
     */
    const PRODUCTS_PER_ORDER_MAX = 3;

    /**
     * The start date of when orders should begin generating.
     */
    const START_DATE_INTERVAL = 'P40D';

    /**
     * Maximum number of users to generate.
     */
    const USERS_MAX = 30;

    /**
     * Minimum number of users to generate.
     */
    const USERS_MIN = 20;

    /**
     * Minimum number of reviews per product.
     */
    const REVIEWS_PER_PRODUCT_MIN = 0;

    /**
     * Maximum number of reviews per product.
     */
    const REVIEWS_PER_PRODUCT_MAX = 20;

    /**
     * @var Generator|null
     */
    private $_faker;

    /**
     * @var array
     */
    private $_users = [];

    /**
     * @var array
     */
    private $_guestCustomers = [];

    /**
     * @var array
     */
    private $_countries = [];

    /**
     * @var Product[]|array
     */
    private $_products = [];

    /**
     * @var OrderStatus[]|array
     */
    private $_orderStatuses = [];

    /**
     * @var DateTime|null
     */
    private $_startDate;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        // Don't let order status emails send while this is running
        Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
            $event->isValid = false;
        });

        $startDate = new DateTime();
        $interval = new DateInterval(self::START_DATE_INTERVAL);
        $this->_startDate = $startDate->sub($interval);

        $this->_faker = Factory::create();
        $this->_countries = Plugin::getInstance()->getCountries()->getAllEnabledCountries();
        $this->_products = Craft::$app->getElements()->createElementQuery(Product::class)->all();
        $this->_orderStatuses = Plugin::getInstance()->getOrderStatuses()->getAllOrderStatuses();
    }

    /**
     * Generate demo data.
     *
     * @throws ElementException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex(): void
    {
        $this->stdout('Creating demo data' . PHP_EOL, Console::FG_GREEN);
        $this->_createUsers();

        $this->_createGuestCustomers();

        $this->_createOrders();

        $this->_createReviews();
        $this->stdout('Finished creating demo data' . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Create demo users.
     *
     * @throws ElementException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createUsers()
    {
        for ($i = 0; $i < random_int(self::USERS_MIN, self::USERS_MAX); $i++) {
            $firstName = $this->_faker->firstName();
            $lastName = $this->_faker->lastName;
            $email = $this->_faker->unique()->email;

            $attributes = [
                'email' => $email,
                'username' => $email,
                'fullName' => $firstName . ' ' . $lastName,
            ];

            /** @var User $user */
            $user = Craft::createObject([
                'class' => User::class,
                'attributes' => $attributes,
            ]);

            if (!Craft::$app->getElements()->saveElement($user)) {
                // If a user cannot be saved, simply skip over it and carry on.
                continue;
            }

            $customer = Plugin::getInstance()->getCustomers()->getCustomerByUserId($user->id);
            $attributes['customerId'] = $customer->id;

            $attributes['addresses'] = [];
            for ($j = 0; $j < random_int(1, 3); $j++) {
                $attributes['addresses'][] = $this->_createAddress($firstName, $lastName, $customer);
            }

            $this->_users[] = array_merge($attributes, [
                'id' => $user->id,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]);

            $this->stdout('Creating user: ' . $attributes['fullName'] . PHP_EOL, Console::FG_PURPLE);
        }
    }

    /**
     * Create guest customer data.
     *
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createGuestCustomers()
    {
        for ($i = 0; $i < random_int(self::CUSTOMERS_MIN, self::CUSTOMERS_MAX); $i++) {
            $customer = Craft::createObject(['class' => Customer::class]);
            Plugin::getInstance()->getCustomers()->saveCustomer($customer);

            $attributes = [
                'customerId' => $customer->id,
                'email' => $this->_faker->email,
                'firstName' => $this->_faker->firstName(),
                'lastName' => $this->_faker->lastName,
                'addresses' => [],
            ];

            for ($j = 0; $j <= random_int(1, 3); $j++) {
                $address = $this->_createAddress($attributes['firstName'], $attributes['lastName'], $customer);

                $attributes['addresses'][] = $address;
            }

            $this->_guestCustomers[] = $attributes;
            $this->stdout('Creating guest customer: ' . $attributes['firstName'] . ' ' . $attributes['lastName'] . PHP_EOL, Console::FG_RED);
        }
    }

    /**
     * Create and save address data.
     *
     * @param $firstName
     * @param $lastName
     * @param $customer
     * @return Address
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createAddress($firstName, $lastName, $customer)
    {
        $country = $this->_getRandomCountry();

        /** @var Address $address */
        $address = Craft::createObject([
            'class' => Address::class,
            'attributes' => [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'address1' => $this->_faker->streetAddress,
                'city' => $this->_faker->city,
                'zipCode' => $this->_faker->postcode,
                'countryId' => $country->id,
            ]
        ]);

        /** @var State $state */
        if ($country->isStateRequired && $state = $this->_getRandomStateFromCountry($country)) {
            $address->setStateValue($state->id);
        }

        Plugin::getInstance()->getCustomers()->saveAddress($address, $customer, false);

        return $address;
    }


    /**
     * @return Country
     * @throws \Exception
     */
    private function _getRandomCountry(): Country
    {
        return $this->_faker->randomElement($this->_countries);
    }

    /**
     * @param Country $country
     * @return mixed|null
     * @throws \Exception
     */
    private function _getRandomStateFromCountry(Country $country)
    {
        $states = $country->getStates();
        if (empty($states)) {
            return null;
        }

        return $this->_faker->randomElement($states);
    }

    /**
     * Create demo orders and carts.
     *
     * @throws \Exception
     */
    private function _createOrders()
    {
        $date = new DateTime();
        while ($date->format('Y-m-d') >= $this->_startDate->format('Y-m-d')) {
            // Carts
            $this->stdout('Creating carts for: ' . $date->format('Y-m-d') . PHP_EOL, Console::FG_YELLOW);
            for ($i = 1; $i <= random_int(1, self::CARTS_PER_DAY_MAX); $i++) {
                $date = $this->_setTime($date);
                $this->_createOrderElement($date, false);
            }

            // Orders
            $this->stdout('Creating orders for: ' . $date->format('Y-m-d') . PHP_EOL, Console::FG_YELLOW);
            for ($j = 1; $j <= random_int(1, self::ORDERS_PER_DAY_MAX); $j++) {
                $date = $this->_setTime($date);
                $this->_createOrderElement($date);
            }

            $date->sub(new DateInterval('P1D'));
        }
    }

    /**
     * Set random time on a DateTime object.
     *
     * @param DateTime $date
     * @return DateTime
     * @throws \Exception
     */
    private function _setTime(DateTime $date): DateTime
    {
        if (DateTimeHelper::isToday($date)) {
            $date->setTime(random_int(0, $date->format('G')), random_int(0, $date->format('i')), 0);
        } else {
            $date->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));
        }

        return $date;
    }

    /**
     * Return a random customer from those imported.
     *
     * @param bool $isUser
     * @return mixed
     */
    private function _getRandomCustomer(bool $isUser)
    {
        return $this->_faker->randomElement($isUser ? $this->_users : $this->_guestCustomers);
    }

    /**
     * Return a random address from an imported customer.
     *
     * @param $customer
     * @return Address
     */
    private function _getRandomAddressFromCustomer($customer): Address
    {
        return $this->_faker->randomElement($customer['addresses']);
    }

    /**
     * Return a random product.
     *
     * @return Product
     */
    private function _getRandomProduct(): Product
    {
        return $this->_faker->randomElement($this->_products);
    }

    /**
     * Return a random order status.
     *
     * @return OrderStatus
     */
    private function _getRandomOrderStatus(): OrderStatus
    {
        return $this->_faker->randomElement($this->_orderStatuses);
    }

    /**
     * Create and save an order element.
     *
     * @param DateTime $date
     * @param bool $isCompleted
     * @return void
     * @throws \Throwable
     * @throws \craft\commerce\errors\OrderStatusException
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createOrderElement(DateTime $date, bool $isCompleted = true)
    {
        $customer = $this->_getRandomCustomer(random_int(0, 1));

        $attributes = [
            'billingAddressId' => $this->_getRandomAddressFromCustomer($customer)->id,
            'shippingAddressId' => $this->_getRandomAddressFromCustomer($customer)->id,
            'dateUpdated' => $date,
            'dateCreated' => $date,
            'email' => $customer['email'],
        ];

        /** @var Order $order */
        $order = Craft::createObject([
            'class' => Order::class,
            'attributes' => $attributes
        ]);

        $order->customerId = $customer['customerId'];
        $order->number = Plugin::getInstance()->getCarts()->generateCartNumber();

        Craft::$app->getElements()->saveElement($order);

        $lineItems = [];
        $numProducts = random_int(1, self::PRODUCTS_PER_ORDER_MAX);
        for ($i = 1; $i <= $numProducts; $i++) {
            $product = $this->_getRandomProduct();
            // Weight the qty in favour of 1 item
            $qty = random_int(0, 9) < 8 ? 1 : 2;
            $lineItems[] = Plugin::getInstance()->getLineItems()->createLineItem($order->id, $product->getDefaultVariant()->id, [], $qty);
        }

        $order->setLineItems($lineItems);

        Craft::$app->getElements()->saveElement($order);

        if ($isCompleted) {
            // Get everything completed before messing with the order status
            $order->markAsComplete();

            $order->orderStatusId = $this->_getRandomOrderStatus()->id;
            $order->dateOrdered = $date;
            Craft::$app->getElements()->saveElement($order);

            $this->_createTransactionForOrder($order);
        }
    }

    /**
     * Create and save a transaction for and order element.
     *
     * @param Order $order
     * @throws \craft\commerce\errors\TransactionException
     */
    private function _createTransactionForOrder(Order $order)
    {
        if ($order->isCompleted) {
            $transaction = Plugin::getInstance()->getTransactions()->createTransaction($order, null);
            $transaction->type = Transaction::TYPE_PURCHASE;
            $transaction->status = Transaction::STATUS_SUCCESS;

            Plugin::getInstance()->getTransactions()->saveTransaction($transaction);
        }
    }

    private function _createReviews()
    {
        $reviewsSection = Craft::$app->getSections()->getSectionByHandle('reviews');
        /** @var UserQuery $authorQuery */
        $authorQuery = Craft::$app->getElements()->createElementQuery(User::class);
        $author = $authorQuery->admin(true)->orderBy('id ASC')->one();

        if (!$reviewsSection || !$author) {
            return;
        }

        $startDateInterval = new DateInterval(self::START_DATE_INTERVAL);

        foreach ($this->_products as $product) {
            for ($i = 0; $i <= random_int(self::REVIEWS_PER_PRODUCT_MIN, self::REVIEWS_PER_PRODUCT_MAX); $i++) {
                $reviewDate = new DateTime();
                $reviewDate->sub(new DateInterval('P' . random_int(0, $startDateInterval->days) . 'D'));
                $reviewDate = $this->_setTime($reviewDate);

                /** @var Entry $review */
                $review = Craft::createObject([
                    'class' => Entry::class,
                    'attributes' => [
                        'authorId' => $author->id,
                        'postDate' => $reviewDate,
                    ]
                ]);

                $review->sectionId = $reviewsSection->id;
                $review->typeId = $reviewsSection->getEntryTypes()[0]->id;
                $review->title = $this->_faker->randomLetter . '. ' . $this->_faker->lastName;

                $paragraphs = $this->_faker->paragraphs(random_int(0, 3));
                $stars = $this->_faker->optional(0.2, 5)->numberBetween(1, 5);
                $review->setFieldValues([
                    'body' => '<p>' . implode('</p><p>', $paragraphs) . '</p>',
                    'product' => [$product->id],
                    'stars' => $stars,
                ]);

                $this->stdout('Creating ' . $stars . ' star review for ' . $product->title. PHP_EOL, Console::FG_BLUE);
                Craft::$app->getElements()->saveElement($review);
            }
        }
    }
}