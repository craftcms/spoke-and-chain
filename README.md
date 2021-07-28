## About nystudio107/spoke-and-chain

This is conversion of the Craft CMS demo site [Spoke and Chain](https://craftcms.com/demo) to a Docker-ized setup that uses [Vite.js](https://vitejs.dev/) modern frontend tooling.

[![Click to Play Video](https://img.youtube.com/vi/_ShZxcXLeXc/0.jpg)](https://www.youtube.com/watch?v=_ShZxcXLeXc)

(Click to Play Video)

Related articles & podcasts:

* [Vite.js Next Generation Frontend Tooling + Craft CMS](https://nystudio107.com/blog/using-vite-js-next-generation-frontend-tooling-with-craft-cms) article
* [An Annotated Docker Config for Frontend Web Development](https://nystudio107.com/blog/an-annotated-docker-config-for-frontend-web-development) article
* [Using Make & Makefiles to Automate your Frontend Workflow](https://nystudio107.com/blog/using-make-makefiles-to-automate-your-frontend-workflow) article
* [Vite.js modern frontend tooling](https://devmode.fm/episodes/vite-js-modern-frontend-tooling) podcast
* [Introduction to Vite in Craft CMS](https://craftquest.io/livestreams/introduction-to-vite-in-craft-cms) video

## Try It Yourself!

### Initial setup

All you'll need is [Docker desktop](https://www.docker.com/products/docker-desktop) for your platform installed, then spin up the Spoke & Chain site in local development.

Ensure no other local development environments are running that might have port conflicts, then:

1. Clone the git repo with:
```
git clone https://github.com/nystudio107/spoke-and-chain.git
```
  
2. Go into the project's directory:
```
   cd spoke-and-chain
```

3. Start up the site by typing this in the project's root directory:
```
make dev
```
(the first build will be somewhat lengthy, ignore the warnings from `queue_1`).

If it appears to hang at `Building php_xdebug`, your PhpStorm or other IDE is likely waiting for an Xdebug connection; quit PhpStorm or stop it from listening for Xdebug during the initial build.

4. Once the site is up and running (see below), navigate to:
```
http://localhost:8000
```

The Vite dev server for Hot Module Replacement (HMR) serving of static resources runs off of `http://localhost:3000`

🎉 You're now up and running Nginx, PHP, MySQL 8, Redis, Xdebug, & Vite without having to do any devops!

The first time you do `make dev` it will be slow, because it has to build all of the Docker images.

Subsequent `make dev` commands will be much faster, but still a little slow because we intentionally do a `composer install` and an `npm install` each time, to keep our dependencies in sync.

Wait until you see the following to indicate that the PHP container is ready:

```
php_1         | Craft is installed.
php_1         | Applying changes from your project config files ... done
php_1         | [01-Dec-2020 18:38:46] NOTICE: fpm is running, pid 22
php_1         | [01-Dec-2020 18:38:46] NOTICE: ready to handle connections
```

...and the following to indicate that the Vite container is ready:
```
vite_1        |   > Local:    http://localhost:3000/
vite_1        |   > Network:  http://172.28.0.3:3000/
vite_1        | 
vite_1        |   ready in 10729ms.
```

All of the Twig files, JavaScript, Vue components, CSS, and even the Vite config itself will relfect changes immediately Hot Module Replacement, so feel free to edit things and play around.

A password-scrubbed seed database will automatically be installed; you can log into the CP at `http://localhost:8000/admin` via these credentials:

**User:** `admin` \
**Password:** `password`

### Makefile Project Commands

This project uses Docker to shrink-wrap the devops it needs to run around the project.

To make using it easier, we're using a Makefile and the built-in `make` utility to create local aliases. You can run the following from terminal in the project directory:

- `make dev` - starts up the local dev server listening on `http://localhost:8000/`
- `make build` - builds the static assets via the Vite buildchain
- `make clean` - shuts down the Docker containers, removes any mounted volumes (including the database), and then rebuilds the containers from scratch
- `make update` - causes the project to update to the latest Composer and NPM dependencies
- `make update-clean` - completely removes `node_modules/` & `vendor/`, then causes the project to update to the latest Composer and NPM dependencies
- `make composer xxx` - runs the `composer` command passed in, e.g. `make composer install`
- `make craft xxx` - runs the `craft` [console command](https://craftcms.com/docs/3.x/console-commands.html) passed in, e.g. `make craft project-config/apply` in the php container
- `make npm xxx` - runs the `npm` command passed in, e.g. `make npm install`

### Things you can try

With the containers up and running, here are a few things you can try:

* Edit a CSS file such as `src/css/components/header.css` to add something like this, and change the colors to see the CSS change instantly via HRM:
```css
* {
  border: 3px solid red;
}
```

* Edit the `src/vue/Confetti.vue` vue component, changing the `defaultSize` and see your changes instantly via HMR (the slider will move)


### Other notes

To update to the latest Composer packages (as constrained by the `cms/composer.json` semvers) and latest npm packages (as constrained by the `buildchain/package.json` semvers), do:
```
make update
```

To start from scratch by removing `buildchain/node_modules/` & `cms/vendor/`, then update to the latest Composer packages (as constrained by the `cms/composer.json` semvers) and latest npm packages (as constrained by the `buildchain/package.json` semvers), do:
```
make update-clean
```

Here's the full, unmodified Spoke & Chain README.md from Pixel & Tonic:

<h1 align="center">Spoke & Chain Craft Commerce Demo</h1>

![Spoke & Chain homepage](https://raw.githubusercontent.com/craftcms/spoke-and-chain/HEAD/web/guide/homepage.png)

## Overview

Spoke & Chain is a fictitious bicycle shop custom-built to demonstrate [Craft CMS](https://craftcms.com) and [Craft Commerce](https://craftcms.com/commerce). This repository houses the source code for our demo, which you can spin up for yourself by visiting [craftcms.com/demo](https://craftcms.com/demo?kind=spokeandchain).

We’ve also included instructions below for setting up the demo in a local development environment with [Craft Nitro](https://getnitro.sh).

Spoke & Chain shows core Craft CMS features and a fully-configured Craft Commerce store:

- Articles and pages with custom layouts and flexible content.
- Front-end global search for products and articles.
- Categorized products with variants, categories, filtering, and sorting.
- Customer membership area with subscription-based services, order tracking and returns, and account management.
- Full, customized checkout process with coupon codes.
- Configured for healthy SEO and built targeting WCAG AA compliance.

### Development Technologies

- [Craft CMS 3](https://craftcms.com/docs/3.x/)
- [Craft Commerce 3](https://craftcms.com/docs/commerce/3.x/)
- PostgreSQL (11.5+) / MySQL (5.7+)
- PHP (7.2.5+), built on the [Yii 2 framework](https://www.yiiframework.com/)
- Native Twig templates with reactive [Sprig](https://plugins.craftcms.com/sprig) components

### Front End Dependencies

- [webpack](https://webpack.js.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [Cypress](https://www.cypress.io)

## Local Development

### Environment

If you’d like to get Spoke & Chain running in a local environment, we recommend using [Craft Nitro](https://getnitro.sh):

1. Follow Nitro’s [installation instructions](https://craftcms.com/docs/nitro/2.x/installation.html) for your OS.
2. Make sure you’ve used `nitro db new` to create a MySQL 8 or MariaDB 10 database engine.
3. Run `nitro create` with the URL to this repository:
    ```zsh
    nitro create craftcms/spoke-and-chain spokeandchain
    ```
    - hostname: `spokeandchain.nitro`
    - web root: `web`
    - PHP version: `8.0`
    - database? `Y`
    - database engine: `mysql-8.0-*.database.nitro` (or `mariadb-latest-*.database.nitro`)
    - database name: `spokeandchain`
    - update env file? `Y`
4. Move to the project directory and add a Craft account for yourself by following the prompts:
    ```zsh
    cd spokeandchain
    nitro craft users/create --admin
    ```

> 💡 If you’re using a different local environment, see Craft’s [Server Requirements](https://craftcms.com/docs/3.x/requirements.html) and [Installation Instructions](https://craftcms.com/docs/3.x/installation.html).

### Front End

Run `npm install` with node 12.19.0 or later. (If you’ve installed [nvm](https://github.com/nvm-sh/nvm) run `nvm use`, then `npm install`.)

If you’ve chosen a different environment setup, make sure your `.env` is configured for it. These environment variables are specifically used by `webpack-dev-server`:

- `DEVSERVER_PUBLIC`
- `DEVSERVER_PORT`
- `DEVSERVER_HOST`
- `TWIGPACK_MANIFEST_PATH`
- `TWIGPACK_PUBLIC_PATH`

You can then run any of the development scripts found in `package.json`:

- `npm run serve` to build and automatically run webpack with hot module reloading for local development
- `npm run build` to build front end assets for production

> 💡 When using `npm run serve`, switch your site’s URL from `https://` to `http://`.

#### PurgeCSS

This project uses PurgeCSS to automatically remove redundant or unused styles generated by Tailwind CSS.

PurgeCSS is disabled by default for the `serve` script, meaning your site will be loaded with every available CSS class. It also means you’ll need to check the site after running `build` to be sure important classes aren’t inadvertently stripped away.

Classes actively being used should be detected automatically, but you can encourage them to be recognized by making sure full class names appear in your template, stylesheet, and JavaScript files.

❌ For example, don’t dynamically combine `text-red-` with a variable for this loop:

```twig
{% set classes = ['100', '500', '900'] %}
{% for class in classes %}
  <div class="text-red-{{ class }}"></div>	
{% endfor %}
```

✅ Loop through complete class names like so they each appear in full:
```twig
{% set classes = ['text-red-100', 'text-red-500', 'text-red-900'] %}
{% for class in classes %}
  <div class="{{ class }}"></div>	
{% endfor %}
```

If you can’t avoid programmatic concatenation, use Tailwind’s [safelist](https://tailwindcss.com/docs/optimizing-for-production#safelisting-specific-classes) option in `tailwind.config.js`.

### Testing

Cypress tests cover multiple parts of the website:

- **control panel** – make sure the content structure is properly defined.
- **front end** – check that the website’s different sections work as expected.
- **accessibility** – evaluate the website for WCAG 2.0 compliance.

Set the environment variables Cypress needs to run by copying `cypress.example.json` to `cypress.json` and adjusting it:

```
cp cypress.example.json cypress.json
```

Open the Cypress Test Runner from the project root:

```
npx cypress open
```

Open accessibility tests only:

```
npx cypress open --config testFiles=./front/a11y/*.spec.js
```

## License

The source code of this project is licensed under the [BSD Zero Clause License](LICENSE.MD) unless stated otherwise.

The imagery used by this project is the property of Marin Bikes, and used with permission. You are not free to use it for your own projects.
