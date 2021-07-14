<?php

namespace modules\demos\console\controllers;

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
use craft\helpers\Db;
use DateInterval;
use DateTime;
use Faker\Generator as FakerGenerator;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use yii\base\Event;
use yii\console\ExitCode;

class SeedController extends Controller
{
    public const FREEFORM_SUBMISSION_MIN = 100;
    public const FREEFORM_SUBMISSION_MAX = 200;
    public const FREEFORM_MESSAGE_CHARS_MIN = 120;
    public const FREEFORM_MESSAGE_CHARS_MAX = 300;

    /**
     * Maximum number of carts to generate per day.
     */
    public const CARTS_PER_DAY_MAX = 3;

    /**
     * Maximum number of customers to generate.
     */
    public const CUSTOMERS_MAX = 50;

    /**
     * Minimum number of customers to generate.
     */
    public const CUSTOMERS_MIN = 40;

    /**
     * Maximum number of orders to generate per day.
     */
    public const ORDERS_PER_DAY_MAX = 3;

    /**
     * Maximum number of products per order/cart.
     */
    public const PRODUCTS_PER_ORDER_MAX = 3;

    /**
     * The start date of when orders should begin generating.
     */
    public const START_DATE_INTERVAL = 'P40D';

    /**
     * Maximum number of users to generate.
     */
    public const USERS_MAX = 30;

    /**
     * Minimum number of users to generate.
     */
    public const USERS_MIN = 20;

    /**
     * Minimum number of reviews per product.
     */
    public const REVIEWS_PER_PRODUCT_MIN = 0;

    /**
     * Maximum number of reviews per product.
     */
    public const REVIEWS_PER_PRODUCT_MAX = 20;

    /**
     * @var string|null
     */
    public ?string $email = null;

    /**
     * @var string|null
     */
    public string $username = 'admin';

    /**
     * @var string|null
     */
    public ?string $password = null;

    /**
     * @var int Duration in seconds to wait between retries
     */
    public int $timeout = 2;

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
     * @var FakerGenerator
     */
    private FakerGenerator $_faker;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        // Don't let order status emails send while this is running
        Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
            $event->isValid = false;
        });

        $startDate = new DateTime();
        $interval = new DateInterval(self::START_DATE_INTERVAL);
        $this->_startDate = $startDate->sub($interval);

        $this->_faker = \Faker\Factory::create();
    }

    /**
     * {@inheritdoc}
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'index':
            case 'admin-user':
                $options[] = 'email';
                $options[] = 'username';
                $options[] = 'password';
                break;
            case 'wait-for-db':
                $options[] = 'timeout';
                break;
        }

        return $options;
    }

    /**
     * Seeds all data necessary for a working demo
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->runAction('admin-user');
        $this->runAction('freeform-data', ['contact']);
        $this->runAction('refresh-articles');
        $this->runAction('commerce-data');

        Craft::$app->projectConfig->set('system.live', true, null, false);

        return ExitCode::OK;
    }

    /**
     * Creates an admin user
     *
     * @return int
     */
    public function actionAdminUser(): int
    {
        $this->stdout('Creating admin user ... ');

        $user = new User([
            'username' => $this->username,
            'newPassword' => $this->password,
            'email' => $this->email,
            'admin' => true,
        ]);

        if (!Craft::$app->getElements()->saveElement($user)) {
            $this->stderr('failed:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $user->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Seeds Freeform with submission data
     *
     * @param string $formHandle Freeform form handle
     * @return int
     */
    public function actionFreeformData(string $formHandle): int
    {
        $this->stdout('Seeding Freeform data ... ' . PHP_EOL);

        $freeform = Freeform::getInstance();
        $form = $freeform->forms->getFormByHandle($formHandle)->getForm();
        $submissionCount = $this->_faker->numberBetween(self::FREEFORM_SUBMISSION_MIN, self::FREEFORM_SUBMISSION_MAX);
        $errorCount = 0;

        for ($i = 1; $i <= $submissionCount; $i++) {
            try {
                $submission = $this->_createFormSubmission($form);
                $this->stdout("    - [{$i}/{$submissionCount}] Creating submission {$submission->title} ... ");

                if ($this->_saveFormSubmission($submission)) {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                } else {
                    $this->stderr('failed: ' . implode(', ', $submission->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $errorCount++;
                }
            } catch (\Throwable $e) {
                $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                $errorCount++;
            }
        }

        $this->stdout('Done seeding Freeform data.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return $errorCount ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    public function actionRefreshArticles(): int
    {
        $this->stdout('Refreshing articles ... ');
        $entries = Entry::find()->section('articles');

        foreach ($entries->all() as $entry) {
            $entry->postDate = $this->_faker->dateTimeInInterval('-1 months', '-5 days');
            Craft::$app->elements->saveElement($entry);
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * @param int $maxTime Maximum time in seconds to wait for database connection
     * @return int
     */
    public function actionWaitForDb(int $maxTime = 0): int
    {
        $this->stdout('Waiting for database ... ' . PHP_EOL);
        $retries = 0;
        $startTime = time();

        while (!Craft::$app->getIsDbConnectionValid()) {
            if ($maxTime && (time() - $startTime) > $maxTime) {
                $this->stderr("Database connection failed: maximum time of $maxTime seconds reached." . PHP_EOL . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $retries++;
            $this->stdout("    - [{$retries}] Retrying in $this->timeout seconds..." . PHP_EOL, Console::FG_YELLOW);
            sleep($this->timeout);
        }

        $totalTime = time() - $startTime;
        $this->stdout("Database connection successful ($totalTime seconds)." . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    public function actionCommerceData(): int
    {
        $this->stdout('Seeding Commerce data ... ' . PHP_EOL);
        $this->_createUsers();
        $this->_createGuestCustomers();
        $this->_createOrders();
        $this->_createReviews();
        $this->stdout('Done seeding Commerce data.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    private function _createFormSubmission(Form $form): Submission
    {
        /** @var Submission $submission */
        $submission = Freeform::getInstance()->submissions->createSubmissionFromForm($form);
        $submission->dateCreated = $submission->dateUpdated = $this->_faker->dateTimeThisMonth();

        // Reparse the title with the fake date
        $submission->title = Craft::$app->view->renderString(
            $form->getSubmissionTitleFormat(),
            $form->getLayout()->getFieldsByHandle() + [
                'dateCreated' => $submission->dateCreated,
                'form' => $form,
            ]
        );

        $submission->setFormFieldValues([
            'email' => $this->_faker->email,
            'firstName' => $this->_faker->firstName,
            'lastName' => $this->_faker->lastName,
            'message' => $this->_faker->realTextBetween(self::FREEFORM_MESSAGE_CHARS_MIN, self::FREEFORM_MESSAGE_CHARS_MAX),
        ]);

        return $submission;
    }

    private function _saveFormSubmission(Submission $submission): bool
    {
        if (!Craft::$app->getElements()->saveElement($submission)) {
            return false;
        }

        // Update submissions table to match date, so element index will sort properly
        $dateCreatedDb = Db::prepareDateForDb($submission->dateCreated);

        Craft::$app->db->createCommand()
            ->update($submission::TABLE, [
                'dateCreated' => $dateCreatedDb,
                'dateUpdated' => $dateCreatedDb,
            ], [
                'id' => $submission->id,
            ])
            ->execute();

        return true;
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
        $this->stdout('Creating users ... ' . PHP_EOL);
        $numUsers = random_int(self::USERS_MIN, self::USERS_MAX);
        for ($i = 1; $i <= $numUsers; $i++) {
            $firstName = $this->_faker->firstName();
            $lastName = $this->_faker->lastName;
            $email = $this->_faker->unique()->email;

            $attributes = [
                'email' => $email,
                'username' => $email,
                'fullName' => $firstName . ' ' . $lastName,
            ];
            $this->stdout("    - [{$i}/{$numUsers}] Creating user " . $attributes['fullName'] . ' ... ');

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
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Done creating users' . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Create guest customer data.
     *
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createGuestCustomers()
    {
        $this->stdout('Creating customers...' . PHP_EOL);
        $numCustomers = random_int(self::CUSTOMERS_MIN, self::CUSTOMERS_MAX);
        for ($i = 0; $i <= $numCustomers; $i++) {
            /** @var Customer $customer */
            $customer = Craft::createObject(['class' => Customer::class]);
            Plugin::getInstance()->getCustomers()->saveCustomer($customer);

            $attributes = [
                'customerId' => $customer->id,
                'email' => $this->_faker->email,
                'firstName' => $this->_faker->firstName(),
                'lastName' => $this->_faker->lastName,
                'addresses' => [],
            ];

            $this->stdout("    - [{$i}/{$numCustomers}] Creating guest customer " . $attributes['firstName'] . ' ' . $attributes['lastName'] . ' ... ');
            for ($j = 0; $j <= random_int(1, 3); $j++) {
                $address = $this->_createAddress($attributes['firstName'], $attributes['lastName'], $customer);

                $attributes['addresses'][] = $address;
            }

            $this->_guestCustomers[] = $attributes;
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Done creating customers' . PHP_EOL, Console::FG_GREEN);
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
        if (empty($this->_countries)) {
            $this->_countries = Plugin::getInstance()->getCountries()->getAllEnabledCountries();
        }

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
        $this->stdout('Creating orders...' . PHP_EOL);
        $date = new DateTime();
        while ($date->format('Y-m-d') >= $this->_startDate->format('Y-m-d')) {
            // Carts
            $this->stdout('    - [' . $date->format('Y-m-d') . '] Creating carts ... ');
            for ($i = 1; $i <= random_int(1, self::CARTS_PER_DAY_MAX); $i++) {
                $date = $this->_setTime($date);
                $this->_createOrderElement($date, false);
            }
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

            // Orders
            $this->stdout('    - [' . $date->format('Y-m-d') . '] Creating orders ... ');
            for ($j = 1; $j <= random_int(1, self::ORDERS_PER_DAY_MAX); $j++) {
                $date = $this->_setTime($date);
                $this->_createOrderElement($date);
            }

            $date->sub(new DateInterval('P1D'));
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Done creating orders' . PHP_EOL, Console::FG_GREEN);
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
        if (empty($this->_products)) {
            $this->_products = Craft::$app->getElements()->createElementQuery(Product::class)->all();
        }

        return $this->_faker->randomElement($this->_products);
    }

    /**
     * Return a random order status.
     *
     * @return OrderStatus
     */
    private function _getRandomOrderStatus(): OrderStatus
    {
        if (empty($this->_orderStatuses)) {
            $this->_orderStatuses = Plugin::getInstance()->getOrderStatuses()->getAllOrderStatuses();
        }

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

    /**
     * Create product review data
     *
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
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

        $this->stdout('Creating reviews ... ' . PHP_EOL);
        $index = 1;
        $numProducts = count($this->_products);
        foreach ($this->_products as $product) {
            $this->stdout("    - [{$index}/{$numProducts}] Creating reviews for " . $product->title . ' ... ');
            for ($i = 0; $i <= random_int(self::REVIEWS_PER_PRODUCT_MIN, self::REVIEWS_PER_PRODUCT_MAX); $i++) {
                $reviewDate = new DateTime();
                $reviewDate->sub(new DateInterval('P' . random_int(0, $startDateInterval->days) . 'D'));
                $reviewDate = $this->_setTime($reviewDate);

                /** @var Entry $review */
                $review = Craft::createObject([
                    'class' => Entry::class,
                ]);

                $review->authorId = $author->id;
                $review->postDate = $reviewDate;
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

                Craft::$app->getElements()->saveElement($review);
            }

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            $index++;
        }

        $this->stdout('Done creating reviews' . PHP_EOL, Console::FG_GREEN);
    }
}
