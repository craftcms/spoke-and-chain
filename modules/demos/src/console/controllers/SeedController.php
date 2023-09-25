<?php

namespace modules\demos\console\controllers;

use CommerceGuys\Addressing\Subdivision\Subdivision;
use Craft;
use craft\commerce\elements\db\ProductQuery;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\events\MailEvent;
use craft\commerce\models\OrderStatus;
use craft\commerce\models\Store;
use craft\commerce\Plugin;
use craft\commerce\records\Transaction;
use craft\commerce\services\Emails;
use craft\console\Controller;
use craft\elements\Address;
use craft\elements\db\ElementQuery;
use craft\elements\db\UserQuery;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\ElementException;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use DateInterval;
use DateTime;
use Faker\Generator as FakerGenerator;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
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
     * Percentage of customers to give VIP status.
     */
    public const VIP_CUSTOMER_PERCENT = 10;

    /**
     * Group handle for “Customers”.
     */
    public const CUSTOMER_GROUP_HANDLE = 'customers';

    /**
     * Group handle for “VIP Customers”.
     */
    public const VIP_CUSTOMER_GROUP_HANDLE = 'vipCustomers';

    /**
     * Minimum number of reviews per product.
     */
    public const REVIEWS_PER_PRODUCT_MIN = 0;

    /**
     * Maximum number of reviews per product.
     */
    public const REVIEWS_PER_PRODUCT_MAX = 20;

    /**
     * @var array
     */
    private array $_users = [];

    /**
     * @var array
     */
    private array $_guestCustomers = [];

    /**
     * @var array
     */
    private array $_countries = [];

    /**
     * @var Product[]|array
     */
    private array $_products = [];

    /**
     * @var OrderStatus[]|array
     */
    private array $_orderStatuses = [];

    /**
     * @var DateTime|null
     */
    private ?DateTime $_startDate;

    /**
     * @var Store|null
     */
    private ?Store $_store = null;

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

        if (!Craft::$app->isInstalled) {
            return;
        }

        // Don’t let order status emails send while this is running
        Event::on(
            Emails::class,
            Emails::EVENT_BEFORE_SEND_MAIL,
            function(MailEvent $event) {
                $event->isValid = false;
            }
        );

        $startDate = new DateTime();
        $interval = new DateInterval(self::START_DATE_INTERVAL);
        $this->_startDate = $startDate->sub($interval);

        $this->_faker = \Faker\Factory::create();
        $this->_store = Plugin::getInstance()->getStore()->getStore();
    }

    /**
     * Seeds all data necessary for a working demo
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout('Beginning seed ... ' . PHP_EOL . PHP_EOL);
        $this->runAction('freeform-data', ['contact']);
        $this->runAction('refresh-articles');
        $this->runAction('commerce-data');
        $this->_cleanup();
        $this->stdout('Seed complete.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    public function actionClean(): int
    {
        /** @var SubmissionQuery $submissions */
        $submissions = Submission::find();
        $submissions->isSpam(null);
        $this->deleteElements($submissions, 'submissions');
        $this->runAction('delete-commerce-data');
        return ExitCode::OK;
    }

    private function _cleanup()
    {
        $this->stdout('Running queue ... ' . PHP_EOL);
        Craft::$app->queue->run();
        $this->stdout('Queue finished.' . PHP_EOL, Console::FG_GREEN);

        $this->stdout('Clearing data cache ... ');
        Craft::$app->getCache()->flush();
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        $compiledClassesPath = Craft::$app->getPath()->getCompiledClassesPath();

        $this->stdout('Clearing compiled classes ... ');
        FileHelper::removeDirectory($compiledClassesPath);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        $this->stdout('Setting system status to online ... ');
        Craft::$app->projectConfig->set('system.live', true, null, false);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
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
            Craft::$app->elements->saveElement(element: $entry);
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    public function actionCommerceData(): int
    {
        $this->stdout('Seeding Commerce data ... ' . PHP_EOL . PHP_EOL);
        $this->_createUsers();
        $this->_createGuestCustomers();
        $this->_createOrders();
        $this->_createReviews();
        $this->stdout('Done seeding Commerce data.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    public function actionDeleteCommerceData(): int
    {
        $this->deleteElements(Order::find(), 'orders');
        $this->deleteElements(User::find()->status(User::STATUS_INACTIVE), 'customers');
        $this->deleteElements(Entry::find()->section('reviews'), 'reviews');

        Plugin::getInstance()->getCarts()->purgeIncompleteCarts();

        return ExitCode::OK;
    }

    private function deleteElements(ElementQuery $query, string $label = 'elements'): void
    {
        $count = $query->count();
        $errorCount = 0;
        $this->stdout("Deleting $label ..." . PHP_EOL);

        foreach ($query->all() as $element) {
            $i = isset($i) ? $i + 1 : 1;
            $this->stdout("    - [{$i}/{$count}] Deleting element {$element->title} ... ");
            try {
                $success = Craft::$app->getElements()->deleteElement($element, true);
                if ($success) {
                    $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
                } else {
                    $this->stderr('failed: ' . implode(', ', $element->getErrorSummary(true)) . PHP_EOL, Console::FG_RED);
                    $errorCount++;
                }
            } catch (\Throwable $e) {
                $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                $errorCount++;
            }
        }
        $message = "Done deleting $label.";
        if ($errorCount) {
            $message .= " ($errorCount errors)";
        }
        $this->stdout($message . PHP_EOL . PHP_EOL, Console::FG_GREEN);
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
        $customerGroup = Craft::$app->getUserGroups()->getGroupByHandle(self::CUSTOMER_GROUP_HANDLE);
        $vipCustomerGroup = Craft::$app->getUserGroups()->getGroupByHandle(self::VIP_CUSTOMER_GROUP_HANDLE);

        $this->stdout('Creating users ... ' . PHP_EOL);
        $numUsers = random_int(self::USERS_MIN, self::USERS_MAX);
        for ($i = 1; $i <= $numUsers; $i++) {
            $firstName = $this->_faker->firstName();
            $lastName = $this->_faker->lastName;
            $email = $this->_faker->unique()->email;
            // Assign everybody to “Customers” group
            $groups = [$customerGroup];

            // Should we also add this user to the “VIP Customers” group?
            if ($vipCustomerGroup && $i <= (ceil($numUsers * (self::VIP_CUSTOMER_PERCENT / 100)))) {
                $groups[] = $vipCustomerGroup;
            }

            $groupIds = array_map(static function($group) {
                return $group->id;
            }, $groups);

            $attributes = [
                'email' => $email,
                'username' => $email,
                'firstName' => $firstName,
                'lastName' => $lastName,
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

            $user->setGroups($groups);
            Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);

            $addresses = [];
            for ($j = 0; $j < random_int(1, 3); $j++) {
                $addresses[] = $this->_createAddress($firstName, $lastName, $user);
            }

            Craft::$app->getUsers()->activateUser($user);

            $this->_users[] = $user->toArray() + compact('addresses');
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
            $customer = Craft::$app->getUsers()->ensureUserByEmail($this->_faker->email);

            $customer->firstName = $this->_faker->firstName();
            $customer->lastName = $this->_faker->lastName;

            Craft::$app->getElements()->saveElement($customer, false);

            $this->stdout("    - [{$i}/{$numCustomers}] Creating guest customer " . $customer->firstName . ' ' . $customer->lastName . ' ... ');
            $addresses = [];
            for ($j = 0; $j <= random_int(1, 3); $j++) {
                $address = $this->_createAddress($customer->firstName, $customer->lastName, $customer);

                $addresses[] = $address;
            }

            $this->_guestCustomers[] = $customer->toArray() + compact('addresses');
            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        }

        $this->stdout('Done creating customers' . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Create and save address data.
     *
     * @param string $firstName
     * @param string $lastName
     * @param User $customer
     * @return Address
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createAddress(string $firstName, string $lastName, User $customer)
    {
        $country = $this->_getRandomCountry();

        /** @var Address $address */
        $address = Craft::createObject([
            'class' => Address::class,
            'attributes' => [
                'ownerId' => $customer->id,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'addressLine1' => $this->_faker->streetAddress,
                'locality' => $this->_faker->city,
                'postalCode' => $this->_faker->postcode,
                'countryCode' => $country,
            ],
        ]);

        $addressFormat = Craft::$app->getAddresses()->getAddressFormatRepository()->get($country);

        if ($addressFormat->getAdministrativeAreaType() !== null && $subdivision = $this->_getRandomStateFromCountry($country)) {
            $address->administrativeArea = $subdivision->getCode();
        }

        Craft::$app->getElements()->saveElement($address, false);

        return $address;
    }


    /**
     * @return string Country code
     * @throws \Exception
     */
    private function _getRandomCountry(): string
    {
        if (empty($this->_countries)) {
            $this->_countries = $this->_store->getCountries();
        }

        return $this->_faker->randomElement($this->_countries);
    }

    /**
     * @param string $country
     * @return Subdivision|null
     * @throws \Exception
     */
    private function _getRandomStateFromCountry(string $country): ?Subdivision
    {
        $subdivisions = Craft::$app->getAddresses()->getSubdivisionRepository()->getAll([$country]);

        if (empty($subdivisions)) {
            return null;
        }

        return $this->_faker->randomElement($subdivisions);
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
            $date->setTime(random_int(0, (int)$date->format('G')), random_int(0, (int)$date->format('i')), 0);
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
     * @param array $customer
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
            /** @var ProductQuery $productQuery */
            $productQuery = Craft::$app->getElements()->createElementQuery(Product::class);
            $this->_products = $productQuery->all();
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
        $customer = $this->_getRandomCustomer((bool)random_int(0, 1));

        $attributes = [
            'dateUpdated' => $date,
            'dateCreated' => $date,
        ];

        /** @var Order $order */
        $order = Craft::createObject([
            'class' => Order::class,
            'attributes' => $attributes,
        ]);


        $order->setCustomerId($customer['id']);
        $order->number = Plugin::getInstance()->getCarts()->generateCartNumber();

        Craft::$app->getElements()->saveElement($order);

        /** @var Address $billingAddress */
        $billingAddress = Craft::$app->getElements()->duplicateElement($this->_getRandomAddressFromCustomer($customer), [
            'ownerId' => $order->id,
            'title' => Craft::t('commerce', 'Billing Address'),
        ]);
        /** @var Address $shippingAddress */
        $shippingAddress = Craft::$app->getElements()->duplicateElement($this->_getRandomAddressFromCustomer($customer), [
            'ownerId' => $order->id,
            'title' => Craft::t('commerce', 'Shipping Address'),
        ]);

        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        $lineItems = [];
        $numProducts = random_int(1, self::PRODUCTS_PER_ORDER_MAX);
        for ($i = 1; $i <= $numProducts; $i++) {
            $product = $this->_getRandomProduct();
            // Weight the qty in favour of 1 item
            $qty = random_int(0, 9) < 8 ? 1 : 2;
            $lineItems[] = Plugin::getInstance()->getLineItems()->createLineItem($order, $product->getDefaultVariant()->id, [], $qty);
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
        /** @var User|null $author */
        $author = $authorQuery->admin(true)->orderBy('id ASC')->one();

        if (!$reviewsSection || !$author) {
            return;
        }

        $startDateInterval = new DateInterval(self::START_DATE_INTERVAL);

        $this->stdout('Creating reviews ... ' . PHP_EOL);
        $index = 1;
        $numProducts = count($this->_products);
        foreach ($this->_products as $product) {
            $numReviews = random_int(self::REVIEWS_PER_PRODUCT_MIN, self::REVIEWS_PER_PRODUCT_MAX);
            $this->stdout("    - [{$index}/{$numProducts}] Creating {$numReviews} reviews for " . $product->title . ' ... ');
            for ($i = 0; $i <= $numReviews; $i++) {
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
                    'stars' => (string)$stars,
                ]);

                Craft::$app->getElements()->saveElement($review);
            }

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            $index++;
        }

        $this->stdout('Done creating reviews' . PHP_EOL, Console::FG_GREEN);
    }
}
